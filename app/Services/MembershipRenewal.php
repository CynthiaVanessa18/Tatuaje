<?php
declare(strict_types=1);
require_once __DIR__.'/StorePromotions.php';
final class MembershipRenewal
{
    public const TERMS='demo-2026-10-09';
    public static function settings(PDO $db,int $membership): ?array {
        $q=$db->prepare('SELECT * FROM membresias_renovacion WHERE id_membresia=?');$q->execute([$membership]);return $q->fetch() ?: null;
    }
    public static function configure(PDO $db,int $client,int $membership,bool $enabled,bool $accepted,string $result): void {
        if ($enabled && (!$accepted || !in_array($result,['aprobado','rechazado'],true))) throw new DomainException('Acepta las condiciones y selecciona un resultado de demostración.');
        $db->beginTransaction();
        try {
            $q=$db->prepare("SELECT m.*,d.descripcion,p.monto,p.proveedor,p.estado AS pago_estado FROM membresias_clientes m JOIN detalle_ventas d ON d.id_detalle_venta=m.id_detalle_venta JOIN pagos p ON p.id_venta=d.id_venta AND p.tipo_operacion='cobro' WHERE m.id_membresia=? AND m.id_cliente=? FOR UPDATE");
            $q->execute([$membership,$client]);$m=$q->fetch();
            if (!$m) throw new DomainException('La membresía no pertenece a tu cuenta.');
            if (!$enabled) { $db->prepare('UPDATE membresias_renovacion SET habilitada=0 WHERE id_membresia=?')->execute([$membership]); }
            else {
                if ($m['estado']!=='activa' || $m['fecha_fin']<=gmdate('Y-m-d H:i:s') || $m['pago_estado']!=='aprobado' || $m['proveedor']!=='demo_membresia') throw new DomainException('Solo una membresía de demostración activa puede renovarse automáticamente.');
                $mode=str_ends_with(trim($m['descripcion']),'| anual')?'anual':'mensual';
                $db->prepare('INSERT INTO membresias_renovacion (id_membresia,habilitada,modalidad,importe_centavos,resultado_demo,terminos_version,fecha_aceptacion) VALUES (?,1,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE habilitada=1,modalidad=VALUES(modalidad),importe_centavos=VALUES(importe_centavos),resultado_demo=VALUES(resultado_demo),terminos_version=VALUES(terminos_version),fecha_aceptacion=VALUES(fecha_aceptacion),ultimo_resultado=NULL')->execute([$membership,$mode,StorePromotions::cents((string)$m['monto']),$result,self::TERMS]);
            }
            $db->commit();
        } catch(Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
    }
    public static function process(PDO $db,?int $client=null): void {
        $q=$db->prepare("SELECT m.id_membresia FROM membresias_clientes m JOIN membresias_renovacion r ON r.id_membresia=m.id_membresia WHERE r.habilitada=1 AND m.estado IN ('activa','vencida') AND m.fecha_fin<=UTC_TIMESTAMP()".($client!==null?' AND m.id_cliente=?':''));$q->execute($client!==null?[$client]:[]);
        foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $db->beginTransaction();
            try {
                $q=$db->prepare('SELECT m.*,r.*,p.activo AS plan_activo,p.nombre FROM membresias_clientes m JOIN membresias_renovacion r ON r.id_membresia=m.id_membresia JOIN planes_membresia p ON p.id_plan=m.id_plan WHERE m.id_membresia=? FOR UPDATE');$q->execute([$id]);$m=$q->fetch();
                if (!$m || !$m['habilitada'] || !in_array($m['estado'],['activa','vencida'],true) || $m['fecha_fin']>gmdate('Y-m-d H:i:s')) {$db->commit();continue;}
                if (!$m['plan_activo']) { $db->prepare("UPDATE membresias_renovacion SET habilitada=0,ultimo_resultado='plan_inactivo' WHERE id_membresia=?")->execute([$id]);$db->prepare("UPDATE membresias_clientes SET estado='vencida' WHERE id_membresia=?")->execute([$id]);$db->commit();continue; }
                $approved=$m['resultado_demo']==='aprobado';$amount=StorePromotions::money((int)$m['importe_centavos']);
                $token='renovacion-demo-'.$id.'-'.$m['id_detalle_venta'].'-'.str_replace([' ',':','-'],'',$m['fecha_fin']);
                $db->prepare("INSERT INTO ventas (id_cliente,estado,moneda,subtotal) VALUES (?,?,'CRC',?)")->execute([$m['id_cliente'],$approved?'completada':'cancelada',$amount]);$sale=(int)$db->lastInsertId();
                $db->prepare("INSERT INTO detalle_ventas (id_venta,tipo_item,id_plan,descripcion,cantidad,precio_unitario) VALUES (?,'membresia',?,?,1,?)")->execute([$sale,$m['id_plan'],$m['nombre'].' | '.$m['modalidad'],$amount]);$detail=(int)$db->lastInsertId();
                $db->prepare("INSERT INTO pagos (id_venta,metodo,monto,moneda,estado,clave_idempotencia,proveedor,referencia_externa) VALUES (?,'pasarela',?,'CRC',?,?,'demo_membresia',?)")->execute([$sale,$amount,$approved?'aprobado':'rechazado',$token,$token]);
                if ($approved) {
                    $start=new DateTimeImmutable('now',new DateTimeZone('UTC'));$end=$start->modify('+'.($m['modalidad']==='anual'?365:30).' days');
                    $db->prepare("UPDATE membresias_clientes SET estado='activa',id_detalle_venta=?,fecha_inicio=?,fecha_fin=? WHERE id_membresia=?")->execute([$detail,$start->format('Y-m-d H:i:s'),$end->format('Y-m-d H:i:s'),$id]);
                } else {$db->prepare("UPDATE membresias_clientes SET estado='vencida' WHERE id_membresia=?")->execute([$id]);}
                $db->prepare('UPDATE membresias_renovacion SET habilitada=?,ultimo_resultado=? WHERE id_membresia=?')->execute([$approved?1:0,$approved?'aprobado':'rechazado',$id]);$db->commit();
            } catch(Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
        }
    }
}
