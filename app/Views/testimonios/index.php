<?php
declare(strict_types=1);

$accountLink = accountAreaLink($account);

function testimonialPublicDate(string $value): string
{
    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('America/Costa_Rica'))
        ->format('Y');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050304">
    <meta name="description" content="Historias reales de clientes de Tinta Viva y las obras que eligieron llevar en la piel.">
    <title>Testimonios · Tinta Viva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/testimonios.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/testimonios.css')) ?>">
    <?php if (($account['nombre_rol'] ?? '') === 'cliente'): ?><link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>"><?php endif ?>
    <?php responsiveAssets(); ?>
</head>
<body class="testimonials-public<?= ($account['nombre_rol'] ?? '') === 'cliente' ? ' client-shared-layout' : '' ?>">
<?php if (($account['nombre_rol'] ?? '') === 'cliente'): ?>
    <?php $clientEndpoint='../index.php';$publicPrefix='../';require __DIR__.'/../cliente-navegacion.php'; ?>
<?php else: ?>
    <header class="testimonials-nav">
        <a class="testimonials-nav__brand" href="../index.php">TINTA <strong>VIVA</strong><small>ARTE QUE DEJA HUELLA</small></a>
        <nav aria-label="Navegación principal"><a href="../index.php">Inicio</a><a href="../artistas/">Artistas</a><a href="../galeria/">Galería</a><a href="../cotizaciones/">Cotizar</a><a href="../cuidados/">Cuidados</a><a aria-current="page" href="./">Testimonios</a><a href="../preguntas/">Preguntas</a></nav>
        <a class="testimonials-nav__account" href="../<?= e($accountLink['url']) ?>"><?= e($accountLink['label']) ?></a>
    </header>
<?php endif ?>

<main>
    <section class="testimonials-hero">
        <div class="testimonials-hero__moon" aria-hidden="true"></div>
        <div class="testimonials-hero__copy">
            <p class="testimonials-kicker"><span>✦</span> VOCES BAJO LA PIEL</p>
            <h1>Historias que<br><em>ya no pueden borrarse.</em></h1>
            <p>No hablamos por nuestros clientes. Dejamos que las personas que confiaron su historia al estudio cuenten cómo una idea terminó convertida en parte de ellas.</p>
            <?php if (($account['nombre_rol'] ?? '') === 'cliente'): ?><a href="../panel/mis-testimonios.php">Contar mi experiencia <b>†</b></a><?php else: ?><a href="../auth/login.php">Ingresar para dejar mi voz <b>†</b></a><?php endif ?>
        </div>
        <blockquote><span>“</span>La tinta permanece.<br>La experiencia también.</blockquote>
    </section>

    <section class="testimonials-archive">
        <header><div><p class="testimonials-kicker"><span>✦</span> EL ARCHIVO DE LAS VOCES</p><h2>Personas reales.<br><em>Marcas eternas.</em></h2></div><p>Recorre cada historia y descubre la experiencia detrás de la tinta.</p></header>

        <?php if ($testimonials === []): ?>
            <div class="testimonials-empty"><span>†</span><p>Las primeras historias todavía están siendo escritas.</p></div>
        <?php else: ?>
            <div class="testimonial-carousel" data-testimonial-carousel aria-roledescription="carrusel" aria-label="Testimonios de clientes">
                <div class="testimonial-carousel__stage" aria-live="polite">
                <?php foreach ($testimonials as $index => $testimonial): ?>
                    <article
                        class="testimonial-card testimonial-card--scene-<?= e((string) (($index % 5) + 1)) ?><?= !empty($testimonial['destacado']) ? ' testimonial-card--featured' : '' ?><?= $index === 0 ? ' is-active' : '' ?>"
                        data-testimonial-slide
                        aria-label="Testimonio <?= e((string) ($index + 1)) ?> de <?= e((string) count($testimonials)) ?>"
                        aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
                    >
                        <div class="testimonial-card__index"><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></div>
                        <div class="testimonial-card__quote" aria-hidden="true">“</div>
                        <p class="testimonial-card__meta"><?= e($testimonial['estilo'] ?: 'Tatuaje personalizado') ?> · <?= e(testimonialPublicDate($testimonial['fecha'])) ?></p>
                        <h3><?= e($testimonial['titulo'] ?: 'Una historia marcada') ?></h3>
                        <blockquote><?= nl2br(e($testimonial['contenido'])) ?></blockquote>
                        <footer><div><strong><?= e($testimonial['nombre_publico'] ?: 'Cliente de Tinta Viva') ?></strong><span>con <?= e($testimonial['artista'] ?: 'el estudio') ?></span></div><?php if (!empty($testimonial['destacado'])): ?><b>DESTACADO</b><?php endif ?></footer>
                    </article>
                <?php endforeach ?>
                </div>

                <div class="testimonial-carousel__controls">
                    <button type="button" data-carousel-prev aria-label="Ver testimonio anterior"><span aria-hidden="true">←</span> Anterior</button>
                    <div class="testimonial-carousel__progress" aria-hidden="true">
                        <strong data-carousel-current>01</strong>
                        <i><span data-carousel-bar></span></i>
                        <span><?= e(str_pad((string) count($testimonials), 2, '0', STR_PAD_LEFT)) ?></span>
                    </div>
                    <div class="testimonial-carousel__dots" aria-label="Elegir testimonio">
                        <?php foreach ($testimonials as $index => $testimonial): ?>
                            <button type="button" data-carousel-dot="<?= e((string) $index) ?>" aria-label="Ir al testimonio <?= e((string) ($index + 1)) ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>"></button>
                        <?php endforeach ?>
                    </div>
                    <button type="button" data-carousel-next aria-label="Ver siguiente testimonio">Siguiente <span aria-hidden="true">→</span></button>
                </div>
            </div>
        <?php endif ?>
    </section>

    <section class="testimonials-cta"><span aria-hidden="true">✦</span><div><p class="testimonials-kicker">TU EXPERIENCIA TAMBIÉN IMPORTA</p><h2>¿Tu obra ya forma parte de ti?</h2><p>Cuando tu cita esté finalizada podrás contarla desde tu espacio privado.</p></div><a href="<?= ($account['nombre_rol'] ?? '') === 'cliente' ? '../panel/mis-testimonios.php' : '../auth/login.php' ?>">Dejar mi testimonio →</a></section>
</main>

<footer class="testimonials-footer"><strong>TINTA VIVA</strong><span>Personas reales · Historias eternas</span><small>© <?= date('Y') ?></small></footer>
<?php if ($testimonials !== []): ?><script src="../assets/js/testimonios-carousel.js?v=<?= e(filemtime(__DIR__.'/../../../public/assets/js/testimonios-carousel.js')) ?>" defer></script><?php endif ?>
</body>
</html>
