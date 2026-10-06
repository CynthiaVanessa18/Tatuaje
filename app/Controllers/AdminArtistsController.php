<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/AdminArtistRepository.php';
require_once __DIR__ . '/../Services/AdminArtistService.php';

function adminArtistId(mixed $value): ?int
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

$database = conectarBaseDatos();
$artistRepository = new AdminArtistRepository($database);
$artistService = new AdminArtistService(
    $artistRepository,
    dirname(__DIR__, 2)
);

$modeValue = $_GET['mode'] ?? 'list';
$mode = is_string($modeValue)
    && in_array($modeValue, ['list', 'create', 'edit'], true)
        ? $modeValue
        : 'list';

$artistId = adminArtistId($_GET['id'] ?? null);
$form = null;
$selectedCategoryIds = [];
$error = null;
$notice = $_SESSION['admin_artist_notice'] ?? null;
unset($_SESSION['admin_artist_notice']);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();

        $action = is_string($_POST['action'] ?? null)
            ? $_POST['action']
            : '';

        if ($action === 'save') {
            $postedArtistId = adminArtistId($_POST['id_artista'] ?? null);

            $savedArtistId = $artistService->save(
                $_POST,
                $_FILES,
                $postedArtistId
            );

            $_SESSION['admin_artist_notice'] = $postedArtistId === null
                ? 'El perfil del artista fue creado correctamente.'
                : 'Los cambios del artista fueron guardados.';

            header(
                'Location: artistas.php?mode=edit&id=' . $savedArtistId,
                true,
                303
            );
            exit;
        }

        if ($action === 'toggle') {
            $postedArtistId = adminArtistId($_POST['id_artista'] ?? null);
            $activeValue = $_POST['activo'] ?? null;

            if (
                $postedArtistId === null
                || !is_scalar($activeValue)
                || !in_array((string) $activeValue, ['0', '1'], true)
            ) {
                throw new DomainException('La acción solicitada no es válida.');
            }

            $newActiveState = (string) $activeValue === '1';
            $artistRepository->setActive(
                $postedArtistId,
                $newActiveState
            );

            $_SESSION['admin_artist_notice'] = $newActiveState
                ? 'El perfil volvió a estar visible para los clientes.'
                : 'El perfil fue ocultado sin eliminar su historial.';

            header('Location: artistas.php', true, 303);
            exit;
        }

        throw new DomainException('La acción solicitada no es válida.');
    }
} catch (DomainException $exception) {
    $error = $exception->getMessage();
} catch (PDOException $exception) {
    error_log('Administración de artistas: ' . $exception->getMessage());

    $databaseCode = (int) ($exception->errorInfo[1] ?? 0);

    $error = match ($databaseCode) {
        1062 => 'Ya existe un perfil con esa cuenta o identificador público.',
        1452 => 'La cuenta o una especialidad seleccionada ya no existe.',
        default => 'No fue posible guardar los cambios. Revisa los datos e intenta nuevamente.',
    };
} catch (RuntimeException $exception) {
    error_log('Fotografía de artista: ' . $exception->getMessage());
    $error = 'No fue posible guardar la fotografía. Intenta nuevamente.';
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'save'
    && $error !== null
) {
    $artistId = adminArtistId($_POST['id_artista'] ?? null);
    $mode = $artistId === null ? 'create' : 'edit';
    $form = $_POST;
    $selectedCategoryIds = array_values(
        array_filter(
            array_map(
                'intval',
                is_array($_POST['especialidades'] ?? null)
                    ? $_POST['especialidades']
                    : []
            )
        )
    );
}

if ($mode === 'edit' && $artistId === null) {
    $mode = 'list';
    $error = 'El identificador del artista no es válido.';
}

if ($mode === 'edit' && $form === null && $artistId !== null) {
    $form = $artistRepository->find($artistId);

    if ($form === null) {
        $mode = 'list';
        $error = 'El artista solicitado ya no existe.';
    } else {
        $selectedCategoryIds = $artistRepository->categoryIds($artistId);
    }
}

if ($mode === 'create' && $form === null) {
    $form = [
        'id_cuenta' => '',
        'nombre_artistico' => '',
        'nombre' => '',
        'apellidos' => '',
        'telefono' => '',
        'biografia' => '',
        'foto_url' => null,
        'slug' => '',
        'sitio_web_url' => 'https://example.com',
        'activo' => 1,
    ];
}

$searchValue = $_GET['buscar'] ?? '';
$search = is_string($searchValue) ? trim($searchValue) : '';
$search = function_exists('mb_substr')
    ? mb_substr($search, 0, 100)
    : substr($search, 0, 100);

$statusValue = $_GET['estado'] ?? '';
$status = is_string($statusValue)
    && in_array($statusValue, ['activo', 'inactivo'], true)
        ? $statusValue
        : '';

$artists = $artistRepository->listing($search, $status);
$categories = $artistRepository->categories();
$availableAccounts = $artistRepository->availableAccounts($artistId);
