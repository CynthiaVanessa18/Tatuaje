<?php
declare(strict_types=1);
if ($_SERVER['REQUEST_METHOD']!=='GET') { header('Allow: GET');http_response_code(405);exit('Este apartado es de consulta.'); }
require_once __DIR__.'/../Services/GiftCardBalances.php';
$balanceService=new GiftCardBalances(conectarBaseDatos());
$search=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,180):'';
$balanceState=is_string($_GET['estado']??null)&&isset(GiftCardBalances::STATES[$_GET['estado']])?$_GET['estado']:'';
$selectedCard=filter_var($_GET['selected_card']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:null;
if (($_GET['suggest']??null)==='1') {
    header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
    echo json_encode($balanceService->suggestions($search,$balanceState),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;
}
$list=$balanceService->listing($search,$balanceState,$selectedCard,max(1,(int)($_GET['page']??1)));
$mode='list';$notice=null;$error=null;$esTienda=false;
