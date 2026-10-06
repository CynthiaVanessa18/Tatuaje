<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/ArtistRepository.php';

$search = is_string($_GET['buscar'] ?? null)
    ? trim($_GET['buscar'])
    : '';

if (function_exists('mb_substr')) {
    $search = mb_substr($search, 0, 100);
} else {
    $search = substr($search, 0, 100);
}

$categoryValue = $_GET['categoria'] ?? null;

$categoryId = is_string($categoryValue)
    && ctype_digit($categoryValue)
    && (int) $categoryValue > 0
        ? (int) $categoryValue
        : null;

$artists = [];
$categories = [];
$loadError = null;

try {
    $repository = new ArtistRepository(
        conectarBaseDatos()
    );

    $categories = $repository->categories();

    $artists = $repository->search(
        $search,
        $categoryId
    );
} catch (PDOException $exception) {
    error_log(
        'Directorio público de artistas: '
        . $exception->getMessage()
    );

    $loadError =
        'No fue posible abrir el archivo de artistas. '
        . 'Intenta nuevamente en unos minutos.';
}