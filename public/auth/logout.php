<?php
require_once __DIR__.'/../../app/Core/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
try { verifyCsrf(); } catch (DomainException $e) { http_response_code(403); exit('Solicitud inválida.'); }
$_SESSION=[]; session_destroy(); header('Location: login.php');
