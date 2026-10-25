<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') chatbotJson(['ok' => false, 'error' => 'Método no permitido.'], 405);

try {
    chatbotJson(['ok' => true, 'data' => chatbotService()->history(chatbotAccount())]);
} catch (Throwable $exception) {
    error_log('Historial del chatbot: ' . $exception->getMessage());
    chatbotJson(['ok' => false, 'error' => 'El oráculo no pudo abrirse. Intenta nuevamente.'], 503);
}
