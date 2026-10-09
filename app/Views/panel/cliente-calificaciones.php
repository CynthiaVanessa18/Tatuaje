<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi experiencia · Tinta Viva</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:wght@400;600&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="../assets/css/admin-artistas.css">
    <link rel="stylesheet" href="../assets/css/cliente-calificaciones.css">
    <link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>">
    <?php responsiveAssets(); ?>
</head>
<body class="client-shared-layout admin-artists">
<?php $clientEndpoint='../index.php';$publicPrefix='../';require __DIR__.'/../cliente-navegacion.php'; ?>
<main class="admin-main">
    <header class="admin-heading"><div><p class="admin-eyebrow">DESPUÉS DE TU SESIÓN</p><h1>Mi experiencia</h1><p>Cuando el estudio marque tu cita como finalizada, podrás calificar tu experiencia con el artista.</p></div><a class="admin-button admin-button--ghost" href="../index.php?section=citas">Mis citas</a></header>
    <?php if ($ratingNotice): ?><p role="status"><?= e($ratingNotice) ?></p><?php endif ?>
    <?php if ($ratingError): ?><p role="alert"><?= e($ratingError) ?></p><?php endif ?>
    <?php if (!$ratedAppointments): ?><p>Aún no tienes sesiones finalizadas para calificar.</p><?php endif ?>
    <div class="client-ratings">
    <?php foreach ($ratedAppointments as $item): ?>
        <article class="client-rating" id="cita-<?= e($item['id_cita']) ?>">
            <h2><?= e($item['artista']) ?></h2>
            <p>Cita #<?= e($item['id_cita']) ?> · <?= e((new DateTimeImmutable($item['fecha_hora_inicio']))->format('d/m/Y H:i')) ?></p>
            <?php if ($item['id_calificacion'] === null): ?>
                <p class="client-rating-status">Pendiente de calificar</p>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="id_cita" value="<?= e($item['id_cita']) ?>">
                    <fieldset><legend>¿Cómo fue tu experiencia con el artista?</legend><div class="client-rating-stars">
                        <?php for ($score=1;$score<=5;$score++): ?><label><input type="radio" name="puntuacion" value="<?= $score ?>" required><span><?= $score ?> ★</span></label><?php endfor ?>
                    </div></fieldset>
                    <label for="comentario-<?= e($item['id_cita']) ?>">Cuéntanos tu experiencia (opcional)</label>
                    <textarea id="comentario-<?= e($item['id_cita']) ?>" name="comentario" rows="4" maxlength="1500" placeholder="Atención, comunicación, comodidad y resultado de la sesión"></textarea>
                    <p>Una valoración por cita. Tu comentario y puntuación podrán publicarse en el perfil del artista después de su revisión.</p>
                    <button class="admin-button" type="submit">Enviar calificación</button>
                </form>
            <?php else: ?>
                <p class="client-rating-status">Experiencia calificada · <?= e($item['puntuacion']) ?>/5 ★</p>
                <p><?= e(match ($item['estado_publicacion']) {'publicado'=>'Valoración publicada','rechazado'=>'Valoración recibida, sin publicación',default=>'Pendiente de revisión para su publicación'}) ?></p>
                <?php if ($item['comentario']): ?><p><?= nl2br(e($item['comentario'])) ?></p><?php endif ?>
            <?php endif ?>
        </article>
    <?php endforeach ?>
    </div>
</main>
</body>
</html>
