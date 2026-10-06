<?php
require_once __DIR__.'/../Services/ClientAccount.php';
$clientAccountService=new ClientAccount(conectarBaseDatos(),(int)$account['id_cuenta']);
$accountError=null;$accountNotice=$_SESSION['account_notice']??null;unset($_SESSION['account_notice']);
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verifyCsrf();$clientAccountService->save($_POST);
        $_SESSION['account_notice']='Tu cuenta se actualizó correctamente.';
        header('Location: '.$clientEndpoint.'?section=cuenta',true,303);exit;
    }
} catch (DomainException $ex) { $accountError=$ex->getMessage(); }
catch (PDOException $ex) { error_log($ex->getMessage());$accountError='No se pudo actualizar tu cuenta. Inténtalo nuevamente.'; }
$clientAccountData=$clientAccountService->load();
if ($accountError) foreach (['usuario','correo','nombre','apellidos','telefono'] as $field) if (is_string($_POST[$field]??null)) $clientAccountData[$field]=$_POST[$field];
