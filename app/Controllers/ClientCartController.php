<?php
declare(strict_types=1);
require_once __DIR__.'/../Services/ClientCheckout.php';
$cartKey='cart_'.$account['id_cuenta'];
$tokenKey='checkout_'.$account['id_cuenta'];
$giftKey='gift_'.$account['id_cuenta'];
$cartGiftId=(int)($_SESSION[$giftKey]??0);
$_SESSION[$cartKey]??=[];
$_SESSION[$tokenKey]??=bin2hex(random_bytes(32));
$cartError=null;$cartNotice=$_SESSION['cart_notice']??null;unset($_SESSION['cart_notice']);
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        verifyCsrf();$action=$_POST['cart_action']??'';
        $id=filter_var($_POST['product']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (in_array($action,['add','update','remove'],true)) {
            if (!$id) throw new DomainException('Artículo inválido.');
            if ($action==='remove') unset($_SESSION[$cartKey][$id]);
            else {
                $qty=filter_var($_POST['quantity']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>99]]);
                if (!$qty) throw new DomainException('Selecciona una cantidad entre 1 y 99.');
                $cart=$_SESSION[$cartKey];$cart[$id]=$action==='add'?($cart[$id]??0)+$qty:$qty;
                ClientCheckout::items(conectarBaseDatos(),$cart);$_SESSION[$cartKey]=$cart;
            }
            $_SESSION[$tokenKey]=bin2hex(random_bytes(32));
            $_SESSION['cart_notice']=$action==='add'?'Producto añadido al carrito.':'Carrito actualizado.';
        } elseif ($action==='gift_apply') {
            if ($profileMissing) throw new DomainException('Completa tu cuenta para usar una tarjeta de regalo.');
            $gift=filter_var($_POST['gift_card']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
            if ($gift===false) throw new DomainException('Selecciona una tarjeta válida.');
            if ($gift) ClientGiftCards::credit(conectarBaseDatos(),(int)$profileId,$gift,PHP_INT_MAX);
            $_SESSION[$giftKey]=$gift;$_SESSION[$tokenKey]=bin2hex(random_bytes(32));
            $_SESSION['cart_notice']=$gift?'Tarjeta seleccionada. Su saldo se aplicará al confirmar la compra.':'Tarjeta retirada del carrito.';
        } elseif ($action==='checkout') {
            if ($profileMissing) throw new DomainException('Solicita al administrador completar tu perfil de cliente para comprar.');
            if (!is_string($_POST['checkout_token']??null) || !hash_equals($_SESSION[$tokenKey],$_POST['checkout_token'])) throw new DomainException('El carrito cambió. Revisa la compra antes de confirmar.');
            $expectedTotal=filter_var($_POST['expected_total']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
            if ($expectedTotal===false) throw new DomainException('Revisa el total de tu compra antes de confirmar.');
            $sale=ClientCheckout::order(conectarBaseDatos(),(int)$profileId,$_SESSION[$cartKey],is_string($_POST['payment_method']??null)?$_POST['payment_method']:'',$_SESSION[$tokenKey],is_string($_POST['demo_result']??null)?$_POST['demo_result']:'aprobado',$expectedTotal,$cartGiftId?:null);
            $q=conectarBaseDatos()->prepare("SELECT COALESCE(p.estado,IF(g.id_venta IS NOT NULL,'aprobado',NULL)) AS estado,g.id_venta AS sin_cobro FROM ventas v LEFT JOIN pagos p ON p.id_venta=v.id_venta LEFT JOIN pedidos_sin_cobro g ON g.id_venta=v.id_venta WHERE v.id_venta=? ORDER BY (p.metodo='tarjeta_regalo') ASC LIMIT 1");$q->execute([$sale]);$paymentResult=$q->fetch();$paymentState=$paymentResult['estado'];
            $_SESSION['receipt_'.$account['id_cuenta']]=$sale;
            if ($paymentState==='rechazado') {
                $_SESSION[$tokenKey]=bin2hex(random_bytes(32));
                $_SESSION['cart_notice']='Tarjeta de prueba rechazada. No se descontó inventario ni se realizó un cobro. Puedes intentar con otra opción.';
            } else {
                $_SESSION[$cartKey]=[];unset($_SESSION[$giftKey]);
                $_SESSION['cart_notice']=$cartGiftId?'Pedido registrado con tarjeta de regalo. Revisa el saldo utilizado y el estado del pago restante.':($paymentResult['sin_cobro'] ? 'Pedido confirmado. La promoción cubre el total y no requiere pago.' : ($paymentState==='aprobado'?'Compra de demostración completada. Pago simulado aprobado; no se realizó ningún cobro real.':'Pedido registrado. Artículos reservados y pago pendiente de verificación al retirar en el estudio.'));
            }
        } else throw new DomainException('Acción inválida.');
        $returnQuery=['section'=>$action==='add'?'tienda':'carrito'];
        if ($action==='gift_apply' && ($_GET['section']??'')==='tienda') $returnQuery['section']='tienda';
        if ($action==='checkout') $returnQuery['paso']=$paymentState==='rechazado'?'pago':'confirmacion';
        if ($action==='gift_apply' && ($_GET['paso']??'')==='pago') $returnQuery['paso']='pago';
        if ($returnQuery['section']==='tienda') {
            $returnQuery['categoria']=is_string($_GET['categoria']??null)?$_GET['categoria']:'';
            $returnQuery['q']=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,180):'';
            $returnQuery['page']=max(1,(int)($_GET['page']??1));
        }
        header('Location: '.($clientEndpoint ?? '../index.php').'?'.http_build_query($returnQuery),true,303);exit;
    } catch (DomainException $e) { $cartError=$e->getMessage(); }
    catch (PDOException $e) { error_log($e->getMessage());$cartError='No se pudo registrar la compra. Inténtalo nuevamente.'; }
}
$cartCount=array_sum($_SESSION[$cartKey]);
$cartReceipt=null;
if (!$profileMissing && isset($_SESSION['receipt_'.$account['id_cuenta']])) {
    $q=conectarBaseDatos()->prepare("SELECT v.fecha,v.total,v.subtotal,v.descuento_total,v.moneda,(SELECT COALESCE(SUM(gp.monto),0) FROM pagos gp WHERE gp.id_venta=v.id_venta AND gp.metodo='tarjeta_regalo' AND gp.estado='aprobado') AS gift_credit,p.monto AS remaining_payment,COALESCE(p.metodo,IF(g.id_venta IS NOT NULL,'sin_cobro',NULL)) AS metodo,COALESCE(p.estado,IF(g.id_venta IS NOT NULL,'aprobado',NULL)) AS estado,p.proveedor FROM ventas v LEFT JOIN pagos p ON p.id_venta=v.id_venta LEFT JOIN pedidos_sin_cobro g ON g.id_venta=v.id_venta WHERE v.id_venta=? AND v.id_cliente=? AND (p.id_pago IS NOT NULL OR g.id_venta IS NOT NULL) ORDER BY (p.metodo='tarjeta_regalo') ASC LIMIT 1");
    $q->execute([$_SESSION['receipt_'.$account['id_cuenta']],$profileId]);$cartReceipt=$q->fetch()?:null;
}
$cartItems=[];$cartTotal=0;$cartSubtotal=0;$cartDiscount=0;$cartPromotion=null;$cartGiftCredit=0;
$cartGiftCards=$profileMissing?[]:ClientGiftCards::owned(conectarBaseDatos(),(int)$profileId);
try {
    $cartQuote=ClientCheckout::quote(conectarBaseDatos(),$_SESSION[$cartKey],$profileMissing?null:(int)$profileId,false,$cartGiftId?:null);
    $cartGiftCredit=$cartQuote['gift_credit'];
    $cartItems=$cartQuote['items'];$cartTotal=$cartQuote['total'];$cartSubtotal=$cartQuote['subtotal'];$cartDiscount=$cartQuote['discount'];$cartPromotion=$cartQuote['promotion'];
}
catch (DomainException $e) {
    $cartError=$e->getMessage();
    // Mantener visibles los artículos para poder quitarlos o reducir su cantidad.
    foreach ($_SESSION[$cartKey] as $id=>$quantity) {
        $q=conectarBaseDatos()->prepare('SELECT * FROM productos WHERE id_producto=?');$q->execute([$id]);$p=$q->fetch();
        $cartItems[]=array_merge($p?:['id_producto'=>$id,'nombre'=>'Artículo no disponible','stock_actual'=>0],['cantidad'=>$quantity,'centavos'=>0]);
    }
}
$cartImages=[];
foreach ($cartItems as $item) {
    $q=conectarBaseDatos()->prepare('SELECT imagen_url FROM imagenes_productos WHERE id_producto=? ORDER BY es_portada DESC,orden,id_imagen LIMIT 1');
    $q->execute([$item['id_producto']]);$image=$q->fetchColumn();
    if (is_string($image) && $image!=='' && (!preg_match('~^(?:[a-z][a-z0-9+.-]*:|//)~i',$image) || preg_match('~^https?://~i',$image))) $cartImages[$item['id_producto']]=$image;
}
