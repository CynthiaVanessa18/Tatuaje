<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/bootstrap.php';

try {
    $account = currentAccount();
    require __DIR__ . '/../../app/Controllers/PublicFaqController.php';
    require __DIR__ . '/../../app/Views/preguntas/index.php';
} catch (PDOException $exception) {
    error_log('Preguntas frecuentes públicas: ' . $exception->getMessage());
    http_response_code(503);
    $connectionError = true;
    require __DIR__ . '/../../app/Views/unavailable.php';
}
