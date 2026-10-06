<?php
require_once __DIR__.'/../Services/MembershipPlans.php';
$membershipService=new MembershipPlans(conectarBaseDatos());
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        verifyCsrf();
        if (($_POST['action']??'')!=='configure') throw new DomainException('Acción inválida.');
        $membershipService->save($_POST);
        $_SESSION['notice']='Cuotas y beneficios guardados.';
        header('Location: administrador.php?module=planes',true,303); exit;
    } catch (DomainException $ex) { $error=$ex->getMessage(); }
    catch (PDOException $ex) { error_log($ex->getMessage()); $error='No se pudo guardar. Revisa que el nombre no esté repetido.'; }
}
$membershipPlans=$membershipService->plans();
