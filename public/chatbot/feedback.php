<?php
declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') chatbotJson(['ok' => false, 'error' => 'Método no permitido.'], 405);

try {
    verifyCsrf();
    $messageId = filter_var($_POST['id_mensaje'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($messageId === false) throw new DomainException('La respuesta seleccionada no es válida.');
    if (!in_array((string) ($_POST['fue_util'] ?? ''), ['0', '1'], true)) {
        throw new DomainException('Selecciona si la respuesta te resultó útil.');
    }
    chatbotService()->feedback(
        chatbotAccount(),
        (int) $messageId,
        (string) $_POST['fue_util'] === '1',
        isset($_POST['comentario']) ? (string) $_POST['comentario'] : null
    );
    chatbotJson(['ok' => true, 'message' => 'Gracias por ayudar a mejorar el oráculo.']);
} catch (DomainException $exception) {
    chatbotJson(['ok' => false, 'error' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    error_log('Opinión del chatbot: ' . $exception->getMessage());
    chatbotJson(['ok' => false, 'error' => 'No fue posible guardar tu opinión.'], 503);
}
