<?php
function overviewStars(float $score): string {
    $percent=max(0,min(100,$score*20));
    return '<span class="artist-score-stars" role="img" aria-label="'.e(number_format($score,2)).' de 5 estrellas"><span aria-hidden="true">★★★★★</span><span aria-hidden="true" style="width:'.$percent.'%">★★★★★</span></span>';
}
?>
<div class="artist-rating-metrics">
    <div><span aria-hidden="true">♙</span><p><strong><?= e($ratingsSummary['artistas']) ?></strong> Artistas</p></div>
    <div><span aria-hidden="true">☆</span><p><strong><?= e($ratingsSummary['resenas']) ?></strong> Reseñas publicadas</p></div>
    <div><span aria-hidden="true">★</span><p><strong><?= $ratingsSummary['promedio']===null?'—':e(number_format((float)$ratingsSummary['promedio'],1)).' / 5' ?></strong> Promedio general</p></div>
</div>
<?php if (!$record || $mode!=='view'): ?>
<form method="get" class="artist-rating-filters">
    <input type="hidden" name="module" value="resumen_calificaciones">
    <label>Buscar artista<input name="q" value="<?= e($search) ?>" placeholder="Nombre del artista"></label>
    <label>Ordenar por<select name="orden"><option value="mejor" <?= $ratingsSort==='mejor'?'selected':'' ?>>Mejor calificados</option><option value="resenas" <?= $ratingsSort==='resenas'?'selected':'' ?>>Más reseñas</option><option value="nombre" <?= $ratingsSort==='nombre'?'selected':'' ?>>Nombre</option></select></label>
    <button type="submit">Buscar</button><a href="administrador.php?module=resumen_calificaciones">Limpiar</a>
</form>
<?php else: ?><p><a href="administrador.php?module=resumen_calificaciones">← Volver a los artistas</a></p><?php endif ?>
<div class="artist-rating-grid">
<?php foreach ($list['rows'] as $artist):
    $photo=trim(str_replace('\\','/',(string)($artist['foto_url']??'')));
    if ($photo!=='' && (str_starts_with($photo,'//') || preg_match('~^(?!https?://)[a-z][a-z0-9+.-]*:~i',$photo))) $photo='';
    // Usa el retrato incluido del mismo artista si no tiene foto asignada.
    if ($photo==='') {
        $slug=(string)($artist['slug']??'');
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)) {
            $portrait='assets/images/artistas/'.$slug.'.png';
            if (is_file(__DIR__.'/../../public/'.$portrait)) $photo=$portrait;
        }
    }
    $count=(int)$artist['cantidad_calificaciones'];
?>
    <article class="artist-rating-card">
        <div class="artist-rating-portrait">
            <span class="artist-rating-placeholder" aria-hidden="true">♙</span>
            <?php if ($photo!==''): ?><img src="<?= e(imageUrl($photo)) ?>" alt="Retrato de <?= e($artist['nombre_publico']) ?>" loading="lazy"><?php endif ?>
        </div>
        <h2><?= e($artist['nombre_publico']) ?></h2>
        <?= overviewStars($count?(float)$artist['promedio']:0) ?>
        <p class="artist-rating-score"><?= $count?e(number_format((float)$artist['promedio'],1)).' / 5':'Sin calificaciones' ?></p>
        <p class="artist-rating-count"><?= $count?e($count).' '.($count===1?'reseña publicada':'reseñas publicadas'):'Aún no tiene reseñas' ?></p>
        <a class="button" href="administrador.php?<?= e(http_build_query(array_replace($_GET,['module'=>'resumen_calificaciones','artista'=>$artist['id_artista'],'resenas_page'=>1,'mode'=>'list']))) ?>">Ver detalle y comentarios</a>
    </article>
<?php endforeach ?>
</div>
<?php if (!$list['rows']): ?><p class="notice">No hay artistas con estos filtros.</p><?php endif ?>
<?php renderPagination($list['total'],$list['page'],array_diff_key($_GET,array_flip(['artista','resenas_page','estado_resena']))); ?>
<?php if ($selectedArtist):
    $closeParams=array_diff_key($_GET,array_flip(['artista','resenas_page','estado_resena','mode','id_artista']));
    $closeParams['module']='resumen_calificaciones';
    $reviewStates=['pendiente'=>'Pendiente','publicado'=>'Publicada','rechazado'=>'Oculta'];
