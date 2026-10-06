<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/bootstrap.php';

try {
    $account = requireRole('cliente', '../cotizaciones/');
    require __DIR__ . '/../../app/Controllers/PublicQuoteController.php';
    require __DIR__ . '/../../app/Views/cotizaciones/index.php';
} catch (PDOException $exception) {
    error_log('Página de cotizaciones: ' . $exception->getMessage());
    http_response_code(503);
    $connectionError = true;
    require __DIR__ . '/../../app/Views/unavailable.php';
}
