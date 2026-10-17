<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/ChatbotRepository.php';
require_once __DIR__ . '/../Services/AdminChatbotService.php';

$chatbotRepository = new ChatbotRepository(conectarBaseDatos());
$chatbotAdminService = new AdminChatbotService($chatbotRepository);
$chatbotNotice = $_SESSION['admin_chatbot_notice'] ?? null;
$chatbotError = null;
unset($_SESSION['admin_chatbot_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        $action = (string) ($_POST['accion'] ?? '');
        $message = match ($action) {
            'guardar_fuente' => $chatbotAdminService->saveSource($_POST),
            'estado_fuente' => $chatbotAdminService->toggleSource($_POST),
            'configuracion' => $chatbotAdminService->saveConfiguration($_POST),
            'estado_conversacion' => $chatbotAdminService->changeConversationState($_POST),
            'responder' => $chatbotAdminService->reply($_POST),
            default => throw new DomainException('La acción solicitada no es válida.'),
        };
        $_SESSION['admin_chatbot_notice'] = $message;
        $query = [];
        if (isset($_POST['id_conversacion']) && (int) $_POST['id_conversacion'] > 0) {
            $query['conversacion'] = (int) $_POST['id_conversacion'];
        }
        header('Location: chatbot.php' . ($query === [] ? '' : '?' . http_build_query($query)), true, 303);
        exit;
    } catch (DomainException $exception) {
        $chatbotError = $exception->getMessage();
    } catch (PDOException $exception) {
        error_log('Administración del chatbot: ' . $exception->getMessage());
        $chatbotError = 'No fue posible guardar el cambio. Intenta nuevamente.';
    }
}

$chatbotStateFilter = is_string($_GET['estado'] ?? null) ? trim($_GET['estado']) : '';
$chatbotSearch = is_string($_GET['buscar'] ?? null) ? trim($_GET['buscar']) : '';
$conversationId = filter_var($_GET['conversacion'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$sourceId = filter_var($_GET['editar_fuente'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$chatbotSummary = $chatbotRepository->adminSummary();
$chatbotConfig = $chatbotRepository->configurations();
$chatbotSources = $chatbotRepository->adminSources();
$editingChatbotSource = $sourceId === false ? null : $chatbotRepository->findSource((int) $sourceId);
$chatbotConversations = $chatbotRepository->adminConversations($chatbotStateFilter, $chatbotSearch);
$selectedConversation = $conversationId === false ? null : $chatbotRepository->adminConversation((int) $conversationId);
$selectedMessages = $selectedConversation === null
    ? []
    : $chatbotRepository->messages((int) $selectedConversation['id_conversacion'], 100);
