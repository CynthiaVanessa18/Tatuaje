<?php
declare(strict_types=1);
require_once __DIR__.'/StorePromotions.php';

final class ClientGiftCards
{
    public static function assignRecipient(PDO $db,int $id,string $email): void
    {
        // La asignación proviene de la emisión administrativa, no de cambiar el correo del cliente.
        $q=$db->prepare("INSERT IGNORE INTO tarjetas_regalo_clientes (id_tarjeta,id_cliente) SELECT ?,cl.id_cliente FROM clientes cl JOIN cuentas c ON c.id_cuenta=cl.id_cuenta WHERE c.correo=? AND c.estado='activo'");
        $q->execute([$id,$email]);
    }
    public static function owned(PDO $db,int $client): array
    {
        $q=$db->prepare("SELECT t.*,COALESCE(SUM(CASE WHEN m.tipo IN ('carga_inicial','devolucion') THEN m.monto ELSE -m.monto END),0) AS saldo FROM tarjetas_regalo t LEFT JOIN tarjetas_regalo_clientes tc ON tc.id_tarjeta=t.id_tarjeta LEFT JOIN movimientos_tarjetas_regalo m ON m.id_tarjeta=t.id_tarjeta WHERE tc.id_cliente=? OR (tc.id_tarjeta IS NULL AND EXISTS (SELECT 1 FROM clientes cl JOIN cuentas c ON c.id_cuenta=cl.id_cuenta WHERE cl.id_cliente=? AND c.correo_verificado=1 AND c.correo=t.correo_destinatario)) GROUP BY t.id_tarjeta ORDER BY t.fecha_emision DESC,t.id_tarjeta DESC");
        $q->execute([$client,$client]);return $q->fetchAll();
    }
    public static function claim(PDO $db,int $client,string $code): void
    {
        $code=strtoupper(str_replace(['-',' '],'',trim($code)));
        if (!preg_match('/^(?:[A-F0-9]{12}|[A-F0-9]{32})$/D',$code)) throw new DomainException('Revisa el código de tu tarjeta de regalo.');
        $db->beginTransaction();
        try {
            $q=$db->prepare('SELECT id_tarjeta FROM tarjetas_regalo WHERE codigo_hash=? FOR UPDATE');$q->execute([hash('sha256',$code,true)]);$id=$q->fetchColumn();
            if (!$id) throw new DomainException('No se encontró una tarjeta con ese código.');
            $q=$db->prepare('SELECT id_cliente FROM tarjetas_regalo_clientes WHERE id_tarjeta=?');$q->execute([$id]);$owner=$q->fetchColumn();
            if ($owner && (int)$owner!==$client) throw new DomainException('Esta tarjeta ya está vinculada a otra cuenta.');
            if (!$owner) $db->prepare('INSERT INTO tarjetas_regalo_clientes (id_tarjeta,id_cliente) VALUES (?,?)')->execute([$id,$client]);
            $db->commit();
        } catch (Throwable $ex) { if ($db->inTransaction()) $db->rollBack();throw $ex; }
    }
    public static function usable(array $card): bool
    {
        return $card['estado']==='activa' && $card['moneda']==='CRC' && (float)$card['saldo']>0 && (!$card['fecha_vencimiento'] || $card['fecha_vencimiento']>gmdate('Y-m-d H:i:s'));
    }
    public static function credit(PDO $db,int $client,int $id,int $total,bool $lock=false): int
    {
        $current=null;
        if ($lock) { $q=$db->prepare('SELECT * FROM tarjetas_regalo WHERE id_tarjeta=? FOR UPDATE');$q->execute([$id]);$current=$q->fetch(); }
        if ($lock) {
            if (!$current) throw new DomainException('Selecciona una tarjeta de regalo de tu cuenta.');
            $q=$db->prepare('SELECT id_cliente FROM tarjetas_regalo_clientes WHERE id_tarjeta=? FOR UPDATE');$q->execute([$id]);$owner=$q->fetchColumn();
            if ($owner && (int)$owner!==$client) throw new DomainException('Selecciona una tarjeta de regalo de tu cuenta.');
            if (!$owner) {
                $q=$db->prepare('SELECT c.correo,c.correo_verificado FROM clientes cl JOIN cuentas c ON c.id_cuenta=cl.id_cuenta WHERE cl.id_cliente=? FOR UPDATE');$q->execute([$client]);$account=$q->fetch();
                if (!$account || !$account['correo_verificado'] || strcasecmp($account['correo'],$current['correo_destinatario'])!==0) throw new DomainException('Vincula primero la tarjeta con su código.');
                $db->prepare('INSERT INTO tarjetas_regalo_clientes (id_tarjeta,id_cliente) VALUES (?,?)')->execute([$id,$client]);
            }
        }
        foreach ($lock?[$current]:self::owned($db,$client) as $card) {
            if ((int)$card['id_tarjeta']!==$id) continue;
            if ($lock && $current) {
                $q=$db->prepare('SELECT tipo,monto FROM movimientos_tarjetas_regalo WHERE id_tarjeta=? FOR UPDATE');$q->execute([$id]);$balance=0;
                foreach ($q->fetchAll() as $movement) $balance+=(in_array($movement['tipo'],['carga_inicial','devolucion'],true)?1:-1)*StorePromotions::cents((string)$movement['monto']);
                $card=array_merge($current,['saldo'=>ClientCheckout::money(max(0,$balance))]);
            }
            if (!self::usable($card)) throw new DomainException('Esta tarjeta no tiene saldo disponible, está inactiva o venció.');
            return min($total,StorePromotions::cents((string)$card['saldo']));
        }
        throw new DomainException('Selecciona una tarjeta de regalo de tu cuenta.');
    }
    public static function redeem(PDO $db,int $id,int $sale,int $amount,string $token): void
    {
        $db->prepare("INSERT INTO pagos (id_venta,metodo,monto,moneda,estado,clave_idempotencia) VALUES (?,'tarjeta_regalo',?,'CRC','aprobado',?)")->execute([$sale,ClientCheckout::money($amount),$token]);
        $payment=(int)$db->lastInsertId();
        $db->prepare("INSERT INTO movimientos_tarjetas_regalo (id_tarjeta,id_venta,id_pago,tipo,monto,referencia) VALUES (?,?,?,'canje',?,?)")->execute([$id,$sale,$payment,ClientCheckout::money($amount),'tienda-'.$payment]);
        $q=$db->prepare('SELECT tipo,monto FROM movimientos_tarjetas_regalo WHERE id_tarjeta=? FOR UPDATE');$q->execute([$id]);$balance=0;
        foreach ($q->fetchAll() as $movement) $balance+=(in_array($movement['tipo'],['carga_inicial','devolucion'],true)?1:-1)*StorePromotions::cents((string)$movement['monto']);
        if ($balance<=0) $db->prepare("UPDATE tarjetas_regalo SET estado='agotada' WHERE id_tarjeta=?")->execute([$id]);
    }
}
