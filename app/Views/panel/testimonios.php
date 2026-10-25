<?php
declare(strict_types=1);

function adminTestimonialDate(?string $value, string $format = 'd/m/Y · H:i'): string
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
    <title>Moderación de testimonios · Tinta Viva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-artistas.css">
    <link rel="stylesheet" href="../assets/css/admin-testimonios.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/admin-testimonios.css')) ?>">
    <link rel="stylesheet" href="../assets/css/admin-navegacion.css">
    <?php responsiveAssets(); ?>
</head>
<body class="admin-artists admin-testimonials">
<?php require __DIR__.'/../admin-navegacion.php'; ?>

<main class="admin-main">
    <header class="admin-heading">
        <div><p class="admin-eyebrow">VOCES DEL ESTUDIO</p><h1>Testimonios</h1><p>Revisa las historias enviadas por clientes reales antes de incorporarlas al archivo público de Tinta Viva.</p></div>
        <a class="admin-button admin-button--ghost" href="../testimonios/" target="_blank" rel="noopener">Ver página pública ↗</a>
    </header>

    <?php if ($testimonialNotice !== null): ?><div class="admin-notice" role="status"><span>✓</span><?= e($testimonialNotice) ?></div><?php endif ?>
    <?php if ($testimonialError !== null): ?><div class="admin-notice admin-notice--error" role="alert"><span>!</span><?= e($testimonialError) ?></div><?php endif ?>

    <section class="admin-testimonial-stats" aria-label="Resumen de testimonios">
        <article><span>I</span><b><?= e($testimonialSummary['total']) ?></b><p>Total</p></article>
        <article><span>II</span><b><?= e($testimonialSummary['pendientes']) ?></b><p>Por revisar</p></article>
        <article><span>III</span><b><?= e($testimonialSummary['publicados']) ?></b><p>Publicados</p></article>
        <article><span>IV</span><b><?= e($testimonialSummary['destacados']) ?></b><p>Destacados</p></article>
    </section>

    <nav class="admin-testimonial-filters" aria-label="Filtrar testimonios">
        <a href="testimonios.php" <?= $testimonialStatus === '' ? 'aria-current="page"' : '' ?>>Todos</a>
        <a href="testimonios.php?estado=pendiente" <?= $testimonialStatus === 'pendiente' ? 'aria-current="page"' : '' ?>>Pendientes</a>
        <a href="testimonios.php?estado=publicado" <?= $testimonialStatus === 'publicado' ? 'aria-current="page"' : '' ?>>Publicados</a>
        <a href="testimonios.php?estado=rechazado" <?= $testimonialStatus === 'rechazado' ? 'aria-current="page"' : '' ?>>Rechazados</a>
    </nav>

    <?php if ($testimonials === []): ?>
        <div class="admin-testimonial-empty"><span>“</span><p>No hay testimonios en esta bandeja.</p></div>
    <?php else: ?>
        <section class="admin-testimonial-list">
            <?php foreach ($testimonials as $testimonial): ?>
                <article class="admin-testimonial-card admin-testimonial-card--<?= e($testimonial['estado_publicacion']) ?>">
                    <div class="admin-testimonial-card__quote" aria-hidden="true">“</div>
                    <div class="admin-testimonial-card__content">
                        <p class="admin-testimonial-card__meta">TESTIMONIO #<?= e($testimonial['id_testimonio']) ?> · <?= e(adminTestimonialDate($testimonial['fecha'])) ?></p>
                        <h2><?= e($testimonial['titulo'] ?: 'Experiencia en Tinta Viva') ?></h2>
                        <blockquote><?= nl2br(e($testimonial['contenido'])) ?></blockquote>
                        <dl><div><dt>Cliente</dt><dd><?= e($testimonial['cliente']) ?> · <?= e($testimonial['correo_cliente']) ?></dd></div><div><dt>Publicar como</dt><dd><?= e($testimonial['nombre_publico']) ?></dd></div><div><dt>Obra</dt><dd><?= e($testimonial['estilo']) ?> con <?= e($testimonial['artista']) ?></dd></div><div><dt>Cita</dt><dd><?= e(adminTestimonialDate($testimonial['fecha_hora_inicio'], 'd/m/Y')) ?></dd></div></dl>
                    </div>
                    <form method="post" class="admin-testimonial-card__actions">
                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                        <input type="hidden" name="id_testimonio" value="<?= e($testimonial['id_testimonio']) ?>">
                        <label><span>Estado</span><select name="estado_publicacion"><option value="pendiente" <?= $testimonial['estado_publicacion'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option><option value="publicado" <?= $testimonial['estado_publicacion'] === 'publicado' ? 'selected' : '' ?>>Publicado</option><option value="rechazado" <?= $testimonial['estado_publicacion'] === 'rechazado' ? 'selected' : '' ?>>Rechazado</option></select></label>
                        <label class="admin-testimonial-featured"><input type="checkbox" name="destacado" value="1" <?= !empty($testimonial['destacado']) ? 'checked' : '' ?>><span></span>Destacar en la página pública</label>
                        <button type="submit">Guardar moderación</button>
                    </form>
                </article>
            <?php endforeach ?>
        </section>
    <?php endif ?>
</main>
</body>
</html>
