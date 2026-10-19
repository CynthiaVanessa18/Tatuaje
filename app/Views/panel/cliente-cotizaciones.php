<?php
declare(strict_types=1);

$statusLabels = [
    'solicitada' => 'Recibida',
    'en_revision' => 'En revisión',
    'enviada' => 'Respuesta lista',
    'aceptada' => 'Aceptada',
    'rechazada' => 'No viable',
    'vencida' => 'Vencida',
    'cancelada' => 'Cancelada',
];

$appointmentStatusLabels = [
    'pendiente' => 'Pendiente de confirmación',
    'confirmada' => 'Confirmada',
    'en_proceso' => 'En proceso',
    'finalizada' => 'Finalizada',
    'cancelada' => 'Cancelada',
    'no_asistio' => 'No asistió',
];

function clientQuoteLocalDate(?string $value, string $format = 'd/m/Y H:i'): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('America/Costa_Rica'))
        ->format($format);
}

function clientQuoteDuration(?int $minutes): string
{
    if ($minutes === null || $minutes <= 0) {
        return 'Por definir';
    }

    $hours = intdiv($minutes, 60);
    $remainingMinutes = $minutes % 60;
    $parts = [];

    if ($hours > 0) {
        $parts[] = $hours . ' ' . ($hours === 1 ? 'hora' : 'horas');
    }

    if ($remainingMinutes > 0) {
        $parts[] = $remainingMinutes . ' min';
    }

    return implode(' ', $parts);
}

function clientQuoteExpired(?string $value): bool
{
    if ($value === null || $value === '') {
        return false;
    }

    return new DateTimeImmutable($value, new DateTimeZone('UTC'))
        < new DateTimeImmutable('now', new DateTimeZone('UTC'));
}

function clientAppointmentLocalDate(?string $value, string $format = 'd/m/Y H:i'): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    // Las citas se guardan como hora local de Costa Rica para que los
    // disparadores de disponibilidad de MariaDB comparen la agenda correctamente.
    return (new DateTimeImmutable($value, new DateTimeZone('America/Costa_Rica')))
        ->format($format);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080506">
    <title>Mis cotizaciones · Tinta Viva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:wght@500;600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-artistas.css">
    <link rel="stylesheet" href="../assets/css/cliente-cotizaciones.css">
<link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>">
<?php responsiveAssets(); ?>
</head>

<body class="client-shared-layout admin-artists client-quotes">
<?php $clientEndpoint='../index.php';$publicPrefix='../';require __DIR__.'/../cliente-navegacion.php'; ?>

