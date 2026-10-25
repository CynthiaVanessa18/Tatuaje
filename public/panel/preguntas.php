<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/bootstrap.php';

try {
    $account = requireRole('administrador');
    require __DIR__ . '/../../app/Controllers/AdminFaqController.php';
    require __DIR__ . '/../../app/Views/panel/preguntas.php';
} catch (PDOException $exception) {
    error_log('Panel de preguntas frecuentes: ' . $exception->getMessage());
    http_response_code(503);
    $connectionError = true;
    require __DIR__ . '/../../app/Views/unavailable.php';
}
