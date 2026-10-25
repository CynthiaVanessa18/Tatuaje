<?php
$states = ['pendiente'=>'Pendiente', 'publicado'=>'Aprobada', 'rechazado'=>'Oculta'];
$decisions = ['pendiente'=>'pendiente', 'publicado'=>'aprobar', 'rechazado'=>'ocultar'];
$reasons = ['insultos'=>'Insultos', 'amenazas'=>'Amenazas', 'publicidad'=>'Publicidad', 'datos_personales'=>'Datos personales', 'contenido_ajeno'=>'Contenido ajeno al servicio'];
$decisionLabels = ['aprobar'=>'Aprobada', 'ocultar'=>'Oculta', 'pendiente'=>'Pendiente'];
$categoryLabels = $reasons + ['aprobacion'=>'Aprobación', 'revision'=>'Revisión'];
?>
<p class="hint">Aprueba las calificaciones u oculta contenido inapropiado y registra el motivo. Una opinión negativa sobre el servicio no es motivo para ocultarla.</p>
<?php if ($record && in_array($mode, ['edit', 'view'], true)): ?>
<?php
$ratingContextQuery=conectarBaseDatos()->prepare("SELECT a.id_artista,COALESCE(NULLIF(a.nombre_artistico,''),CONCAT_WS(' ',a.nombre,a.apellidos)) AS artista,CONCAT_WS(' ',cl.nombre,cl.apellidos) AS cliente FROM citas c JOIN artistas a ON a.id_artista=c.id_artista LEFT JOIN clientes cl ON cl.id_cliente=c.id_cliente WHERE c.id_cita=?");
$ratingContextQuery->execute([$record['id_cita']]);$ratingContext=$ratingContextQuery->fetch();
?>
<p><a href="administrador.php?module=resumen_calificaciones&amp;artista=<?= e($ratingContext['id_artista']??'') ?>">← Volver a los comentarios del artista</a></p>
<section class="card">
    <h2><?= $mode === 'edit' ? 'Moderar calificación' : 'Detalle e historial de la calificación' ?></h2>
    <dl>
        <dt>Artista</dt><dd><?= e($ratingContext['artista']??'Artista no disponible') ?></dd>
        <dt>Cliente</dt><dd><?= e($ratingContext['cliente']??'Cliente no disponible') ?></dd>
        <dt>Puntuación</dt><dd><span class="rating-display" role="img" aria-label="<?= e($record['puntuacion']) ?> de 5 estrellas"><span aria-hidden="true"><span class="rating-display-filled"><?= str_repeat('★', max(0, min(5, (int)$record['puntuacion']))) ?></span><span class="rating-display-empty"><?= str_repeat('☆', 5-max(0, min(5, (int)$record['puntuacion']))) ?></span></span></span> <span><?= e($record['puntuacion']) ?>/5</span></dd>
        <dt>Comentario</dt><dd><?= nl2br(e($record['comentario'] ?? 'Sin comentario.')) ?></dd>
        <dt>Estado actual</dt><dd><?= e($states[$record['estado_publicacion']]) ?></dd>
    </dl>
    <?php if ($mode === 'edit'):
        $retry = $_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['id_calificacion'] ?? '') === (string)$record['id_calificacion'];
        $selectedDecision = $retry && is_string($_POST['decision'] ?? null) ? $_POST['decision'] : $decisions[$record['estado_publicacion']];
        $selectedReason = $retry && is_string($_POST['categoria_motivo'] ?? null) ? $_POST['categoria_motivo'] : '';
        $reasonText = $retry && is_string($_POST['motivo'] ?? null) ? $_POST['motivo'] : '';
    ?>
    <form method="post" action="administrador.php?<?= e(http_build_query(['module'=>'calificaciones', 'mode'=>'edit', 'id_calificacion'=>$record['id_calificacion']])) ?>" class="record-form">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="action" value="moderate">
        <input type="hidden" name="id_calificacion" value="<?= e($record['id_calificacion']) ?>">
        <div class="form-grid">
            <label>Estado de publicación *
                <select name="decision" required>
                    <?php foreach ($states as $state=>$text): ?>
                    <option value="<?= e($decisions[$state]) ?>" <?= $selectedDecision === $decisions[$state] ? 'selected' : '' ?>><?= e($text) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label>Motivo para ocultar
                <select name="categoria_motivo">
                    <option value="">Selecciona si vas a ocultarla</option>
                    <?php foreach ($reasons as $value=>$text): ?>
                    <option value="<?= e($value) ?>" <?= $selectedReason === $value ? 'selected' : '' ?>><?= e($text) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label>Motivo de la decisión *<textarea name="motivo" rows="3" maxlength="1000" required placeholder="Explica el cambio de estado."><?= e($reasonText) ?></textarea></label>
        </div>
        <div class="actions"><button type="submit">Guardar moderación</button><a href="administrador.php?module=calificaciones">Cancelar</a></div>
    </form>
    <?php else: $historyList=$ratingModerator->historyListing((int)$record['id_calificacion'],max(1,(int)($_GET['history_page']??1))); $history=$historyList['rows']; ?>
    <div class="table-scroll"><table data-server-paginated>
        <thead><tr><th scope="col">Fecha · Costa Rica</th><th scope="col">Administrador</th><th scope="col">Estado</th><th scope="col">Categoría</th><th scope="col">Motivo</th></tr></thead>
        <tbody>
        <?php foreach ($history as $event):
            $detail = json_decode($event['detalle'], true) ?? [];
            $date = (new DateTimeImmutable($event['fecha'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Costa_Rica'));
        ?>
        <tr><td><?= e($date->format('d/m/Y H:i')) ?></td><td><?= e($event['usuario'] ?? 'Cuenta no disponible') ?></td><td><?= e($decisionLabels[$detail['decision'] ?? ''] ?? 'No disponible') ?></td><td><?= e($categoryLabels[$detail['categoria'] ?? ''] ?? '—') ?></td><td><?= nl2br(e($detail['motivo'] ?? '')) ?></td></tr>
        <?php endforeach ?>
        <?php if (!$history): ?><tr><td colspan="5" class="empty">Sin decisiones registradas.</td></tr><?php endif ?>
        </tbody>
    </table></div>
    <?php renderPagination($historyList['total'],$historyList['page'],$_GET,'history_page'); ?><a href="administrador.php?module=calificaciones">Volver</a>
    <?php endif ?>
</section>
<?php endif ?>
<?php if ($record && in_array($mode,['view','edit'],true)) return; ?>
<section class="card">
    <form method="get" action="administrador.php" class="filters">
        <input type="hidden" name="module" value="calificaciones">
        <label>Buscar<input name="q" value="<?= e($search) ?>" placeholder="Buscar en comentarios"></label>
        <label>Estado de publicación
            <select name="filter[estado_publicacion]">
                <option value="">Todos</option>
                <?php foreach ($states as $value=>$text): ?>
                <option value="<?= e($value) ?>" <?= ($filters['estado_publicacion'] ?? '') === $value ? 'selected' : '' ?>><?= e($text) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <button type="submit">Filtrar</button><a href="administrador.php?module=calificaciones">Limpiar</a>
    </form>
    <p><?= e($list['total']) ?> registros · Página <?= e($list['page']) ?></p>
    <div class="table-scroll"><table data-server-paginated>
        <thead><tr><th scope="col">Cita</th><th scope="col">Puntuación</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($list['rows'] as $rating): $query = http_build_query(['module'=>'calificaciones', 'id_calificacion'=>$rating['id_calificacion']]); ?>
        <tr>
            <td>Cita #<?= e($rating['id_cita']) ?></td>
            <td><span class="rating-display" role="img" aria-label="<?= e($rating['puntuacion']) ?> de 5 estrellas"><span aria-hidden="true"><span class="rating-display-filled"><?= str_repeat('★', max(0, min(5, (int)$rating['puntuacion']))) ?></span><span class="rating-display-empty"><?= str_repeat('☆', 5-max(0, min(5, (int)$rating['puntuacion']))) ?></span></span></span> <span><?= e($rating['puntuacion']) ?>/5</span></td><td><?= e($states[$rating['estado_publicacion']]) ?></td>
            <td><div class="actions"><a href="administrador.php?<?= e($query) ?>&amp;mode=view">Ver</a><a href="administrador.php?<?= e($query) ?>&amp;mode=edit">Moderar</a></div></td>
        </tr>
        <?php endforeach ?>
        <?php if (!$list['rows']): ?><tr><td colspan="4" class="empty">No hay calificaciones con estos filtros.</td></tr><?php endif ?>
        </tbody>
    </table></div>
    <?php renderPagination((int)$list['total'], (int)$list['page'], array_replace($_GET, ['module'=>$moduleId])); ?>
</section>
