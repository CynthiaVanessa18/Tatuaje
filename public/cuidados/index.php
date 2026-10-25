<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/bootstrap.php';

try {
    $account = currentAccount();
    require __DIR__ . '/../../app/Controllers/PublicCareController.php';
    require __DIR__ . '/../../app/Views/cuidados/index.php';
} catch (PDOException $exception) {
    error_log('Guía pública de cuidados: ' . $exception->getMessage());
    http_response_code(503);
    $connectionError = true;
    require __DIR__ . '/../../app/Views/unavailable.php';
}
