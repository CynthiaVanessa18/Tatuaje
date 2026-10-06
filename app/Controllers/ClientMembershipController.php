<?php
require_once __DIR__.'/../Services/ClientMembershipCheckout.php';
$membershipError=null;$membershipQuote=null;
$membershipNotice=$_SESSION['membership_notice_'.$account['id_cuenta']]??null;
unset($_SESSION['membership_notice_'.$account['id_cuenta']]);
$membershipTokenKey='membership_checkout_'.$account['id_cuenta'];
$_SESSION[$membershipTokenKey]??=bin2hex(random_bytes(32));
$membershipPlanId=filter_var($_GET['plan']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$membershipMode=is_string($_GET['modalidad']??null)?$_GET['modalidad']:'mensual';
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verifyCsrf();
        if ($profileMissing) throw new DomainException('Completa tu perfil de cliente con el estudio para comprar.');
        $action=$_POST['membership_action']??'subscribe';
        if ($action==='cancel') {
            $membershipId=filter_var($_POST['id_membresia']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if (!$membershipId || ($_POST['confirm_cancel']??'')!=='1') throw new DomainException('Confirma la cancelación de tu membresía.');
            ClientMembershipCheckout::cancel(conectarBaseDatos(),(int)$profileId,$membershipId);
            $_SESSION['membership_notice_'.$account['id_cuenta']]='Membresía cancelada. Puedes suscribirte a otro plan.';
            unset($_SESSION['membership_receipt_'.$account['id_cuenta']]);
            $_SESSION[$membershipTokenKey]=bin2hex(random_bytes(32));
            header('Location: '.$clientEndpoint.'?section=membresias',true,303);exit;
        }
        if ($action!=='subscribe') throw new DomainException('Acción de membresía inválida.');
        $token=$_POST['checkout_token']??null;
        if (!is_string($token) || !hash_equals($_SESSION[$membershipTokenKey],$token)) throw new DomainException('La solicitud cambió. Vuelve a revisar el plan.');
        $expected=filter_var($_POST['expected_total']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$membershipPlanId || $expected===false) throw new DomainException('Revisa el plan antes de confirmar.');
        $method=is_string($_POST['payment_method']??null)?$_POST['payment_method']:'';
        $result=is_string($_POST['demo_result']??null)?$_POST['demo_result']:'';
        $id=ClientMembershipCheckout::order(conectarBaseDatos(),(int)$profileId,$membershipPlanId,$membershipMode,$method,$token,$expected,$result);
        $_SESSION['membership_receipt_'.$account['id_cuenta']]=$id;
        $_SESSION[$membershipTokenKey]=bin2hex(random_bytes(32));
        header('Location: '.$clientEndpoint.'?section=membresias&paso=confirmacion',true,303);exit;
    }
    if ($membershipPlanId) $membershipQuote=ClientMembershipCheckout::quote(conectarBaseDatos(),$membershipPlanId,$membershipMode);
} catch (DomainException $ex) { $membershipError=$ex->getMessage(); }
catch (PDOException $ex) { error_log($ex->getMessage()); $membershipError='No se pudo registrar la membresía. Inténtalo nuevamente.'; }
$clientMembershipList=$profileMissing?[]:ClientMembershipCheckout::memberships(conectarBaseDatos(),(int)$profileId);
$membershipReceipt=null;
if (($_GET['paso']??'')==='confirmacion') foreach ($clientMembershipList as $row) if ($row['id_membresia']==($_SESSION['membership_receipt_'.$account['id_cuenta']]??null)) { $membershipReceipt=$row;break; }
