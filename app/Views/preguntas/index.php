<?php
declare(strict_types=1);

$accountLink = accountAreaLink($account);
$faqTotal = count($faqs);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050304">
    <meta name="description" content="Respuestas sobre cotizaciones, sesiones, dolor, preparación y cuidados en Tinta Viva.">
    <title>Preguntas frecuentes · Tinta Viva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/preguntas.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/preguntas.css')) ?>">
    <?php if (($account['rol'] ?? '') === 'cliente'): ?><link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>"><?php endif ?>
    <?php responsiveAssets(); ?>
</head>
<body class="faq-public<?= ($account['rol'] ?? '') === 'cliente' ? ' client-shared-layout' : '' ?>">
<?php if (($account['rol'] ?? '') === 'cliente'): ?>
    <?php $clientEndpoint='../panel/cliente.php';$publicPrefix='../';require __DIR__.'/../cliente-navegacion.php'; ?>
<?php else: ?>
    <header class="faq-nav">
        <a class="faq-nav__brand" href="../index.php">TINTA <strong>VIVA</strong><small>ARTE QUE DEJA HUELLA</small></a>
        <nav aria-label="Navegación principal"><a href="../index.php">Inicio</a><a href="../artistas/">Artistas</a><a href="../galeria/">Galería</a><a href="../cotizaciones/">Cotizar</a><a href="../cuidados/">Cuidados</a><a href="../testimonios/">Testimonios</a><a aria-current="page" href="./">Preguntas</a></nav>
        <a class="faq-nav__account" href="../<?= e($accountLink['url']) ?>"><?= e($accountLink['label']) ?></a>
    </header>
<?php endif ?>

<main>
    <section class="faq-hero">
        <div class="faq-hero__copy">
            <p class="faq-kicker"><span>✦</span> EL ORÁCULO DE LA TINTA</p>
            <h1>Todo lo que<br><em>la piel pregunta.</em></h1>
            <p>Antes de la primera línea siempre hay dudas. Aquí reunimos respuestas honestas para que llegues al estudio con claridad, confianza y una idea lista para cobrar vida.</p>
            <a href="#archivo-preguntas">Consultar el archivo <b>↓</b></a>
        </div>
        <div class="faq-hero__sigil" aria-hidden="true"><span>?</span><i></i><b></b></div>
        <p class="faq-hero__aside">CONOCIMIENTO<br>ANTES DE LA<br>AGUJA</p>
    </section>

    <section class="faq-archive" id="archivo-preguntas">
        <header class="faq-archive__heading">
            <div><p class="faq-kicker"><span>✦</span> RESPUESTAS DEL ESTUDIO</p><h2>Abre la pregunta.<br><em>Despeja la sombra.</em></h2></div>
            <p>Busca una palabra o recorre las categorías. Cada respuesta viene directamente del proceso de Tinta Viva.</p>
        </header>

        <div class="faq-search">
            <label for="faq-query"><span>⌕</span><input id="faq-query" type="search" placeholder="Escribe tu duda: dolor, cuidados, agenda…" autocomplete="off" data-faq-search></label>
            <p><strong data-faq-visible><?= e((string) $faqTotal) ?></strong> respuestas disponibles</p>
        </div>

        <nav class="faq-categories" aria-label="Categorías de preguntas">
            <button type="button" data-faq-category="" aria-pressed="true">Todas</button>
            <?php foreach ($faqCategories as $category): ?>
                <button type="button" data-faq-category="<?= e($category['categoria']) ?>" aria-pressed="false"><?= e($category['categoria']) ?><span><?= e($category['total']) ?></span></button>
            <?php endforeach ?>
        </nav>

        <?php if ($faqs === []): ?>
            <div class="faq-empty"><span>?</span><p>El archivo todavía no contiene respuestas.</p></div>
        <?php else: ?>
            <div class="faq-list" data-faq-list>
                <?php foreach ($faqs as $index => $faq): ?>
                    <details class="faq-item" data-faq-item data-category="<?= e($faq['categoria'] ?: 'General') ?>"<?= $index === 0 ? ' open' : '' ?>>
                        <summary>
                            <span class="faq-item__number"><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                            <span class="faq-item__category"><?= e($faq['categoria'] ?: 'General') ?></span>
                            <strong><?= e($faq['pregunta']) ?></strong>
                            <i aria-hidden="true"></i>
                        </summary>
                        <div class="faq-item__answer"><span aria-hidden="true">†</span><p><?= nl2br(e($faq['respuesta'])) ?></p></div>
                    </details>
                <?php endforeach ?>
            </div>
            <div class="faq-no-results" data-faq-empty hidden><span>∅</span><p>Ninguna respuesta coincide con tu búsqueda.</p><small>Prueba con otra palabra o selecciona todas las categorías.</small></div>
        <?php endif ?>
    </section>

    <section class="faq-cta"><div><p class="faq-kicker">¿TU DUDA ES MÁS PERSONAL?</p><h2>Convirtamos la pregunta en una idea.</h2><p>Cuéntanos qué imaginas y el estudio te responderá mediante una cotización.</p></div><a href="../cotizaciones/">Solicitar cotización →</a></section>
</main>

<footer class="faq-footer"><strong>TINTA VIVA</strong><span>Claridad antes de marcar la piel</span><small>© <?= date('Y') ?></small></footer>
<?php if ($faqs !== []): ?><script src="../assets/js/preguntas.js?v=<?= e(filemtime(__DIR__.'/../../../public/assets/js/preguntas.js')) ?>" defer></script><?php endif ?>
</body>
</html>
