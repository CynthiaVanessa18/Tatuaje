<?php
require_once __DIR__.'/../Services/AdminAccounts.php';
$accountManager=new AdminAccounts(conectarBaseDatos(),(int)currentAccount()['id_cuenta']);
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verifyCsrf();
        $action=$_POST['action']??'';
        if (!in_array($action,['create','update'],true)) throw new DomainException('Las cuentas se desactivan cambiando su estado.');
        $id=$action==='update'?filter_var($_POST['id_cuenta']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]):null;
        if ($action==='update' && !$id) throw new DomainException('Cuenta inválida.');
        $accountManager->save($_POST,$id);
        $_SESSION['notice']='Cuenta guardada correctamente.';
        header('Location: administrador.php?module=cuentas',true,303);exit;
    }
    if ($mode==='edit') {
        $id=filter_var($_GET['id_cuenta']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$id) throw new DomainException('Cuenta inválida.');
        $record=$accountManager->find($id);
    }
} catch (DomainException $ex) { $error=$ex->getMessage(); }
catch (PDOException $ex) { error_log($ex->getMessage());$error='No se pudo guardar la cuenta. Inténtalo nuevamente.'; }
if ($_SERVER['REQUEST_METHOD']==='POST' && $error) {
    $mode=($_POST['action']??'')==='create'?'create':'edit';
    $record=array_intersect_key($_POST,array_flip(['id_cuenta','usuario','correo','rol','estado','nombre','apellidos','telefono']));
}
if (!in_array($mode,['list','create','edit'],true)) $mode='list';
$accountRoleFilter=is_string($_GET['rol']??null)?$_GET['rol']:'';
$accountStateFilter=is_string($_GET['estado']??null)?$_GET['estado']:'';
$list=$accountManager->listing($search,$accountRoleFilter,$accountStateFilter,max(1,(int)($_GET['page']??1)));
