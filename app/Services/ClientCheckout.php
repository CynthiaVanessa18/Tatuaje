<?php
declare(strict_types=1);
require_once __DIR__.'/StorePromotions.php';
require_once __DIR__.'/ClientGiftCards.php';

final class ClientCheckout
{
    public static function items(PDO $db, array $cart, bool $lock=false): array
    {
        if (!$cart) return [];
        ksort($cart,SORT_NUMERIC);
        $q=$db->prepare('SELECT p.* FROM productos p JOIN categorias_productos c ON c.id_categoria_producto=p.id_categoria_producto WHERE p.id_producto=? AND p.activo=1 AND c.activo=1'.($lock?' FOR UPDATE':''));
        $items=[];
        foreach ($cart as $id=>$quantity) {
            $q->execute([$id]);$p=$q->fetch();
            if (!$p) throw new DomainException('Un artículo del carrito ya no está disponible. Retíralo para continuar.');
            if (!is_int($quantity) || $quantity<1 || $quantity>99 || $quantity>(int)$p['stock_actual']) throw new DomainException('No hay existencias suficientes de '.$p['nombre'].'. Ajusta la cantidad.');
            $p['cantidad']=$quantity;
            $p['centavos']=self::cents($p['precio'])*$quantity;
            $items[]=$p;
        }
        return $items;
    }
    private static function cents(string $price): int { $parts=explode('.',$price);return (int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0'); }
    public static function money(int $cents): string { return intdiv($cents,100).'.'.str_pad((string)($cents%100),2,'0',STR_PAD_LEFT); }
    public static function quote(PDO $db, array $cart, ?int $client, bool $lock=false, ?int $giftCard=null): array
    {
        $quote=StorePromotions::quote(self::items($db,$cart,$lock),StorePromotions::available($db,$client,$lock));
        $quote['order_total']=$quote['total'];
        $quote['gift_credit']=$giftCard && $client?ClientGiftCards::credit($db,$client,$giftCard,$quote['total'],$lock):0;
        $quote['total']-=$quote['gift_credit'];
        return $quote;
    }
    public static function order(PDO $db, int $client, array $cart, string $method, string $token, string $demoResult='aprobado', ?int $expectedTotal=null, ?int $giftCard=null): int
    {
        if (!in_array($method,['efectivo','transferencia','pasarela'],true)) throw new DomainException('Selecciona un método de pago válido.');
        if ($method==='pasarela' && !in_array($demoResult,['aprobado','rechazado'],true)) throw new DomainException('Selecciona una tarjeta de prueba válida.');
        $paymentState=$method==='pasarela'?$demoResult:'pendiente';
        $saleState=$paymentState==='aprobado'?'completada':($paymentState==='rechazado'?'cancelada':'confirmada');
        $db->beginTransaction();
        try {
            $q=$db->prepare('SELECT v.id_venta FROM pagos p JOIN ventas v ON v.id_venta=p.id_venta WHERE p.clave_idempotencia=? AND v.id_cliente=?');
            $q->execute([$token,$client]);
            if ($id=$q->fetchColumn()) { $db->commit();return (int)$id; }
            $q=$db->prepare('SELECT v.id_venta FROM pedidos_sin_cobro g JOIN ventas v ON v.id_venta=g.id_venta WHERE g.clave_idempotencia=? AND v.id_cliente=?');
            $q->execute([$token,$client]);
            if ($id=$q->fetchColumn()) { $db->commit();return (int)$id; }
            $quote=self::quote($db,$cart,$client,true,$giftCard);
            $items=$quote['items'];
            if (!$items) throw new DomainException('Añade al menos un producto al carrito.');
            $total=$quote['total'];
            if ($expectedTotal!==null && $expectedTotal!==$total) throw new DomainException('El precio o la promoción cambió. Actualiza la página y revisa el nuevo total antes de confirmar.');
            if ($total===0) { $paymentState='aprobado';$saleState='completada'; }
            $q=$db->prepare('INSERT INTO ventas (id_cliente,estado,moneda,subtotal,descuento_total) VALUES (?,?,\'CRC\',?,?)');$q->execute([$client,$saleState,self::money($quote['subtotal']),self::money($quote['discount'])]);
            $sale=(int)$db->lastInsertId();
            foreach($items as $item) {
                if ($paymentState!=='rechazado') $db->prepare('UPDATE productos SET stock_actual=stock_actual-? WHERE id_producto=?')->execute([$item['cantidad'],$item['id_producto']]);
                $db->prepare("INSERT INTO detalle_ventas (id_venta,tipo_item,id_producto,descripcion,cantidad,precio_unitario,id_promocion,descuento) VALUES (?,'producto',?,?,?,?,?,?)")->execute([$sale,$item['id_producto'],$item['nombre'],$item['cantidad'],$item['precio'],$item['id_promocion'],self::money($item['descuento_centavos'])]);
            }
            if ($quote['gift_credit']>0 && $paymentState!=='rechazado') ClientGiftCards::redeem($db,$giftCard,$sale,$quote['gift_credit'],$total===0?$token:$token.'-gift');
            if ($total===0 && $quote['gift_credit']===0) {
                $db->prepare('INSERT INTO pedidos_sin_cobro (id_venta,clave_idempotencia) VALUES (?,?)')->execute([$sale,$token]);
            } elseif ($total>0) {
                $db->prepare("INSERT INTO pagos (id_venta,metodo,monto,moneda,estado,clave_idempotencia,proveedor,referencia_externa) VALUES (?,?,?,'CRC',?,?,?,?)")->execute([$sale,$method,self::money($total),$paymentState,$token,$method==='pasarela'?'demo_tienda':null,$method==='pasarela'?'demo-'.$token:null]);
            }
            $db->commit();return $sale;
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack();throw $e; }
    }
}
