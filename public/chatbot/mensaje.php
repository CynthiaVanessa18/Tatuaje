<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') chatbotJson(['ok' => false, 'error' => 'Método no permitido.'], 405);

try {
    verifyCsrf();
    $result = chatbotService()->answer(chatbotAccount(), (string) ($_POST['mensaje'] ?? ''));
    chatbotJson(['ok' => true, 'data' => $result]);
} catch (DomainException $exception) {
    chatbotJson(['ok' => false, 'error' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    error_log('Respuesta del chatbot: ' . $exception->getMessage());
    chatbotJson(['ok' => false, 'error' => 'No pude responder en este momento. Intenta nuevamente.'], 503);
}
