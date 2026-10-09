<?php
declare(strict_types=1);

$accountLink = accountAreaLink($account);
$isClient = ($account['nombre_rol'] ?? '') === 'cliente';
$careCount = count($careInstructions);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050304">
    <meta name="description" content="Guía de cuidados para acompañar la cicatrización de tu tatuaje en Tinta Viva.">
    <title>Cuidados del tatuaje · Tinta Viva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/cuidados.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cuidados.css')) ?>">
    <?php if ($isClient): ?><link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>"><?php endif ?>
    <?php responsiveAssets(); ?>
</head>
<body class="care-public<?= $isClient ? ' client-shared-layout' : '' ?>">
<?php if ($isClient): ?>
    <?php $clientEndpoint='../index.php';$publicPrefix='../';require __DIR__.'/../cliente-navegacion.php'; ?>
<?php else: ?>
    <header class="care-nav">
        <a class="care-nav__brand" href="../index.php">TINTA <strong>VIVA</strong><small>ARTE QUE DEJA HUELLA</small></a>
        <nav aria-label="Navegación principal"><a href="../index.php">Inicio</a><a href="../artistas/">Artistas</a><a href="../galeria/">Galería</a><a href="../cotizaciones/">Cotizar</a><a aria-current="page" href="./">Cuidados</a><a href="../testimonios/">Testimonios</a><a href="../preguntas/">Preguntas</a></nav>
        <a class="care-nav__account" href="../<?= e($accountLink['url']) ?>"><?= e($accountLink['label']) ?></a>
    </header>
<?php endif ?>

<main>
    <section class="care-hero">
        <div class="care-hero__copy">
            <p class="care-kicker"><span>✦</span> EL RITUAL DESPUÉS DE LA AGUJA</p>
            <h1>La obra termina<br><em>cuando la piel sana.</em></h1>
            <p>La tinta ya es parte de ti. Ahora comienza el proceso que protege sus líneas, su intensidad y la historia que decidiste llevar.</p>
            <a href="#ritual-curacion">Comenzar el ritual <b>↓</b></a>
        </div>
        <div class="care-hero__mark" aria-hidden="true"><span>†</span><i></i><b></b></div>
        <p class="care-hero__mantra">LIMPIAR · PROTEGER · RESPETAR · SANAR</p>
    </section>

    <section class="care-intro" id="ritual-curacion">
        <div><p class="care-kicker"><span>✦</span> CÓDICE DE CICATRIZACIÓN</p><h2>Cinco pasos.<br><em>Una marca eterna.</em></h2></div>
        <p>Sigue siempre las indicaciones particulares de tu artista. Esta guía reúne recomendaciones generales para acompañar cada etapa.</p>
    </section>

    <?php if ($careInstructions === []): ?>
        <div class="care-empty"><span>†</span><p>La guía está siendo preparada por el estudio.</p></div>
    <?php else: ?>
        <section class="care-timeline" data-care-timeline>
            <aside class="care-timeline__rail" aria-hidden="true">
                <span data-care-current>01</span>
                <i><b data-care-progress></b></i>
                <small><?= e(str_pad((string) $careCount, 2, '0', STR_PAD_LEFT)) ?></small>
            </aside>

            <div class="care-timeline__entries">
                <?php foreach ($careInstructions as $index => $care): ?>
                    <article class="care-step care-step--scene-<?= e((string) (($index % 5) + 1)) ?>" id="<?= e($care['slug'] ?: 'cuidado-' . $care['id_cuidado']) ?>" data-care-step data-step="<?= e((string) ($index + 1)) ?>">
                        <div class="care-step__visual">
                            <?php if (!empty($care['imagen_url'])): ?><img src="<?= e(imageUrl($care['imagen_url'])) ?>" alt="<?= e($care['texto_alternativo'] ?: $care['titulo']) ?>"><?php endif ?>
                            <span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                        </div>
                        <div class="care-step__copy">
                            <p><?= e($care['categoria'] ?: 'Cuidado esencial') ?></p>
                            <h3><?= e($care['titulo']) ?></h3>
                            <div><?= nl2br(e($care['contenido'])) ?></div>
                            <small>PASO <?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?> · TINTA VIVA</small>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        </section>
    <?php endif ?>

    <section class="care-warning">
        <span aria-hidden="true">!</span>
        <div><p class="care-kicker">ESCUCHA A TU CUERPO</p><h2>Si algo no se siente bien, no lo ignores.</h2><p>Esta guía es informativa y no sustituye una valoración profesional. Sigue las indicaciones de tu artista y busca atención sanitaria ante síntomas preocupantes.</p></div>
        <a href="../preguntas/">Consultar preguntas →</a>
    </section>
</main>

<footer class="care-footer"><strong>TINTA VIVA</strong><span>La tinta permanece cuando la piel se respeta</span><small>© <?= date('Y') ?></small></footer>
<?php if ($careInstructions !== []): ?><script src="../assets/js/cuidados.js?v=<?= e(filemtime(__DIR__.'/../../../public/assets/js/cuidados.js')) ?>" defer></script><?php endif ?>
</body>
</html>
