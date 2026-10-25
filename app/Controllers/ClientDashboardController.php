<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/QuoteRepository.php';

$account = requireRole('cliente');
$database = conectarBaseDatos();
$quoteRepository = new QuoteRepository($database);
$clientProfile = $quoteRepository->clientForAccount((int) $account['id_cuenta']);
$profileMissing = $clientProfile === null;
require_once __DIR__.'/../Services/ClientRatings.php';
$pendingRatingAppointments = array_filter(
    (new ClientRatings($database, (int)$account['id_cuenta']))->appointments(),
    static fn(array $item): bool => $item['id_calificacion'] === null
);
$appointments = [];
$quotes = [];
$quoteSummary = [
    'total' => 0,
    'en_proceso' => 0,
    'respondidas' => 0,
];

if (!$profileMissing) {
    $clientId = (int) $clientProfile['id_cliente'];
    $appointmentQuery = $database->prepare(
        "SELECT
            appointment.id_cita,
            appointment.fecha_hora_inicio,
            appointment.fecha_hora_fin,
            appointment.estado,
            COALESCE(
                NULLIF(artist.nombre_artistico, ''),
                CONCAT_WS(' ', artist.nombre, artist.apellidos)
            ) AS artista
         FROM citas appointment
         INNER JOIN artistas artist
            ON artist.id_artista = appointment.id_artista
         WHERE appointment.id_cliente = :client_id
           AND appointment.fecha_hora_fin >= :local_now
           AND appointment.estado NOT IN ('cancelada', 'no_asistio')
         ORDER BY appointment.fecha_hora_inicio
         LIMIT 6"
    );
    $appointmentQuery->execute([
        'client_id' => $clientId,
        'local_now' => (new DateTimeImmutable('now', new DateTimeZone('America/Costa_Rica')))
            ->format('Y-m-d H:i:s'),
    ]);
    $appointments = $appointmentQuery->fetchAll();

    $quotes = $quoteRepository->quotesForClient($clientId);
    $quoteSummary['total'] = count($quotes);

    foreach ($quotes as $quoteItem) {
        if (in_array($quoteItem['estado'], ['solicitada', 'en_revision'], true)) {
            $quoteSummary['en_proceso']++;
        }

        if (in_array($quoteItem['estado'], ['enviada', 'aceptada'], true)) {
            $quoteSummary['respondidas']++;
        }
    }
}

$recentQuotes = array_slice($quotes, 0, 3);
$clientDisplayName = $clientProfile === null
    ? $account['usuario']
    : trim($clientProfile['nombre'] . ' ' . $clientProfile['apellidos']);
