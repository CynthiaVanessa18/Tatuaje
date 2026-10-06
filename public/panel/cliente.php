<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/bootstrap.php';
if (isset($_GET['section'])) {
    header('Location: ../index.php?'.http_build_query($_GET),true,307);exit;
}

try {
    require __DIR__ . '/../../app/Controllers/ClientDashboardController.php';
    require __DIR__ . '/../../app/Views/panel/cliente-inicio.php';
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(503);
    $connectionError = true;
    require __DIR__ . '/../../app/Views/unavailable.php';
}
