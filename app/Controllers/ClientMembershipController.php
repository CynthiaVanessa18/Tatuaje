<?php
require_once __DIR__.'/../Services/ClientMembershipCheckout.php';
require_once __DIR__.'/../Services/MembershipRenewal.php';
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
        if (in_array($action,['renew_enable','renew_disable'],true)) {
            $membershipId=filter_var($_POST['id_membresia']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if (!$membershipId) throw new DomainException('Membresía inválida.');
            MembershipRenewal::configure(conectarBaseDatos(),(int)$profileId,$membershipId,$action==='renew_enable',($_POST['accept_renewal_terms']??'')==='1',is_string($_POST['renew_demo_result']??null)?$_POST['renew_demo_result']:'aprobado');
            $_SESSION['membership_notice_'.$account['id_cuenta']]=$action==='renew_enable'?'Renovación automática de demostración activada.':'Renovación desactivada. Conservas tus beneficios hasta finalizar el período pagado.';
            header('Location: '.$clientEndpoint.'?section=membresias',true,303);exit;
        }
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
        $renewalChoice=$_POST['renewal_enabled']??'0';
        if (!in_array($renewalChoice,['0','1'],true)) throw new DomainException('Selecciona si deseas renovar automáticamente.');
        $id=ClientMembershipCheckout::order(conectarBaseDatos(),(int)$profileId,$membershipPlanId,$membershipMode,$method,$token,$expected,$result,$renewalChoice==='1',($_POST['accept_renewal_terms']??'')==='1');
        $_SESSION['membership_receipt_'.$account['id_cuenta']]=$id;
        $_SESSION[$membershipTokenKey]=bin2hex(random_bytes(32));
        header('Location: '.$clientEndpoint.'?section=membresias&paso=confirmacion',true,303);exit;
    }
    if ($membershipPlanId) $membershipQuote=ClientMembershipCheckout::quote(conectarBaseDatos(),$membershipPlanId,$membershipMode);
} catch (DomainException $ex) { $membershipError=$ex->getMessage(); }
catch (PDOException $ex) { error_log($ex->getMessage()); $membershipError='No se pudo registrar la membresía. Inténtalo nuevamente.'; }
if (!$profileMissing) MembershipRenewal::process(conectarBaseDatos(),(int)$profileId);
$clientMembershipList=$profileMissing?[]:ClientMembershipCheckout::memberships(conectarBaseDatos(),(int)$profileId);
foreach ($clientMembershipList as &$ownedMembership) $ownedMembership['renovacion']=MembershipRenewal::settings(conectarBaseDatos(),(int)$ownedMembership['id_membresia']);
unset($ownedMembership);
$membershipReceipt=null;
if (($_GET['paso']??'')==='confirmacion') foreach ($clientMembershipList as $row) if ($row['id_membresia']==($_SESSION['membership_receipt_'.$account['id_cuenta']]??null)) { $membershipReceipt=$row;break; }
