<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/FaqRepository.php';
require_once __DIR__ . '/../Services/FaqService.php';

$faqRepository = new FaqRepository(conectarBaseDatos());
$faqService = new FaqService($faqRepository);
$faqNotice = $_SESSION['admin_faq_notice'] ?? null;
$faqError = null;
unset($_SESSION['admin_faq_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        $action = (string) ($_POST['accion'] ?? 'guardar');
        $_SESSION['admin_faq_notice'] = $action === 'estado'
            ? $faqService->toggle($_POST)
            : $faqService->save($_POST);
        header('Location: preguntas.php', true, 303);
        exit;
    } catch (DomainException $exception) {
        $faqError = $exception->getMessage();
    } catch (PDOException $exception) {
        error_log('Administración de preguntas frecuentes: ' . $exception->getMessage());
        $faqError = 'No fue posible guardar la pregunta. Intenta nuevamente.';
    }
}

$searchValue = is_string($_GET['buscar'] ?? null) ? trim($_GET['buscar']) : '';
$categoryValue = is_string($_GET['categoria'] ?? null) ? trim($_GET['categoria']) : '';
$editId = filter_var($_GET['editar'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$editingFaq = $editId === false ? null : $faqRepository->find((int) $editId);
$adminFaqs = $faqRepository->adminListing($searchValue, $categoryValue);
$adminFaqCategories = $faqRepository->categories();
$faqSummary = $faqRepository->summary();
