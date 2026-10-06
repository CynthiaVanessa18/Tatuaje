<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/AdminGalleryRepository.php';
require_once __DIR__ . '/../Services/AdminGalleryService.php';

function adminGalleryId(mixed $value): ?int
{
    if (
        !is_scalar($value)
        || !ctype_digit((string) $value)
        || (int) $value < 1
    ) {
        return null;
    }

    return (int) $value;
}

function adminGalleryImageUrl(mixed $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $path = trim(str_replace('\\', '/', $value));

    if ($path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    $path = ltrim($path, '/');

    return str_contains($path, '../') ? null : '../' . $path;
}

$database = conectarBaseDatos();
$galleryRepository = new AdminGalleryRepository($database);
$galleryService = new AdminGalleryService($galleryRepository, dirname(__DIR__, 2));

$modeValue = $_GET['mode'] ?? 'list';
$mode = is_string($modeValue) && in_array($modeValue, ['list', 'create', 'edit'], true)
    ? $modeValue
    : 'list';
$workId = adminGalleryId($_GET['id'] ?? null);
$form = null;
$error = null;
$notice = $_SESSION['admin_gallery_notice'] ?? null;
unset($_SESSION['admin_gallery_notice']);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();

        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

        if ($action === 'save') {
            $postedWorkId = adminGalleryId($_POST['id_tatuaje_realizado'] ?? null);
            $savedWorkId = $galleryService->save($_POST, $_FILES, $postedWorkId);

            $_SESSION['admin_gallery_notice'] = $postedWorkId === null
                ? 'La obra fue añadida a la colección.'
                : 'Los cambios de la obra fueron guardados.';

            header('Location: galeria.php?mode=edit&id=' . $savedWorkId, true, 303);
            exit;
        }

        if ($action === 'toggle') {
            $postedWorkId = adminGalleryId($_POST['id_tatuaje_realizado'] ?? null);
            $publishedValue = $_POST['publicado'] ?? null;

            if (
                $postedWorkId === null
                || !is_scalar($publishedValue)
                || !in_array((string) $publishedValue, ['0', '1'], true)
            ) {
                throw new DomainException('La acción solicitada no es válida.');
            }

            $newPublishedState = (string) $publishedValue === '1';
            $galleryRepository->setPublished($postedWorkId, $newPublishedState);
            $_SESSION['admin_gallery_notice'] = $newPublishedState
                ? 'La obra ahora forma parte de la galería pública.'
                : 'La obra fue ocultada sin borrar su información ni fotografías.';

            header('Location: galeria.php', true, 303);
            exit;
        }

        throw new DomainException('La acción solicitada no es válida.');
    }
} catch (DomainException $exception) {
    $error = $exception->getMessage();
} catch (PDOException $exception) {
    error_log('Administración de galería: ' . $exception->getMessage());
    $databaseCode = (int) ($exception->errorInfo[1] ?? 0);
    $error = match ($databaseCode) {
        1062 => 'Ya existe una obra con ese identificador público.',
        1452 => 'El cliente, artista o estilo seleccionado ya no existe.',
        default => 'No fue posible guardar la obra. Revisa los datos e intenta nuevamente.',
    };
} catch (RuntimeException $exception) {
    error_log('Fotografía de galería: ' . $exception->getMessage());
    $error = 'No fue posible guardar la fotografía. Intenta nuevamente.';
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'save'
    && $error !== null
) {
    $workId = adminGalleryId($_POST['id_tatuaje_realizado'] ?? null);
    $mode = $workId === null ? 'create' : 'edit';
    $form = $_POST;
}

if ($mode === 'edit' && $workId === null) {
    $mode = 'list';
    $error = 'El identificador de la obra no es válido.';
}

if ($mode === 'edit' && $form === null && $workId !== null) {
    $form = $galleryRepository->find($workId);

    if ($form === null) {
        $mode = 'list';
        $error = 'La obra solicitada ya no existe.';
    }
}

if ($mode === 'create' && $form === null) {
    $form = [
        'id_cliente' => '',
        'id_artista' => '',
        'id_categoria' => '',
        'titulo' => '',
        'descripcion' => '',
        'slug' => '',
        'fecha_realizacion' => date('Y-m-d'),
        'autorizacion_publicacion' => 1,
        'publicado' => 1,
        'imagen_url' => null,
        'imagen_descripcion' => '',
        'texto_alternativo' => '',
    ];
}

$searchValue = $_GET['buscar'] ?? '';
$search = is_string($searchValue) ? trim($searchValue) : '';
$search = function_exists('mb_substr') ? mb_substr($search, 0, 100) : substr($search, 0, 100);

$statusValue = $_GET['estado'] ?? '';
$status = is_string($statusValue)
    && in_array($statusValue, ['publicado', 'oculto', 'sin_autorizacion'], true)
        ? $statusValue
        : '';

$works = $galleryRepository->listing($search, $status);
$artists = $galleryRepository->artists();
$clients = $galleryRepository->clients();
$categories = $galleryRepository->categories();
