<?php
declare(strict_types=1);

$statusLabels = [
    'solicitada' => 'Nueva',
    'en_revision' => 'En revisión',
    'enviada' => 'Enviada',
    'aceptada' => 'Aceptada',
    'rechazada' => 'Rechazada',
    'vencida' => 'Vencida',
    'cancelada' => 'Cancelada',
];

$appointmentStatusLabels = [
    'pendiente' => 'Pendiente',
    'confirmada' => 'Confirmada',
    'en_proceso' => 'En proceso',
    'finalizada' => 'Finalizada',
    'cancelada' => 'Cancelada',
    'no_asistio' => 'No asistió',
];

$weekDayLabels = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo',
];

function adminQuoteLocalDate(?string $value, string $format = 'd/m/Y H:i'): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('America/Costa_Rica'))
        ->format($format);
}

function adminQuoteDurationHours(mixed $minutes): string
{
    if ($minutes === null || (int) $minutes <= 0) {
        return '';
    }

    return rtrim(rtrim(number_format((int) $minutes / 60, 2, '.', ''), '0'), '.');
}

function adminAppointmentLocalDate(?string $value, string $format = 'd/m/Y H:i'): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return (new DateTimeImmutable($value, new DateTimeZone('America/Costa_Rica')))->format($format);
}