<main class="admin-main">
    <header class="admin-heading client-heading">
        <div><p class="admin-eyebrow">EL ARCHIVO DE TU PIEL</p><h1>Mis cotizaciones</h1><p>Sigue cada idea desde la primera confesión hasta la respuesta del estudio.</p></div>
        <a class="admin-button admin-button--primary" href="../cotizaciones/">+ Nueva solicitud</a>
    </header>

    <?php if ($profileMissing): ?><div class="admin-notice admin-notice--warning"><span>!</span>Tu cuenta todavía no tiene un perfil de cliente vinculado. Solicita ayuda al administrador.</div><?php endif ?>
    <?php if ($notice !== null): ?><div class="admin-notice" role="status"><span>✓</span><?= e($notice) ?></div><?php endif ?>
    <?php if ($error !== null): ?><div class="admin-notice admin-notice--error"><span>!</span><?= e($error) ?></div><?php endif ?>

    <?php if ($selectedQuote !== null): ?>
        <?php
        $quoteExpired = clientQuoteExpired($selectedQuote['fecha_vencimiento']);
        $proposalVisible = $selectedQuote['precio_cotizado'] !== null
            && in_array($selectedQuote['estado'], ['enviada', 'aceptada', 'rechazada', 'vencida', 'cancelada'], true);
        $canRespond = $selectedQuote['estado'] === 'enviada'
            && $proposalVisible
            && !$quoteExpired;
        ?>
        <section class="client-quote-detail">
            <header><div><p class="admin-eyebrow">SOLICITUD #<?= e($selectedQuote['id_cotizacion']) ?></p><h2><?= e($selectedQuote['categoria']) ?></h2><span class="client-status client-status--<?= e($selectedQuote['estado']) ?>"><?= e($statusLabels[$selectedQuote['estado']] ?? $selectedQuote['estado']) ?></span></div><a href="mis-cotizaciones.php">Cerrar detalle ×</a></header>
            <div class="client-quote-detail__grid">
                <div class="client-quote-vision"><p class="client-quote-label">TU VISIÓN</p><blockquote><?= nl2br(e($selectedQuote['descripcion_idea'])) ?></blockquote><dl><div><dt>Artista</dt><dd><?= e($selectedQuote['artista']) ?></dd></div><div><dt>Zona</dt><dd><?= e($selectedQuote['zona_cuerpo']) ?></dd></div><div><dt>Tamaño</dt><dd><?= e($selectedQuote['tamano_descripcion'] ?: 'Por definir') ?></dd></div><div><dt>Fecha preferida</dt><dd><?= $selectedQuote['fecha_preferida'] ? e(date('d/m/Y', strtotime((string) $selectedQuote['fecha_preferida']))) . ' · aún no reservada' : 'Sin definir' ?></dd></div><div><dt>Solicitada</dt><dd><?= e(clientQuoteLocalDate($selectedQuote['fecha_solicitud'])) ?></dd></div></dl></div>
                <div class="client-quote-response">
                    <p class="client-quote-label">RESPUESTA DEL ESTUDIO</p>
                    <?php if (!$proposalVisible): ?>
                        <div class="client-pending-sigil"><span>⌛</span><h3>La interpretación continúa</h3><p>Aún estamos calculando artista, tiempo y valor. El estado cambiará cuando la respuesta esté lista.</p></div>
                    <?php else: ?>
                        <div class="client-price"><small>VALOR COTIZADO</small><b>₡<?= e(number_format((float) $selectedQuote['precio_cotizado'], 0, ',', '.')) ?></b><span><?= e($selectedQuote['sesiones_estimadas'] ?: '—') ?> <?= (int) ($selectedQuote['sesiones_estimadas'] ?? 0) === 1 ? 'sesión' : 'sesiones' ?> · <?= e(clientQuoteDuration($selectedQuote['duracion_estimada_minutos'] !== null ? (int) $selectedQuote['duracion_estimada_minutos'] : null)) ?></span></div>
                        <dl><div><dt>Anticipo</dt><dd>₡<?= e(number_format((float) $selectedQuote['anticipo_requerido'], 0, ',', '.')) ?></dd></div><div><dt>Fecha límite para aceptar</dt><dd><?= e(clientQuoteLocalDate($selectedQuote['fecha_vencimiento'], 'd/m/Y H:i')) ?></dd></div></dl>
                    <?php endif ?>

                    <?php if ($proposalVisible && $selectedQuote['observaciones']): ?><div class="client-observations"><b>Notas del estudio</b><p><?= nl2br(e($selectedQuote['observaciones'])) ?></p></div><?php endif ?>

                    <?php if ($canRespond): ?>
                        <form method="post" class="client-decision-form">
                            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                            <input type="hidden" name="id_cotizacion" value="<?= e($selectedQuote['id_cotizacion']) ?>">
                            <div><p class="client-quote-label">TU DECISIÓN</p><h3>¿Cómo deseas continuar?</h3><p>Aceptar aprueba el precio y las condiciones, pero todavía no reserva la fecha preferida. El estudio confirmará cada sesión por separado.</p></div>
                            <div class="client-session-preferences">
                                <p class="client-quote-label">FECHAS PREFERIDAS POR SESIÓN</p>
                                <p>Elige un día distinto para cada sesión. Solo aparecen días donde <?= e($selectedQuote['artista']) ?> tiene un espacio suficiente según la duración cotizada.</p>
                                <?php $clientEstimatedSessions = max(1, (int) ($selectedQuote['sesiones_estimadas'] ?? 1)); ?>
                                <?php for ($sessionNumber = 1; $sessionNumber <= $clientEstimatedSessions; $sessionNumber++): ?>
                                    <?php $submittedSessionDate = is_array($_POST['fechas_sesiones'] ?? null) ? ($_POST['fechas_sesiones'][$sessionNumber] ?? '') : ''; ?>
                                    <?php $suggestedSessionDate = $submittedSessionDate ?: ($sessionNumber === 1 ? ($selectedQuote['fecha_preferida'] ?? '') : ''); ?>
                                    <label><span>Sesión <?= e($sessionNumber) ?> de <?= e($clientEstimatedSessions) ?> *</span><select name="fechas_sesiones[<?= e($sessionNumber) ?>]" required><option value="">Selecciona un día disponible</option><?php foreach ($clientSessionDateOptions as $availableDate): ?><option value="<?= e($availableDate) ?>" <?= $suggestedSessionDate === $availableDate ? 'selected' : '' ?>><?= e((new DateTimeImmutable($availableDate))->format('d/m/Y')) ?></option><?php endforeach ?></select></label>
                                <?php endfor ?>
                                <?php if ($clientSessionDateOptions === []): ?><small>No hay fechas disponibles en los próximos seis meses. Solicita cambios para que el estudio revise la agenda.</small><?php endif ?>
                            </div>
                            <label><span>Mensaje para el estudio</span><textarea name="mensaje" rows="4" maxlength="1500" placeholder="Es obligatorio si solicitas cambios; para aceptar o rechazar es opcional."><?= e($responseMessage) ?></textarea></label>
                            <div class="client-decision-form__actions">
                                <button class="admin-button admin-button--success" type="submit" name="accion" value="aceptar">Aceptar cotización</button>
                                <button class="admin-button admin-button--primary" type="submit" name="accion" value="solicitar_cambios" formnovalidate>Solicitar cambios</button>
                                <button class="admin-button admin-button--danger" type="submit" name="accion" value="rechazar" formnovalidate>Rechazar</button>
                            </div>
                        </form>
                    <?php elseif ($selectedQuote['estado'] === 'aceptada'): ?>
                        <div class="client-response-state client-response-state--accepted"><span>✓</span><div><b>Cotización aceptada</b><p>El administrador podrá proponer una cita. El correo se enviará únicamente cuando esa cita quede confirmada.</p></div></div>
                        <?php if (($selectedQuote['preferencias_sesiones'] ?? []) !== []): ?><div class="client-session-preferences"><p class="client-quote-label">TUS FECHAS PROPUESTAS</p><?php foreach ($selectedQuote['preferencias_sesiones'] as $sessionNumber => $preferredSessionDate): ?><p><b>Sesión <?= e($sessionNumber) ?>:</b> <?= e((new DateTimeImmutable($preferredSessionDate))->format('d/m/Y')) ?> · aún no reservada</p><?php endforeach ?></div><?php endif ?>
                        <?php if (($selectedQuote['preferencias_sesiones'] ?? []) === []): ?>
                            <form method="post" class="client-decision-form">
                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                <input type="hidden" name="id_cotizacion" value="<?= e($selectedQuote['id_cotizacion']) ?>">
                                <input type="hidden" name="accion" value="guardar_fechas">
                                <div><p class="client-quote-label">FALTA ELEGIR LAS FECHAS</p><h3>Propón un día para cada sesión</h3><p>Tu cotización ya está aceptada, pero necesitamos estas preferencias antes de preparar la agenda definitiva.</p></div>
                                <div class="client-session-preferences">
                                    <?php $clientEstimatedSessions = max(1, (int) ($selectedQuote['sesiones_estimadas'] ?? 1)); ?>
                                    <?php for ($sessionNumber = 1; $sessionNumber <= $clientEstimatedSessions; $sessionNumber++): ?>
                                        <?php $suggestedSessionDate = $sessionNumber === 1 ? ($selectedQuote['fecha_preferida'] ?? '') : ''; ?>
                                        <label><span>Sesión <?= e($sessionNumber) ?> de <?= e($clientEstimatedSessions) ?> *</span><select name="fechas_sesiones[<?= e($sessionNumber) ?>]" required><option value="">Selecciona un día disponible</option><?php foreach ($clientSessionDateOptions as $availableDate): ?><option value="<?= e($availableDate) ?>" <?= $suggestedSessionDate === $availableDate ? 'selected' : '' ?>><?= e((new DateTimeImmutable($availableDate))->format('d/m/Y')) ?></option><?php endforeach ?></select></label>
                                    <?php endfor ?>
                                    <?php if ($clientSessionDateOptions === []): ?><small>No hay fechas disponibles en los próximos seis meses. Contacta al estudio para revisar la agenda.</small><?php endif ?>
                                </div>
                                <button class="admin-button admin-button--success" type="submit" <?= $clientSessionDateOptions === [] ? 'disabled' : '' ?>>Guardar fechas preferidas</button>
                            </form>
                        <?php endif ?>
                    <?php elseif ($selectedQuote['estado'] === 'rechazada'): ?>
                        <div class="client-response-state"><span>×</span><div><b>Cotización rechazada</b><p>La propuesta quedó cerrada. Puedes iniciar una nueva solicitud cuando lo desees.</p></div></div>
                    <?php elseif ($quoteExpired && $selectedQuote['estado'] === 'enviada'): ?>
                        <div class="client-response-state"><span>⌛</span><div><b>Propuesta vencida</b><p>Contacta al estudio para solicitar una actualización antes de responder.</p></div></div>
                    <?php elseif (!$proposalVisible): ?>
                        <div class="client-response-state"><span>◷</span><div><b>Propuesta en preparación</b><p>Podrás consultar el valor y responder cuando el estudio termine la propuesta.</p></div></div>
                    <?php endif ?>
                </div>
            </div>
            <?php if (($selectedQuote['citas'] ?? []) !== []): ?>
                <div class="client-quote-appointments">
                    <p class="client-quote-label">COORDINACIÓN DE LA CITA</p>
                    <div>
                        <?php foreach ($selectedQuote['citas'] as $appointment): ?>
                            <?php $appointmentStatus = (string) $appointment['estado']; ?>
                            <article class="client-appointment-card client-appointment-card--<?= e($appointmentStatus) ?>">
                                <div class="client-appointment-card__date">
                                    <small>SESIÓN <?= e($appointment['numero_sesion']) ?> DE <?= e($selectedQuote['sesiones_estimadas'] ?: $appointment['numero_sesion']) ?></small>
                                    <b><?= e(clientAppointmentLocalDate($appointment['fecha_hora_inicio'], 'd')) ?></b>
                                    <span><?= e(strtoupper(clientAppointmentLocalDate($appointment['fecha_hora_inicio'], 'M Y'))) ?></span>
                                </div>
                                <div class="client-appointment-card__body">
                                    <span class="client-status client-status--appointment-<?= e($appointmentStatus) ?>"><?= e($appointmentStatusLabels[$appointmentStatus] ?? $appointmentStatus) ?></span>
                                    <h3><?= e(clientAppointmentLocalDate($appointment['fecha_hora_inicio'], 'H:i')) ?> – <?= e(clientAppointmentLocalDate($appointment['fecha_hora_fin'], 'H:i')) ?></h3>
                                    <p>Con <?= e($appointment['artista']) ?></p>
                                    <?php if ($appointment['observaciones']): ?><small><?= nl2br(e($appointment['observaciones'])) ?></small><?php endif ?>
                                    <?php if ($appointmentStatus === 'pendiente'): ?>
                                        <div class="client-appointment-card__notice">Esta hora es una propuesta del estudio. Todavía no se ha enviado ningún correo.</div>
                                    <?php elseif ($appointmentStatus === 'confirmada'): ?>
                                        <div class="client-appointment-card__notice client-appointment-card__notice--confirmed">Esta es tu cita definitiva. Revisa el correo asociado a tu cuenta para ver la confirmación.</div>
                                    <?php endif ?>
                                </div>
                            </article>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endif ?>
            <?php if (($selectedQuote['historial'] ?? []) !== []): ?>
                <div class="client-quote-history">
                    <p class="client-quote-label">ACTIVIDAD DE LA PROPUESTA</p>
                    <div><?php foreach ($selectedQuote['historial'] as $historyItem): ?><article><span></span><div><b><?= e($historyItem['responsable'] === 'cliente' ? 'Tu respuesta' : 'Actualización del estudio') ?></b><small><?= e(clientQuoteLocalDate($historyItem['fecha'])) ?></small><p><?= e($statusLabels[$historyItem['estado_anterior']] ?? $historyItem['estado_anterior'] ?? 'Inicio') ?> → <?= e($statusLabels[$historyItem['estado_nuevo']] ?? $historyItem['estado_nuevo']) ?></p><?php if ($historyItem['mensaje_cliente']): ?><blockquote><?= nl2br(e($historyItem['mensaje_cliente'])) ?></blockquote><?php endif ?></div></article><?php endforeach ?></div>
                </div>
            <?php endif ?>
            <?php if ($selectedQuote['referencias'] !== []): ?><div class="client-references"><p class="client-quote-label">TUS REFERENCIAS</p><div><?php foreach ($selectedQuote['referencias'] as $reference): ?><?php $referenceUrl = clientQuoteImageUrl($reference['imagen_url']); ?><?php if ($referenceUrl !== null): ?><a href="<?= e($referenceUrl) ?>" target="_blank" rel="noopener"><img src="<?= e($referenceUrl) ?>" alt="<?= e($reference['texto_alternativo'] ?: 'Referencia de la cotización') ?>"></a><?php endif ?><?php endforeach ?></div></div><?php endif ?>
        </section>
    <?php endif ?>

    <section class="client-quote-archive">
        <div class="client-quote-archive__heading"><div><p class="admin-eyebrow">CRONOLOGÍA</p><h2>Ideas entregadas</h2></div><b><?= e(count($quotes)) ?></b></div>
        <?php if (!$profileMissing && $quotes === []): ?><div class="client-empty"><span>†</span><h3>El archivo aún está vacío</h3><p>Tu primera marca puede comenzar aquí.</p><a class="admin-button admin-button--primary" href="../cotizaciones/">Crear solicitud</a></div><?php else: ?><div class="client-quote-list"><?php foreach ($quotes as $index => $item): ?><?php $itemProposalVisible = $item['precio_cotizado'] !== null && in_array($item['estado'], ['enviada', 'aceptada', 'rechazada', 'vencida', 'cancelada'], true); ?><article><div class="client-quote-list__index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></div><div><p><?= e($item['categoria']) ?> · <?= e($item['zona_cuerpo']) ?></p><h3>Solicitud #<?= e($item['id_cotizacion']) ?></h3><blockquote><?= e($item['descripcion_idea']) ?></blockquote></div><div class="client-quote-list__response"><span class="client-status client-status--<?= e($item['estado']) ?>"><?= e($statusLabels[$item['estado']] ?? $item['estado']) ?></span><b><?= $itemProposalVisible ? '₡' . e(number_format((float) $item['precio_cotizado'], 0, ',', '.')) : 'Valor pendiente' ?></b><small><?= e(clientQuoteLocalDate($item['fecha_solicitud'])) ?></small></div><a class="admin-button admin-button--ghost" href="mis-cotizaciones.php?id=<?= e($item['id_cotizacion']) ?>">Ver detalle</a></article><?php endforeach ?></div><?php endif ?>
    </section>
</main>
</body>
</html>
