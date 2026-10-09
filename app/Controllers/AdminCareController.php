<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/CareRepository.php';
require_once __DIR__ . '/../Services/CareService.php';

$careRepository = new CareRepository(conectarBaseDatos());
$careService = new CareService($careRepository);
$careNotice = $_SESSION['admin_care_notice'] ?? null;
$careError = null;
unset($_SESSION['admin_care_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        $action = (string) ($_POST['accion'] ?? 'guardar');
        $_SESSION['admin_care_notice'] = $action === 'estado'
            ? $careService->toggle($_POST)
            : $careService->save($_POST);
        header('Location: cuidados.php', true, 303);
        exit;
    } catch (DomainException $exception) {
        $careError = $exception->getMessage();
    } catch (PDOException $exception) {
        error_log('Administración de cuidados: ' . $exception->getMessage());
        $careError = 'No fue posible guardar la instrucción. Intenta nuevamente.';
    }
}

$careSearch = is_string($_GET['buscar'] ?? null) ? trim($_GET['buscar']) : '';
$careStatus = is_string($_GET['estado'] ?? null) && in_array($_GET['estado'], ['activo', 'oculto'], true)
    ? $_GET['estado']
    : '';
$editId = filter_var($_GET['editar'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$editingCare = $editId === false ? null : $careRepository->find((int) $editId);
$adminCareInstructions = $careRepository->adminListing($careSearch, $careStatus);
$careSummary = $careRepository->summary();