function adminScheduleTime(string $value): string
{
    return substr($value, 0, 5);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080506">
    <title>Cotizaciones · Administración · Tinta Viva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:wght@500;600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-artistas.css">
    <link rel="stylesheet" href="../assets/css/admin-cotizaciones.css">
    <link rel="stylesheet" href="../assets/css/admin-navegacion.css">
<?php responsiveAssets(); ?>
</head>

<body class="admin-artists admin-quotes">
<?php require __DIR__.'/../admin-navegacion.php'; ?>

<main class="admin-main">
    <header class="admin-heading quote-admin-heading">
        <div><p class="admin-eyebrow">PACTOS PENDIENTES</p><h1>Cotizaciones</h1><p>Convierte cada idea en una respuesta clara: artista, precio, sesiones, anticipo y vigencia.</p></div>
        <a class="admin-button admin-button--ghost" href="../cotizaciones/" target="_blank" rel="noopener">Abrir formulario ↗</a>
    </header>

    <?php if ($notice !== null): ?><div class="admin-notice" role="status"><span>✓</span><?= e($notice) ?></div><?php endif ?>
    <?php if ($warning !== null): ?><div class="admin-notice admin-notice--warning" role="alert"><span>!</span><?= e($warning) ?></div><?php endif ?>
    <?php if ($error !== null): ?><div class="admin-notice admin-notice--error" role="alert"><span>!</span><?= e($error) ?></div><?php endif ?>

    <section class="quote-admin-stats" aria-label="Resumen de cotizaciones">
        <article><span>I</span><p><b><?= e($summary['nuevas'] ?? 0) ?></b>Nuevas</p></article>
        <article><span>II</span><p><b><?= e($summary['en_revision'] ?? 0) ?></b>En revisión</p></article>
        <article><span>III</span><p><b><?= e($summary['enviadas'] ?? 0) ?></b>Enviadas</p></article>
        <article><span>IV</span><p><b><?= e($summary['aceptadas'] ?? 0) ?></b>Por agendar</p></article>
        <article><span>V</span><p><b><?= e($summary['agendadas'] ?? 0) ?></b>Agendadas</p></article>
        <article><span>VI</span><p><b><?= e($summary['cerradas'] ?? 0) ?></b>Cerradas</p></article>
    </section>

    <?php if ($mode === 'view' && $quote !== null): ?>
        <section class="quote-dossier">
            <header class="quote-dossier__header">
                <div><p class="admin-eyebrow">EXPEDIENTE #<?= e($quote['id_cotizacion']) ?></p><h2><?= e($quote['nombre_contacto']) ?></h2><span class="quote-status quote-status--<?= e($quote['estado']) ?>"><?= e($statusLabels[$quote['estado']] ?? $quote['estado']) ?></span></div>
                <a href="cotizaciones.php">Cerrar expediente ×</a>
            </header>

            <div class="quote-dossier__grid">
                <div class="quote-dossier__story">
                    <p class="quote-dossier__label">LA VISIÓN DEL CLIENTE</p>
                    <blockquote><?= nl2br(e($quote['descripcion_idea'])) ?></blockquote>
                    <dl>
                        <div><dt>Estilo</dt><dd><?= e($quote['categoria']) ?></dd></div>
                        <div><dt>Zona</dt><dd><?= e($quote['zona_cuerpo']) ?></dd></div>
                        <div><dt>Medidas</dt><dd><?= $quote['ancho_cm'] !== null ? e($quote['ancho_cm']) . ' × ' . e($quote['alto_cm']) . ' cm' : e($quote['tamano_descripcion'] ?: 'Por definir') ?></dd></div>
                        <div><dt>Color</dt><dd><?= !empty($quote['a_color']) ? 'Sí' : 'No' ?></dd></div>
                        <div><dt>Fecha preferida</dt><dd><?= $quote['fecha_preferida'] ? e(date('d/m/Y', strtotime((string) $quote['fecha_preferida']))) . ' · aún no reservada' : 'Sin definir' ?></dd></div>
                        <div><dt>Solicitada</dt><dd><?= e(adminQuoteLocalDate($quote['fecha_solicitud'])) ?> <small>(hora de Costa Rica)</small></dd></div>
                    </dl>

                    <div class="quote-contact-card">
                        <p class="quote-dossier__label">CORREO DE LA CUENTA</p>
                        <a href="mailto:<?= e($quote['correo_contacto']) ?>"><?= e($quote['correo_contacto']) ?></a>
                        <span>Teléfono opcional: <?= e($quote['telefono_contacto'] ?: 'No indicado') ?></span>
                        <?php if ($quote['cliente_registrado']): ?><small>Cliente registrado: <?= e($quote['cliente_registrado']) ?></small><?php endif ?>
                    </div>
                </div>

                <?php $quoteEditable = in_array($quote['estado'], ['solicitada', 'en_revision'], true); ?>
                <form method="post" class="quote-response-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id_cotizacion" value="<?= e($quote['id_cotizacion']) ?>">
                    <p class="quote-dossier__label">RESPUESTA DEL ESTUDIO</p>
                    <p class="quote-flow-hint"><?php if ($quoteEditable): ?>Al guardar, el sistema cambiará automáticamente la solicitud a <strong>Enviada</strong>.<?php else: ?>Esta propuesta está <strong><?= e(strtolower($statusLabels[$quote['estado']] ?? $quote['estado'])) ?></strong> y sus condiciones quedaron bloqueadas.<?php endif ?></p>
                    <fieldset class="quote-response-fields" <?= $quoteEditable ? '' : 'disabled' ?>>
                        <?php if ($quote['id_artista'] !== null): ?>
                            <label><span>Artista elegido por el cliente</span><input value="<?= e($quote['artista']) ?>" readonly><input type="hidden" name="id_artista" value="<?= e($quote['id_artista']) ?>"><small>La elección del cliente se conserva y no puede cambiarse desde la cotización.</small></label>
                        <?php else: ?>
                            <label><span>Asignar artista disponible *</span><select name="id_artista" required><option value="" selected disabled>Selecciona un artista disponible</option><?php foreach ($artists as $adminArtist): ?><option value="<?= e($adminArtist['id_artista']) ?>"><?= e($adminArtist['nombre_publico']) ?></option><?php endforeach ?></select><small>Solo aparecen artistas con espacio en la fecha solicitada.</small></label>
                        <?php endif ?>
                        <label><span>Precio cotizado (CRC)</span><input type="number" name="precio_cotizado" min="0" step="0.01" value="<?= e($quote['precio_cotizado'] ?? '') ?>" placeholder="95000"></label>
                        <label><span>Anticipo requerido (CRC)</span><input type="number" name="anticipo_requerido" min="0" step="0.01" value="<?= e($quote['anticipo_requerido'] ?? 0) ?>"></label>
                        <label><span>Duración estimada (horas)</span><input type="number" name="duracion_estimada_horas" min="0.01" max="168" step="0.01" value="<?= e(adminQuoteDurationHours($quote['duracion_estimada_minutos'] ?? null)) ?>" placeholder="Ej. 2.5"></label>
                        <label><span>Sesiones estimadas</span><input type="number" name="sesiones_estimadas" min="1" max="30" value="<?= e($quote['sesiones_estimadas'] ?? '') ?>"></label>
                        <label><span>Fecha límite para aceptar</span><input value="<?= e($automaticExpiration ? $automaticExpiration->setTimezone(new DateTimeZone('America/Costa_Rica'))->format('d/m/Y H:i') : 'Se requiere una nueva fecha preferida') ?>" readonly><small><?= e($validityIssue ?: 'Se usa la fecha más cercana: siete días después del envío o dos días antes de la fecha preferida.') ?></small></label>
                        <label class="quote-response-fields__wide"><span>Mensaje visible para el cliente</span><textarea name="observaciones" rows="6" maxlength="5000" placeholder="Condiciones, aclaraciones o próximos pasos para el cliente..."><?= e($quote['observaciones'] ?? '') ?></textarea></label>
                    </fieldset>
                    <div class="quote-response-form__actions"><?php if ($quoteEditable): ?><button class="admin-button admin-button--primary" type="submit">Enviar propuesta al cliente</button><?php endif ?><a href="cotizaciones.php">Volver al archivo</a></div>
                </form>
            </div>

            <div class="quote-history">
                <div class="quote-history__heading"><p class="quote-dossier__label">HISTORIAL DE LA PROPUESTA</p><span><?= e(count($quote['historial'] ?? [])) ?> movimientos</span></div>
                <?php if (($quote['historial'] ?? []) === []): ?>
                    <p class="quote-history__empty">Los cambios y las respuestas del cliente aparecerán aquí.</p>
                <?php else: ?>
                    <div class="quote-history__list">
                        <?php foreach ($quote['historial'] as $historyItem): ?>
                            <?php
                            $previousStatus = $historyItem['estado_anterior'] !== null
                                ? ($statusLabels[$historyItem['estado_anterior']] ?? $historyItem['estado_anterior'])
                                : 'Inicio';
                            $newStatus = $statusLabels[$historyItem['estado_nuevo']] ?? $historyItem['estado_nuevo'];
                            $responsible = $historyItem['responsable_rol'] === 'cliente'
                                ? 'Cliente'
                                : ($historyItem['responsable'] ?: 'Estudio');
                            ?>
                            <article class="quote-history__item <?= $historyItem['responsable_rol'] === 'cliente' ? 'quote-history__item--client' : '' ?>">
                                <span></span>
                                <div><b><?= e($responsible) ?></b><small><?= e(adminQuoteLocalDate($historyItem['fecha'])) ?></small></div>
                                <p><?= e($previousStatus) ?> → <?= e($newStatus) ?></p>
                                <?php if ($historyItem['observacion']): ?><blockquote><?= nl2br(e($historyItem['observacion'])) ?></blockquote><?php endif ?>
                            </article>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>

            <?php if ($quote['estado'] === 'aceptada' || $appointments !== []): ?>
                <?php
                $activeSessionNumbers = [];
                foreach ($appointments as $appointmentItem) {
                    if (!in_array($appointmentItem['estado'], ['cancelada', 'no_asistio'], true)) {
                        $activeSessionNumbers[] = (int) $appointmentItem['numero_sesion'];
                    }
                }
                $estimatedSessions = max(1, (int) ($quote['sesiones_estimadas'] ?? 1));
                $missingSessions = array_values(array_diff(range(1, $estimatedSessions), $activeSessionNumbers));
                $hasAllSessionPreferences = count($quote['preferencias_sesiones'] ?? []) >= $estimatedSessions;
                $canAddSession = $quote['estado'] === 'aceptada'
                    && $quote['id_cliente'] !== null
                    && $quote['id_artista'] !== null
                    && $hasAllSessionPreferences
                    && $missingSessions !== [];
                $minimumAppointmentStart = (new DateTimeImmutable('now', new DateTimeZone('America/Costa_Rica')))
                    ->format('Y-m-d\TH:i');
                ?>
                <div class="quote-appointments">
                    <div class="quote-appointments__heading"><div><p class="quote-dossier__label">AGENDA DE LA COTIZACIÓN</p><h3>Citas y confirmaciones</h3></div><span><?= e(count($appointments)) ?> registradas</span></div>
                    <p class="quote-flow-hint">Crear una cita la deja <strong>Pendiente</strong> y no envía correo. El correo real se genera únicamente al presionar <strong>Confirmar cita y enviar correo</strong>.</p>

                    <?php if ($quote['id_artista'] !== null): ?>
                        <section class="quote-artist-availability" data-appointment-planner>
                            <script type="application/json" data-artist-schedule><?= json_encode($artistSchedule, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
                            <script type="application/json" data-artist-unavailable><?= json_encode($artistUnavailablePeriods, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
                            <script type="application/json" data-available-slots><?= json_encode($availableAppointmentSlots, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
                            <div class="quote-artist-availability__heading">
                                <div><small>HORARIO ACTIVO DEL ARTISTA</small><h4><?= e($quote['artista']) ?></h4></div>
                                <span>Hora de Costa Rica</span>
                            </div>
                            <?php if ($artistSchedule === []): ?>
                                <p class="quote-availability-empty">Este artista no tiene un horario activo registrado. Agrega su disponibilidad antes de crear la cita.</p>
                            <?php else: ?>
                                <div class="quote-schedule-list">
                                    <?php foreach ($artistSchedule as $scheduleItem): ?>
                                        <article><b><?= e($weekDayLabels[(int) $scheduleItem['dia_semana']] ?? 'Día') ?></b><span><?= e(adminScheduleTime($scheduleItem['hora_inicio'])) ?>–<?= e(adminScheduleTime($scheduleItem['hora_fin'])) ?></span></article>
                                    <?php endforeach ?>
                                </div>
                                <p class="quote-availability-note">El espacio debe comenzar y terminar dentro de uno de estos intervalos. Los bloqueos y otras citas también se comprueban automáticamente.</p>
                            <?php endif ?>
                            <div class="quote-slot-feedback" data-slot-feedback aria-live="polite">
                                <span>◷</span><div><b>Selecciona el inicio y la duración</b><p>Aquí verás la hora de finalización y si el espacio está disponible.</p></div>
                            </div>
                        </section>
                    <?php endif ?>

                    <?php if ($appointments !== []): ?>
                        <div class="quote-appointment-list">
                            <?php foreach ($appointments as $appointmentItem): ?>
                                <article class="quote-appointment-card quote-appointment-card--<?= e($appointmentItem['estado']) ?>">
                                    <div class="quote-appointment-card__date"><b><?= e(adminAppointmentLocalDate($appointmentItem['fecha_hora_inicio'], 'd')) ?></b><span><?= e(strtoupper(adminAppointmentLocalDate($appointmentItem['fecha_hora_inicio'], 'M'))) ?></span></div>
                                    <div class="quote-appointment-card__details"><small>SESIÓN <?= e($appointmentItem['numero_sesion']) ?> · <?= e($appointmentItem['artista']) ?></small><h4><?= e(adminAppointmentLocalDate($appointmentItem['fecha_hora_inicio'], 'H:i')) ?>–<?= e(adminAppointmentLocalDate($appointmentItem['fecha_hora_fin'], 'H:i')) ?> · hora de Costa Rica</h4><p><?= e($appointmentItem['observaciones'] ?: 'Sin indicaciones adicionales.') ?></p></div>
                                    <div class="quote-appointment-card__status"><span><?= e($appointmentStatusLabels[$appointmentItem['estado']] ?? $appointmentItem['estado']) ?></span><?php if ($appointmentItem['estado'] === 'confirmada'): ?><small>Correo: <?= e($appointmentItem['estado_correo'] ?: 'sin registro') ?></small><?php endif ?></div>
                                    <div class="quote-appointment-card__actions">
                                        <?php if ($appointmentItem['estado'] === 'pendiente'): ?>
                                            <form method="post" data-confirm="La cita quedará definitiva y se intentará enviar el correo real al cliente. ¿Deseas confirmarla?"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="confirm_appointment"><input type="hidden" name="id_cotizacion" value="<?= e($quote['id_cotizacion']) ?>"><input type="hidden" name="id_cita" value="<?= e($appointmentItem['id_cita']) ?>"><button class="admin-button admin-button--success" type="submit">Confirmar cita y enviar correo</button></form>
                                            <form method="post" data-confirm="¿Deseas cancelar esta propuesta de cita? No se enviará correo."><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="cancel_appointment"><input type="hidden" name="id_cotizacion" value="<?= e($quote['id_cotizacion']) ?>"><input type="hidden" name="id_cita" value="<?= e($appointmentItem['id_cita']) ?>"><button class="admin-button admin-button--danger" type="submit">Cancelar pendiente</button></form>
                                        <?php elseif ($appointmentItem['estado'] === 'confirmada' && $appointmentItem['estado_correo'] === 'fallido'): ?>
                                            <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="retry_appointment_email"><input type="hidden" name="id_cotizacion" value="<?= e($quote['id_cotizacion']) ?>"><input type="hidden" name="id_cita" value="<?= e($appointmentItem['id_cita']) ?>"><button class="admin-button admin-button--primary" type="submit">Reintentar correo</button></form>
                                            <small><?= e($appointmentItem['error_correo'] ?: 'El envío anterior falló.') ?></small>
                                        <?php elseif ($appointmentItem['estado'] === 'confirmada' && $appointmentItem['estado_correo'] === 'enviado'): ?>
                                            <small>Enviado <?= e(adminQuoteLocalDate($appointmentItem['fecha_envio'])) ?></small>
                                        <?php endif ?>
                                    </div>
                                </article>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>

                    <?php if ($canAddSession): ?>
                        <form method="post" class="quote-appointment-form" data-appointment-form>
                            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                            <input type="hidden" name="action" value="schedule_appointment">
                            <input type="hidden" name="id_cotizacion" value="<?= e($quote['id_cotizacion']) ?>">
                            <div><p class="quote-dossier__label">PLAN DE SESIONES</p><h3>Agenda <?= e(count($missingSessions)) ?> sesión(es) pendiente(s)</h3><p>Se guardan juntas: si una hora no está disponible, ninguna se registra.</p></div>
                            <div class="quote-session-planner">
                                <?php foreach ($missingSessions as $sessionNumber): ?>
                                    <?php $preferredSessionDate = $quote['preferencias_sesiones'][$sessionNumber] ?? ''; ?>
                                    <?php $selectedSessionDate = $appointmentForm['fecha_sesion'][$sessionNumber] ?? $preferredSessionDate; ?>
                                    <fieldset class="quote-session-row" data-appointment-row>
                                        <legend>Sesión <?= e($sessionNumber) ?> de <?= e($estimatedSessions) ?></legend>
                                        <div class="quote-response-fields">
                                            <label><span>Fecha disponible *</span><select name="fecha_sesion[<?= e($sessionNumber) ?>]" data-session-date required><option value="">Selecciona una fecha</option><?php foreach (array_keys($availableAppointmentSlots) as $availableDate): ?><option value="<?= e($availableDate) ?>" <?= $selectedSessionDate === $availableDate ? 'selected' : '' ?>><?= e((new DateTimeImmutable($availableDate))->format('d/m/Y')) ?><?= $preferredSessionDate === $availableDate ? ' · preferida por el cliente' : '' ?></option><?php endforeach ?></select></label>
                                            <label><span>Hora disponible *</span><select name="hora_sesion[<?= e($sessionNumber) ?>]" data-session-time data-selected-time="<?= e($appointmentForm['hora_sesion'][$sessionNumber] ?? '') ?>" required><option value="">Selecciona primero la fecha</option></select></label>
                                            <label><span>Duración cotizada (horas)</span><input type="number" name="duracion_horas[<?= e($sessionNumber) ?>]" value="<?= e(adminQuoteDurationHours($quote['duracion_estimada_minutos'] ?? null) ?: '2') ?>" data-appointment-duration readonly required></label>
                                            <label class="quote-response-fields__wide"><span>Indicaciones visibles para el cliente</span><textarea name="observaciones_cita[<?= e($sessionNumber) ?>]" rows="3" maxlength="2000" placeholder="Preparación o indicaciones para esta sesión..."><?= e($appointmentForm['observaciones_cita'][$sessionNumber] ?? '') ?></textarea></label>
                                        </div>
                                        <div class="quote-slot-feedback" data-row-feedback aria-live="polite"><span>◷</span><div><b>Selecciona fecha y hora</b><p>Validaremos horario, bloqueos y citas existentes.</p></div></div>
                                    </fieldset>
                                <?php endforeach ?>
                            </div>
                            <button class="admin-button admin-button--primary" type="submit" data-appointment-submit <?= $artistSchedule === [] || $availableAppointmentSlots === [] ? 'disabled' : '' ?>>Crear todas las citas pendientes</button>
                        </form>
                    <?php elseif ($quote['estado'] === 'aceptada' && ($quote['id_cliente'] === null || $quote['id_artista'] === null)): ?>
                        <p class="quote-history__empty">Asigna un artista y asegúrate de que la cotización pertenezca a un cliente registrado antes de agendar.</p>
                    <?php elseif ($quote['estado'] === 'aceptada' && !$hasAllSessionPreferences): ?>
                        <p class="quote-history__empty">El cliente todavía debe elegir una fecha preferida para cada sesión. La agenda se habilitará automáticamente cuando las complete.</p>
                    <?php elseif ($quote['estado'] === 'aceptada' && $estimatedSessions > 0): ?>
                        <p class="quote-history__empty">Ya se registraron todas las sesiones estimadas de esta cotización.</p>
                    <?php endif ?>
                </div>
            <?php endif ?>

            <div class="quote-references">
                <p class="quote-dossier__label">REFERENCIAS VISUALES · <?= e(count($quote['referencias'])) ?></p>
                <?php if ($quote['referencias'] === []): ?><p class="quote-references__empty">El cliente no adjuntó imágenes.</p><?php else: ?><div><?php foreach ($quote['referencias'] as $reference): ?><?php $referenceUrl = adminQuoteImageUrl($reference['imagen_url']); ?><?php if ($referenceUrl !== null): ?><a href="<?= e($referenceUrl) ?>" target="_blank" rel="noopener"><img src="<?= e($referenceUrl) ?>" alt="<?= e($reference['texto_alternativo'] ?: 'Referencia de la cotización') ?>"><span>Ampliar ↗</span></a><?php endif ?><?php endforeach ?></div><?php endif ?>
            </div>
        </section>
    <?php endif ?>

    <section class="artist-management quote-archive">
        <form class="artist-search quote-search" method="get">
            <label><span>Buscar solicitud</span><input name="buscar" value="<?= e($search) ?>" placeholder="Número, cliente, correo, idea o estilo..."></label>
            <label><span>Bandeja</span><select name="vista"><option value="active" <?= $archiveView === 'active' ? 'selected' : '' ?>>Activas y por atender</option><option value="scheduled" <?= $archiveView === 'scheduled' ? 'selected' : '' ?>>Agenda completa</option><option value="closed" <?= $archiveView === 'closed' ? 'selected' : '' ?>>Cerradas</option></select></label>
            <label><span>Estado específico</span><select name="estado"><option value="">Todos en esta bandeja</option><?php foreach ($statusLabels as $value => $caption): ?><option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($caption) ?></option><?php endforeach ?></select></label>
            <button class="admin-button admin-button--primary" type="submit">Filtrar</button>
            <?php if ($search !== '' || $status !== '' || $archiveView !== 'active'): ?><a href="cotizaciones.php">Limpiar</a><?php endif ?>
        </form>

        <div class="artist-management__summary"><p><strong><?= e(count($quotes)) ?></strong> <?= count($quotes) === 1 ? 'solicitud encontrada' : 'solicitudes encontradas' ?></p><p>Abre un expediente para preparar la respuesta.</p></div>

        <?php if ($quotes === []): ?>
            <div class="admin-empty"><span>†</span><h2>No hay pactos en este archivo</h2><p>Ajusta los filtros o espera una nueva solicitud.</p></div>
        <?php else: ?>
            <div class="quote-list">
                <?php foreach ($quotes as $item): ?>
                    <article class="quote-list-card">
                        <div class="quote-list-card__number"><small>EXPEDIENTE</small><b>#<?= e($item['id_cotizacion']) ?></b><span class="quote-status quote-status--<?= e($item['estado']) ?>"><?= e($statusLabels[$item['estado']] ?? $item['estado']) ?></span></div>
                        <div class="quote-list-card__idea"><p><?= e($item['categoria']) ?> · <?= e($item['zona_cuerpo']) ?></p><h2><?= e($item['nombre_contacto']) ?></h2><blockquote><?= e($item['descripcion_idea']) ?></blockquote></div>
                        <dl><div><dt>Artista</dt><dd><?= e($item['artista']) ?></dd></div><div><dt>Valor</dt><dd><?= $item['precio_cotizado'] !== null ? '₡' . e(number_format((float) $item['precio_cotizado'], 0, ',', '.')) : 'Por definir' ?></dd></div><div><dt>Sesiones</dt><dd><?= e($item['sesiones_agendadas']) ?>/<?= e($item['sesiones_estimadas'] ?: 1) ?></dd></div></dl>
                        <div class="quote-list-card__action"><time datetime="<?= e($item['fecha_solicitud']) ?>"><?= e(adminQuoteLocalDate($item['fecha_solicitud'], 'd M Y · H:i')) ?></time><a class="admin-button admin-button--ghost" href="cotizaciones.php?id=<?= e($item['id_cotizacion']) ?>">Abrir expediente</a></div>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
</main>
<script src="../assets/js/admin-cotizaciones.js" defer></script>
<script src="../assets/js/app.js" defer></script>
</body>
</html>
