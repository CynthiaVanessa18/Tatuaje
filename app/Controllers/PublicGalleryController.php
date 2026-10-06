<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/GalleryRepository.php';

function galleryRomanNumeral(int $number): string
{
    if ($number < 1) {
        return '—';
    }

    $values = [
        1000 => 'M',
        900 => 'CM',
        500 => 'D',
        400 => 'CD',
        100 => 'C',
        90 => 'XC',
        50 => 'L',
        40 => 'XL',
        10 => 'X',
        9 => 'IX',
        5 => 'V',
        4 => 'IV',
        1 => 'I',
    ];

    $result = '';
    foreach ($values as $value => $symbol) {
        while ($number >= $value) {
            $result .= $symbol;
            $number -= $value;
        }
    }

    return $result;
}

function galleryFilterId(mixed $value): ?int
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

function galleryImageUrl(mixed $value): ?string
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

    if (str_contains($path, '../')) {
        return null;
    }

    return '../' . $path;
}

$searchValue = $_GET['buscar'] ?? '';
$search = is_string($searchValue) ? trim($searchValue) : '';
$search = function_exists('mb_substr')
    ? mb_substr($search, 0, 100)
    : substr($search, 0, 100);

$categoryId = galleryFilterId($_GET['categoria'] ?? null);
$artistId = galleryFilterId($_GET['artista'] ?? null);

$works = [];
$categories = [];
$artists = [];
$summary = [
    'total_obras' => 0,
    'total_artistas' => 0,
    'total_estilos' => 0,
];
$loadError = null;

try {
    $galleryRepository = new GalleryRepository(conectarBaseDatos());
    $categories = $galleryRepository->categories();
    $artists = $galleryRepository->artists();
    $works = $galleryRepository->search($search, $categoryId, $artistId);
    $summary = $galleryRepository->summary();
} catch (PDOException $exception) {
    error_log('Galería pública: ' . $exception->getMessage());
    $loadError = 'La colección no pudo abrirse en este momento. Intenta nuevamente en unos minutos.';
}
