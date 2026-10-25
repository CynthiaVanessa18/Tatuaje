<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_once __DIR__ . '/../../app/Services/ArtistAvailabilityService.php';
require_once __DIR__ . '/../../app/Services/QuoteValidityPolicy.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('cliente', '../cotizaciones/');
    $date = is_scalar($_GET['fecha'] ?? null) ? trim((string) $_GET['fecha']) : '';
    (new QuoteValidityPolicy())->validatePreferredDate($date);

    $artists = (new ArtistAvailabilityService(conectarBaseDatos()))->availableArtistsOnDate($date, 60);
    echo json_encode(['ok' => true, 'artists' => $artists], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (DomainException $exception) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Disponibilidad pública: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'No fue posible consultar la agenda.'], JSON_UNESCAPED_UNICODE);
}
