<?php
declare(strict_types=1);
require_once __DIR__.'/../Services/ClientCheckout.php';
$cartKey='cart_'.$account['id_cuenta'];
$tokenKey='checkout_'.$account['id_cuenta'];
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
        } elseif ($action==='checkout') {
            if ($profileMissing) throw new DomainException('Solicita al administrador completar tu perfil de cliente para comprar.');
            if (!is_string($_POST['checkout_token']??null) || !hash_equals($_SESSION[$tokenKey],$_POST['checkout_token'])) throw new DomainException('El carrito cambió. Revisa la compra antes de confirmar.');
            $sale=ClientCheckout::order(conectarBaseDatos(),(int)$profileId,$_SESSION[$cartKey],is_string($_POST['payment_method']??null)?$_POST['payment_method']:'',$_SESSION[$tokenKey],is_string($_POST['demo_result']??null)?$_POST['demo_result']:'aprobado');
            $q=conectarBaseDatos()->prepare('SELECT estado FROM pagos WHERE id_venta=?');$q->execute([$sale]);$paymentState=$q->fetchColumn();
            $_SESSION['receipt_'.$account['id_cuenta']]=$sale;
            if ($paymentState==='rechazado') {
                $_SESSION[$tokenKey]=bin2hex(random_bytes(32));
                $_SESSION['cart_notice']='Tarjeta de prueba rechazada. No se descontó inventario ni se realizó un cobro. Puedes intentar con otra opción.';
            } else {
                $_SESSION[$cartKey]=[];
                $_SESSION['cart_notice']=$paymentState==='aprobado'?'Compra de demostración completada. Pago simulado aprobado; no se realizó ningún cobro real.':'Pedido registrado. Artículos reservados y pago pendiente de verificación al retirar en el estudio.';
            }
        } else throw new DomainException('Acción inválida.');
        $returnQuery=['section'=>$action==='add'?'tienda':'carrito'];
        if ($action==='checkout') $returnQuery['paso']=$paymentState==='rechazado'?'pago':'confirmacion';
        if ($action==='add') {
            $returnQuery['categoria']=is_string($_GET['categoria']??null)?$_GET['categoria']:'';
            $returnQuery['q']=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,180):'';
            $returnQuery['page']=max(1,(int)($_GET['page']??1));
        }
        header('Location: cliente.php?'.http_build_query($returnQuery),true,303);exit;
    } catch (DomainException $e) { $cartError=$e->getMessage(); }
    catch (PDOException $e) { error_log($e->getMessage());$cartError='No se pudo registrar la compra. Inténtalo nuevamente.'; }
}
$cartCount=array_sum($_SESSION[$cartKey]);
$cartReceipt=null;
if (!$profileMissing && isset($_SESSION['receipt_'.$account['id_cuenta']])) {
    $q=conectarBaseDatos()->prepare('SELECT v.fecha,v.total,v.moneda,p.metodo,p.estado,p.proveedor FROM ventas v JOIN pagos p ON p.id_venta=v.id_venta WHERE v.id_venta=? AND v.id_cliente=?');
    $q->execute([$_SESSION['receipt_'.$account['id_cuenta']],$profileId]);$cartReceipt=$q->fetch()?:null;
}
$cartItems=[];$cartTotal=0;
try { $cartItems=ClientCheckout::items(conectarBaseDatos(),$_SESSION[$cartKey]);$cartTotal=array_sum(array_column($cartItems,'centavos')); }
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
