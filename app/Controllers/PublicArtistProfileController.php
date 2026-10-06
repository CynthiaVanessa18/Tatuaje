<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/ArtistRepository.php';

$referenceValue = $_GET['artista'] ?? '';
$artistReference = is_string($referenceValue)
    ? trim($referenceValue)
    : '';

if (function_exists('mb_substr')) {
    $artistReference = mb_substr($artistReference, 0, 160);
} else {
    $artistReference = substr($artistReference, 0, 160);
}

if ($artistReference === '') {
    header('Location: index.php');
    exit;
}

$artist = null;
$specialties = [];
$certifications = [];
$portfolio = [];
$reviews = [];
$loadError = null;

try {
    $repository = new ArtistRepository(
        conectarBaseDatos()
    );

    $artist = $repository->findByReference($artistReference);

    if ($artist === null) {
        http_response_code(404);
    } else {
        $artistId = (int) $artist['id_artista'];

        $specialties = $repository->specialties($artistId);
        $certifications = $repository->certifications($artistId);
        $portfolio = $repository->portfolio($artistId);
        $reviews = $repository->reviews($artistId);
    }
} catch (PDOException $exception) {
    error_log(
        'Perfil público de artista: '
        . $exception->getMessage()
    );

    http_response_code(500);

    $loadError =
        'No fue posible consultar el perfil en este momento. '
        . 'Intenta nuevamente en unos minutos.';
}
