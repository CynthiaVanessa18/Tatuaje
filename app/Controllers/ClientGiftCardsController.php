<?php
require_once __DIR__.'/../Services/ClientGiftCards.php';
$giftError=null;$giftNotice=null;$giftCards=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        verifyCsrf();
        if ($profileMissing) throw new DomainException('Completa los datos de tu cuenta antes de vincular una tarjeta.');
        ClientGiftCards::claim(conectarBaseDatos(),(int)$profileId,is_string($_POST['gift_code']??null)?$_POST['gift_code']:'');
        $giftNotice='Tarjeta vinculada a tu cuenta.';
    } catch (DomainException $ex) { $giftError=$ex->getMessage(); }
}
if (!$profileMissing) $giftCards=ClientGiftCards::owned(conectarBaseDatos(),(int)$profileId);
