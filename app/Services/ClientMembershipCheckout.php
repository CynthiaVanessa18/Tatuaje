<?php
declare(strict_types=1);
require_once __DIR__.'/StorePromotions.php';
require_once __DIR__.'/MembershipPlans.php';
require_once __DIR__.'/MembershipRenewal.php';

final class ClientMembershipCheckout
{
    public static function refreshStatus(PDO $db, int $client): void
    {
        // Preserve cancellations and expire paid periods at their exact end time.
        $db->prepare("UPDATE membresias_clientes SET estado='vencida'
            WHERE id_cliente=? AND estado='activa' AND fecha_fin<=UTC_TIMESTAMP()")->execute([$client]);
        $db->prepare("UPDATE membresias_clientes m
            JOIN detalle_ventas d ON d.id_detalle_venta=m.id_detalle_venta
            SET m.estado=CASE WHEN m.fecha_fin<=UTC_TIMESTAMP() THEN 'vencida' ELSE 'activa' END
            WHERE m.id_cliente=? AND m.estado='pendiente' AND m.fecha_inicio<=UTC_TIMESTAMP()
            AND EXISTS (SELECT 1 FROM pagos p WHERE p.id_venta=d.id_venta
                AND p.tipo_operacion='cobro' AND p.estado='aprobado')")->execute([$client]);
    }

    public static function quote(PDO $db, int $planId, string $mode, bool $lock=false): array
    {
        if (!in_array($mode,['mensual','anual'],true)) throw new DomainException('Selecciona una modalidad válida.');
        $q=$db->prepare('SELECT p.*,c.modalidad,c.cuota_mensual,c.cuota_anual FROM planes_membresia p JOIN membresias_configuracion c ON c.id_plan=p.id_plan WHERE p.id_plan=? AND p.activo=1'.($lock?' FOR UPDATE':''));
        $q->execute([$planId]); $plan=$q->fetch();
        if (!$plan || !in_array($plan['modalidad'],[$mode,'ambas'],true)) throw new DomainException('El plan o la modalidad ya no está disponible.');
        $total=StorePromotions::cents((string)$plan['cuota_'.$mode]);
        if (!$total) throw new DomainException('Este plan aún no tiene una cuota disponible.');
        return ['plan'=>$plan,'mode'=>$mode,'days'=>$mode==='anual'?365:30,'total'=>$total];
    }

    public static function order(PDO $db, int $client, int $plan, string $mode, string $method, string $token, int $expected, string $result='aprobado', bool $renew=false, bool $accepted=false): int
    {
        if ($renew && !$accepted) throw new DomainException('Acepta los términos para autorizar la renovación automática.');
        if ($method !== 'pasarela' || !in_array($result,['aprobado','rechazado'],true)) throw new DomainException('Las membresías se pagan únicamente con tarjeta.');
        $db->beginTransaction();
        try {
            $q=$db->prepare('SELECT id_cliente FROM clientes WHERE id_cliente=? FOR UPDATE'); $q->execute([$client]);
            if (!$q->fetchColumn()) throw new DomainException('Completa tu perfil de cliente antes de comprar.');
            self::refreshStatus($db,$client);
            $q=$db->prepare("SELECT m.id_membresia FROM membresias_clientes m JOIN detalle_ventas d ON d.id_detalle_venta=m.id_detalle_venta JOIN pagos p ON p.id_venta=d.id_venta WHERE p.clave_idempotencia=? AND m.id_cliente=?");
            $q->execute([$token,$client]);
            if ($id=$q->fetchColumn()) { $db->commit(); return (int)$id; }
            $q=$db->prepare("SELECT id_membresia FROM membresias_clientes WHERE id_cliente=? AND (estado='pendiente' OR (estado='activa' AND fecha_fin>UTC_TIMESTAMP())) FOR UPDATE");
            $q->execute([$client]);
            if ($q->fetchColumn()) throw new DomainException('Ya tienes una membresía activa o pendiente de pago. Revisa Mis membresías.');
            $quote=self::quote($db,$plan,$mode,true);
            if ($quote['total']!==$expected) throw new DomainException('La cuota cambió. Revisa el nuevo precio antes de confirmar.');
            $payment=$method==='pasarela'?$result:'pendiente';
            $state=match($payment) {'aprobado'=>'activa','rechazado'=>'cancelada',default=>'pendiente'};
            $saleState=match($payment) {'aprobado'=>'completada','rechazado'=>'cancelada',default=>'confirmada'};
            $amount=StorePromotions::money($quote['total']);
            $db->prepare("INSERT INTO ventas (id_cliente,estado,moneda,subtotal) VALUES (?,?,'CRC',?)")->execute([$client,$saleState,$amount]);
            $sale=(int)$db->lastInsertId();
            $db->prepare("INSERT INTO detalle_ventas (id_venta,tipo_item,id_plan,descripcion,cantidad,precio_unitario) VALUES (?,'membresia',?,?,1,?)")->execute([$sale,$plan,$quote['plan']['nombre'].' | '.$mode,$amount]);
            $detail=(int)$db->lastInsertId();
            $db->prepare("INSERT INTO pagos (id_venta,metodo,monto,moneda,estado,clave_idempotencia,proveedor,referencia_externa) VALUES (?,?,?,'CRC',?,?,?,?)")->execute([$sale,$method,$amount,$payment,$token,$method==='pasarela'?'demo_membresia':null,$method==='pasarela'?'demo-'.$token:null]);
            $start=new DateTimeImmutable('now',new DateTimeZone('UTC'));
            $db->prepare('INSERT INTO membresias_clientes (id_cliente,id_plan,id_detalle_venta,fecha_inicio,fecha_fin,estado) VALUES (?,?,?,?,?,?)')->execute([$client,$plan,$detail,$start->format('Y-m-d H:i:s'),$start->modify('+'.$quote['days'].' days')->format('Y-m-d H:i:s'),$state]);
            $id=(int)$db->lastInsertId();
            if ($renew && $payment==='aprobado') {
                $db->prepare("INSERT INTO membresias_renovacion (id_membresia,habilitada,modalidad,importe_centavos,resultado_demo,terminos_version,fecha_aceptacion) VALUES (?,1,?,?,'aprobado',?,UTC_TIMESTAMP())")->execute([$id,$mode,$quote['total'],MembershipRenewal::TERMS]);
            }
            $db->commit(); return $id;
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
    }

    public static function cancel(PDO $db, int $client, int $membership): void
    {
        $db->beginTransaction();
        try {
            $q=$db->prepare('SELECT id_cliente FROM clientes WHERE id_cliente=? FOR UPDATE');$q->execute([$client]);
            if (!$q->fetchColumn()) throw new DomainException('Cliente no disponible.');
            self::refreshStatus($db,$client);
            $q=$db->prepare('SELECT m.estado,d.id_venta FROM membresias_clientes m JOIN detalle_ventas d ON d.id_detalle_venta=m.id_detalle_venta WHERE m.id_membresia=? AND m.id_cliente=? FOR UPDATE');
            $q->execute([$membership,$client]);$row=$q->fetch();
            if (!$row) throw new DomainException('Esta membresía no pertenece a tu cuenta.');
            if ($row['estado']==='cancelada') { $db->commit();return; }
            if (!in_array($row['estado'],['activa','pendiente'],true)) throw new DomainException('Esta membresía ya no se puede cancelar.');
            $q=$db->prepare("SELECT id_pago,estado FROM pagos WHERE id_venta=? AND tipo_operacion='cobro' FOR UPDATE");$q->execute([$row['id_venta']]);$payments=$q->fetchAll();
            $db->prepare("UPDATE membresias_clientes SET estado='cancelada' WHERE id_membresia=? AND id_cliente=?")->execute([$membership,$client]);
            $db->prepare('UPDATE membresias_renovacion SET habilitada=0 WHERE id_membresia=?')->execute([$membership]);
            $hasApproved=false;
            foreach ($payments as $payment) {
                if ($payment['estado']==='aprobado') $hasApproved=true;
                if ($payment['estado']==='pendiente') $db->prepare("UPDATE pagos SET estado='cancelado' WHERE id_pago=?")->execute([$payment['id_pago']]);
            }
            // Conserva los pagos aprobados: cancelar beneficios no emite reembolsos.
            if (!$hasApproved) $db->prepare("UPDATE ventas SET estado='cancelada' WHERE id_venta=?")->execute([$row['id_venta']]);
            $db->commit();
        } catch (Throwable $ex) { if ($db->inTransaction()) $db->rollBack();throw $ex; }
    }

    public static function memberships(PDO $db, int $client): array
    {
        self::refreshStatus($db,$client);
        $q=$db->prepare("SELECT m.*,p.nombre,d.descripcion,v.total,pa.metodo,pa.estado AS pago_estado FROM membresias_clientes m JOIN planes_membresia p ON p.id_plan=m.id_plan JOIN detalle_ventas d ON d.id_detalle_venta=m.id_detalle_venta JOIN ventas v ON v.id_venta=d.id_venta JOIN pagos pa ON pa.id_venta=v.id_venta AND pa.tipo_operacion='cobro' WHERE m.id_cliente=? ORDER BY m.id_membresia DESC");
        $q->execute([$client]);$rows=$q->fetchAll();
        $rules=$db->prepare('SELECT codigo,valor FROM membresias_reglas WHERE id_plan=?');$byPlan=[];$textsByPlan=[];
        foreach ($rows as &$row) {
            if (!isset($byPlan[$row['id_plan']])) { $rules->execute([$row['id_plan']]);$byPlan[$row['id_plan']]=array_column($rules->fetchAll(),'valor','codigo'); }
            $row['reglas']=$byPlan[$row['id_plan']];
            $textsByPlan[$row['id_plan']]??=(new MembershipPlans($db))->texts((int)$row['id_plan']);
            $row['textos']=$textsByPlan[$row['id_plan']];
        }
        unset($row);return $rows;
    }
}
