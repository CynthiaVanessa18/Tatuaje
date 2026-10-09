<?php
declare(strict_types=1);

$testimonialStatusLabels = [
    'pendiente' => 'En revisión',
    'publicado' => 'Publicado',
    'rechazado' => 'No publicado',
];

function clientTestimonialDate(?string $value, string $format = 'd/m/Y'): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('America/Costa_Rica'))
        ->format($format);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050304">
    <title>Mi testimonio · Tinta Viva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-artistas.css">
    <link rel="stylesheet" href="../assets/css/testimonios.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/testimonios.css')) ?>">
    <link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>">
    <?php responsiveAssets(); ?>
</head>
<body class="client-shared-layout client-testimonials">
<?php $clientEndpoint='../index.php';$publicPrefix='../';require __DIR__.'/../cliente-navegacion.php'; ?>

<main class="client-testimonials__main">
    <section class="client-testimonials__hero">
        <p class="testimonials-kicker"><span>✦</span> TU VOZ TAMBIÉN DEJA HUELLA</p>
        <h1>Cuenta la historia<br><em>detrás de tu marca.</em></h1>
        <p>Solo puedes escribir sobre una cita finalizada. Tu testimonio pasa primero por revisión para proteger tu privacidad y la del estudio.</p>
        <a href="../testimonios/">Ver historias publicadas ↗</a>
    </section>

    <?php if ($testimonialNotice !== null): ?><div class="testimonial-notice" role="status"><span>✓</span><?= e($testimonialNotice) ?></div><?php endif ?>
    <?php if ($testimonialError !== null): ?><div class="testimonial-notice testimonial-notice--error" role="alert"><span>!</span><?= e($testimonialError) ?></div><?php endif ?>
    <?php if ($profileMissing): ?><div class="testimonial-notice testimonial-notice--error"><span>!</span>Tu cuenta todavía no tiene un perfil de cliente vinculado.</div><?php endif ?>

    <div class="client-testimonials__layout">
        <section class="testimonial-form-card">
            <div class="testimonial-form-card__number" aria-hidden="true">I</div>
            <header><p class="testimonials-kicker"><span>✦</span> ESCRIBIR TESTIMONIO</p><h2>Tu experiencia,<br>con tus palabras.</h2></header>

            <?php if ($eligibleAppointments === []): ?>
                <div class="testimonials-empty"><span>◷</span><p>No tienes citas finalizadas disponibles.</p><small>Cuando el administrador finalice una cita, podrás dejar aquí un único testimonio sobre esa experiencia.</small></div>
            <?php else: ?>
                <form method="post" class="testimonial-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <label><span>Obra realizada *</span><select name="id_cita" required><option value="">Selecciona una cita finalizada</option><?php foreach ($eligibleAppointments as $eligible): ?><option value="<?= e($eligible['id_cita']) ?>" <?= (string) ($_POST['id_cita'] ?? '') === (string) $eligible['id_cita'] ? 'selected' : '' ?>><?= e(clientTestimonialDate($eligible['fecha_hora_inicio'])) ?> · <?= e($eligible['artista']) ?> · <?= e($eligible['estilo']) ?></option><?php endforeach ?></select></label>
                    <label><span>Título</span><input name="titulo" maxlength="180" value="<?= e($_POST['titulo'] ?? '') ?>" placeholder="Ej. Una obra que superó mi idea"></label>
                    <label><span>Tu historia *</span><textarea name="contenido" rows="8" minlength="40" maxlength="1500" required placeholder="Cuéntanos cómo fue el proceso, qué sentiste y qué significa la obra para ti..."><?= e($_POST['contenido'] ?? '') ?></textarea><small>Entre 40 y 1500 caracteres.</small></label>
                    <label><span>Nombre público *</span><input name="nombre_publico" maxlength="180" value="<?= e($_POST['nombre_publico'] ?? $defaultPublicName) ?>" required><small>Puedes usar tu nombre abreviado o un seudónimo.</small></label>
                    <button type="submit">Entregar mi historia <b>†</b></button>
                </form>
            <?php endif ?>
        </section>

        <section class="client-testimonial-history">
            <header><p class="testimonials-kicker"><span>✦</span> MI ARCHIVO</p><h2>Historias entregadas</h2><b><?= e(count($clientTestimonials)) ?></b></header>
            <?php if ($clientTestimonials === []): ?>
                <div class="testimonials-empty"><span>“</span><p>Todavía no has entregado ningún testimonio.</p></div>
            <?php else: ?>
                <div class="client-testimonial-list">
                    <?php foreach ($clientTestimonials as $testimonial): ?>
                        <article>
                            <div class="client-testimonial-list__status client-testimonial-list__status--<?= e($testimonial['estado_publicacion']) ?>"><?= e($testimonialStatusLabels[$testimonial['estado_publicacion']] ?? $testimonial['estado_publicacion']) ?></div>
                            <p><?= e($testimonial['estilo']) ?> · <?= e($testimonial['artista']) ?></p>
                            <h3><?= e($testimonial['titulo'] ?: 'Mi experiencia en Tinta Viva') ?></h3>
                            <blockquote><?= nl2br(e($testimonial['contenido'])) ?></blockquote>
                            <footer><span>Como <?= e($testimonial['nombre_publico']) ?></span><time><?= e(clientTestimonialDate($testimonial['fecha'])) ?></time></footer>
                        </article>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>
    </div>
</main>
</body>
</html>