?>
<dialog class="artist-rating-dialog" id="artist-rating-detail" aria-labelledby="artist-detail-title" open>
    <header class="artist-detail-heading"><div><p class="eyebrow">DETALLE DEL ARTISTA</p><h2 id="artist-detail-title"><?= e($selectedArtist['nombre_publico']) ?></h2></div><a class="button" href="?<?= e(http_build_query($closeParams)) ?>" aria-label="Cerrar detalle del artista">Cerrar ×</a></header>
    <div class="artist-rating-metrics">
        <div><p><strong><?= $artistStats['promedio']===null?'—':e(number_format((float)$artistStats['promedio'],2)).' / 5' ?></strong>Promedio publicado</p></div>
        <div><p><strong><?= e($artistStats['total']) ?></strong>Calificaciones totales</p></div>
        <div><p><strong><?= (int)$artistStats['pendientes'] ?></strong>Pendientes de revisión</p></div>
    </div>
    <p><?= (int)$artistStats['publicadas'] ?> publicadas · <?= (int)$artistStats['ocultas'] ?> ocultas. El promedio y la distribución incluyen únicamente calificaciones publicadas.</p>
    <section class="artist-distribution" aria-label="Distribución de estrellas publicadas">
        <?php for ($stars=5;$stars>=1;$stars--): $votes=(int)($artistDistribution[$stars]??0);$percentage=(int)$artistStats['publicadas']>0?round($votes*100/(int)$artistStats['publicadas']):0; ?>
        <div><span><?= $stars ?> ★</span><meter min="0" max="100" value="<?= $percentage ?>" aria-label="<?= $stars ?> estrellas: <?= $votes ?> calificaciones"><?= $percentage ?>%</meter><span><?= $votes ?> (<?= $percentage ?>%)</span></div>
        <?php endfor ?>
    </section>
    <form method="get" class="artist-rating-filters">
        <?php foreach (array_diff_key($_GET,array_flip(['estado_resena','resenas_page'])) as $key=>$value): if (!is_string($value)) continue; ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach ?>
        <input type="hidden" name="module" value="resumen_calificaciones"><input type="hidden" name="artista" value="<?= e($selectedArtistId) ?>">
        <label>Comentarios<select name="estado_resena"><option value="">Todos los estados</option><?php foreach ($reviewStates as $key=>$title): ?><option value="<?= e($key) ?>" <?= $reviewState===$key?'selected':'' ?>><?= e($title) ?></option><?php endforeach ?></select></label><button>Filtrar</button>
    </form>
    <section class="artist-rating-reviews"><h3>Comentarios y calificaciones · <?= $reviewTotal ?></h3>
    <?php foreach ($artistReviews as $review): ?>
        <article><header><strong><?= e($review['cliente']?:'Cliente') ?></strong><span><?= e($reviewStates[$review['estado_publicacion']]) ?></span></header>
            <?= overviewStars((float)$review['puntuacion']) ?> <span><?= e($review['puntuacion']) ?>/5</span>
            <p><?= nl2br(e(trim((string)$review['comentario'])!==''?$review['comentario']:'Sin comentario.')) ?></p>
            <footer><span><?= e((new DateTimeImmutable($review['fecha'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Costa_Rica'))->format('d/m/Y H:i')) ?> · Costa Rica</span><a class="button" href="?<?= e(http_build_query(['module'=>'calificaciones','mode'=>'edit','id_calificacion'=>$review['id_calificacion']])) ?>">Moderar</a><a href="?<?= e(http_build_query(['module'=>'calificaciones','mode'=>'view','id_calificacion'=>$review['id_calificacion']])) ?>">Historial</a></footer>
        </article>
    <?php endforeach ?>
    <?php if (!$artistReviews): ?><p>No hay calificaciones con este estado.</p><?php endif ?>
    <?php renderPagination($reviewTotal,$reviewPage,array_replace($_GET,['module'=>'resumen_calificaciones','artista'=>$selectedArtistId]),'resenas_page'); ?>
    </section>
</dialog>
<script src="../assets/js/artistas-calificaciones.js" defer></script>
<?php endif ?>
