<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/bootstrap.php';

try {
    $account = requireRole('administrador');
    require __DIR__ . '/../../app/Controllers/AdminChatbotController.php';
    require __DIR__ . '/../../app/Views/panel/chatbot.php';
} catch (PDOException $exception) {
    error_log('Panel del chatbot: ' . $exception->getMessage());
    http_response_code(503);
    $connectionError = true;
    require __DIR__ . '/../../app/Views/unavailable.php';
}
