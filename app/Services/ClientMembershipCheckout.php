<?php
declare(strict_types=1);
require_once __DIR__.'/StorePromotions.php';

final class ClientMembershipCheckout
{
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

    public static function order(PDO $db, int $client, int $plan, string $mode, string $method, string $token, int $expected, string $result='aprobado'): int
    {
        if (!in_array($method,['efectivo','transferencia','pasarela'],true) || !in_array($result,['aprobado','rechazado'],true)) throw new DomainException('Selecciona una opción de pago válida.');
        $db->beginTransaction();
        try {
            $q=$db->prepare('SELECT id_cliente FROM clientes WHERE id_cliente=? FOR UPDATE'); $q->execute([$client]);
            if (!$q->fetchColumn()) throw new DomainException('Completa tu perfil de cliente antes de comprar.');
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
            $id=(int)$db->lastInsertId(); $db->commit(); return $id;
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
    }

    public static function cancel(PDO $db, int $client, int $membership): void
    {
        $db->beginTransaction();
        try {
            $q=$db->prepare('SELECT id_cliente FROM clientes WHERE id_cliente=? FOR UPDATE');$q->execute([$client]);
            if (!$q->fetchColumn()) throw new DomainException('Cliente no disponible.');
            $q=$db->prepare('SELECT m.estado,d.id_venta FROM membresias_clientes m JOIN detalle_ventas d ON d.id_detalle_venta=m.id_detalle_venta WHERE m.id_membresia=? AND m.id_cliente=? FOR UPDATE');
            $q->execute([$membership,$client]);$row=$q->fetch();
            if (!$row) throw new DomainException('Esta membresía no pertenece a tu cuenta.');
            if ($row['estado']==='cancelada') { $db->commit();return; }
            if (!in_array($row['estado'],['activa','pendiente'],true)) throw new DomainException('Esta membresía ya no se puede cancelar.');
            $q=$db->prepare("SELECT id_pago,estado FROM pagos WHERE id_venta=? AND tipo_operacion='cobro' FOR UPDATE");$q->execute([$row['id_venta']]);$payments=$q->fetchAll();
            $db->prepare("UPDATE membresias_clientes SET estado='cancelada' WHERE id_membresia=? AND id_cliente=?")->execute([$membership,$client]);
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
        $q=$db->prepare("SELECT m.*,p.nombre,d.descripcion,v.total,pa.metodo,pa.estado AS pago_estado FROM membresias_clientes m JOIN planes_membresia p ON p.id_plan=m.id_plan JOIN detalle_ventas d ON d.id_detalle_venta=m.id_detalle_venta JOIN ventas v ON v.id_venta=d.id_venta JOIN pagos pa ON pa.id_venta=v.id_venta AND pa.tipo_operacion='cobro' WHERE m.id_cliente=? ORDER BY m.id_membresia DESC");
        $q->execute([$client]);$rows=$q->fetchAll();
        $rules=$db->prepare('SELECT codigo,valor FROM membresias_reglas WHERE id_plan=?');$byPlan=[];
        foreach ($rows as &$row) {
            if (!isset($byPlan[$row['id_plan']])) { $rules->execute([$row['id_plan']]);$byPlan[$row['id_plan']]=array_column($rules->fetchAll(),'valor','codigo'); }
            $row['reglas']=$byPlan[$row['id_plan']];
        }
        unset($row);return $rows;
    }
}
