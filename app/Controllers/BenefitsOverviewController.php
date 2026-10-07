<?php
declare(strict_types=1);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('Configura los beneficios en su apartado original.');
}
require_once __DIR__.'/../Services/BenefitsOverview.php';
$section=is_string($_GET['benefit_section']??null)?$_GET['benefit_section']:'productos';
$benefitsOverview=(new BenefitsOverview(conectarBaseDatos()))->listing($section,max(1,(int)($_GET['page']??1)));
$mode='list'; $notice=null; $error=null; $esTienda=false;
