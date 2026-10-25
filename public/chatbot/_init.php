<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_once __DIR__ . '/../../app/Services/ChatbotService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function chatbotJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function chatbotAccount(): ?array
{
    try {
        return currentAccount();
    } catch (PDOException $exception) {
        error_log('Sesión del chatbot: ' . $exception->getMessage());
        return null;
    }
}

function chatbotService(): ChatbotService
{
    return new ChatbotService(new ChatbotRepository(conectarBaseDatos()));
}
