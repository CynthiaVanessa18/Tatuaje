<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/bootstrap.php';

try {
    $account = requireRole('cliente');
    require __DIR__ . '/../../app/Controllers/ClientTestimonialsController.php';
    require __DIR__ . '/../../app/Views/panel/cliente-testimonios.php';
} catch (PDOException $exception) {
    error_log('Panel de testimonios del cliente: ' . $exception->getMessage());
    http_response_code(503);
    $connectionError = true;
    require __DIR__ . '/../../app/Views/unavailable.php';
}
