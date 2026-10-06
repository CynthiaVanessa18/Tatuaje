<?php
declare(strict_types=1);

$hasFilters = $search !== '' || $categoryId !== null || $artistId !== null;
$accountLink = accountAreaLink($account ?? null, '../');
$totalPublishedWorks = (int) ($summary['total_obras'] ?? 0);
$galleryRomanTotal = galleryRomanNumeral($totalPublishedWorks);
$galleryStoryIntro = match ($totalPublishedWorks) {
    0 => 'La colección espera su primera historia.',
    1 => 'Una historia nacida entre tinta, sombras y ritual.',
    default => $totalPublishedWorks . ' historias nacidas entre tinta, sombras y ritual.',
};
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Galería de tatuajes de Tinta Viva: blackwork, realismo oscuro, línea fina y arte gótico.">
    <meta name="theme-color" content="#060405">
    <title>Galería · Tinta Viva</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,500&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/site.css">
    <link rel="stylesheet" href="../assets/css/galeria.css">
    <script src="../assets/js/site.js" defer></script>
    <script src="../assets/js/galeria.js" defer></script>
<link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>">
</head>

<body class="gallery-page">
<div class="cursor-glow" aria-hidden="true"></div>
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<?php if (($account['nombre_rol']??null)==='cliente'): $clientEndpoint='../index.php';$publicPrefix='../';require __DIR__.'/../cliente-navegacion.php'; else: ?>
<header class="site-header" id="siteHeader">
    <div class="nav-shell">
        <a class="brand" href="../index.php" aria-label="Tinta Viva, inicio">
            <svg class="brand__symbol" viewBox="0 0 64 64" aria-hidden="true">
                <path d="M32 4 42 20 60 24 46 37 49 57 32 48 15 57 18 37 4 24 22 20Z"/>
                <circle cx="32" cy="31" r="9"/>
                <path d="M32 12v38M13 31h38"/>
            </svg>
            <span class="brand__text">
                <strong>TINTA VIVA</strong>
                <small>ARTE QUE DEJA HUELLA</small>
            </span>
        </a>

        <button class="menu-button" id="menuButton" type="button" aria-controls="mainNav" aria-expanded="false" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" id="mainNav" aria-label="Navegación principal">
            <a href="../index.php">Inicio</a>
            <a href="../artistas/">Artistas</a>
            <a href="index.php" aria-current="page">Galería</a>
            <a href="../cotizaciones/">Cotizar</a>
            <a href="../index.php#cuidados">Cuidados</a>
        </nav>

        <a class="button button--login" href="<?= e($accountLink['url']) ?>"><?= e($accountLink['label']) ?></a>
    </div>

    <div class="blood-edge" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
</header>
<?php endif ?>

<main id="contenido">
    <section class="gallery-hero">
        <div class="gallery-hero__mist" aria-hidden="true"></div>

        <div class="gallery-hero__copy reveal">
            <p class="eyebrow">EL ARCHIVO DE LA PIEL</p>
            <h1>Galería de<br><span>marcas eternas</span></h1>
            <p><?= e($galleryStoryIntro) ?> Explora cada pieza y descubre al artista detrás de la obra.</p>
        </div>

        <div class="gallery-hero__sigil reveal" aria-hidden="true">
            <span><?= e($galleryRomanTotal) ?></span>
            <svg viewBox="0 0 300 300">
                <circle cx="150" cy="150" r="112"/>
                <circle cx="150" cy="150" r="78"/>
                <path d="M150 24 230 150 150 276 70 150Z"/>
                <path d="M74 74 226 226M226 74 74 226"/>
                <circle cx="150" cy="150" r="18"/>
            </svg>
        </div>

        <dl class="gallery-stats reveal">
            <div><dt><?= e($summary['total_obras']) ?></dt><dd>Obras publicadas</dd></div>
            <div><dt><?= e($summary['total_artistas']) ?></dt><dd>Artistas</dd></div>
            <div><dt><?= e($summary['total_estilos']) ?></dt><dd>Estilos</dd></div>
        </dl>
    </section>

    <div class="gallery-tear" aria-hidden="true"></div>

    <section class="gallery-archive">
        <div class="gallery-heading reveal">
            <div>
                <p class="eyebrow">COLECCIÓN COMPLETA</p>
                <h2>Cada imagen guarda un secreto</h2>
            </div>
            <p>Selecciona una obra para verla en detalle. Puedes filtrar por estilo, artista o palabra.</p>
        </div>

        <form class="gallery-filters reveal" method="get">
            <label class="gallery-filters__search">
                <span>Buscar en el archivo</span>
                <input name="buscar" value="<?= e($search) ?>" maxlength="100" placeholder="Cuervo, rosa, catedral...">
            </label>

            <label>
                <span>Estilo</span>
                <select name="categoria">
                    <option value="">Todos los estilos</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id_categoria']) ?>" <?= $categoryId === (int) $category['id_categoria'] ? 'selected' : '' ?>>
                            <?= e($category['nombre']) ?> · <?= e($category['total_obras']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </label>

            <label>
                <span>Artista</span>
                <select name="artista">
                    <option value="">Todos los artistas</option>
                    <?php foreach ($artists as $galleryArtist): ?>
                        <option value="<?= e($galleryArtist['id_artista']) ?>" <?= $artistId === (int) $galleryArtist['id_artista'] ? 'selected' : '' ?>>
                            <?= e($galleryArtist['nombre_publico']) ?> · <?= e($galleryArtist['total_obras']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </label>

            <button class="button button--blood" type="submit">Invocar obras</button>

            <?php if ($hasFilters): ?>
                <a class="gallery-filters__clear" href="index.php">Limpiar filtros</a>
            <?php endif ?>
        </form>

        <?php if ($loadError !== null): ?>
            <div class="gallery-message gallery-message--error" role="alert"><?= e($loadError) ?></div>
        <?php elseif ($works === []): ?>
            <div class="gallery-message">
                <span aria-hidden="true">†</span>
                Ninguna obra responde a esta invocación. Prueba con otros filtros.
            </div>
        <?php else: ?>
            <div class="gallery-result-line reveal">
                <strong><?= e(count($works)) ?></strong>
                <?= count($works) === 1 ? 'obra encontrada' : 'obras encontradas' ?>
            </div>

            <div class="gallery-grid" id="galleryGrid">
                <?php foreach ($works as $position => $work): ?>
                    <?php $imageUrl = galleryImageUrl($work['imagen_url'] ?? null); ?>

                    <article class="gallery-card reveal <?= $position % 5 === 1 || $position % 5 === 4 ? 'gallery-card--tall' : '' ?>">
                        <button
                            class="gallery-card__open"
                            type="button"
                            data-gallery-open
                            data-image="<?= e($imageUrl) ?>"
                            data-title="<?= e($work['titulo']) ?>"
                            data-category="<?= e($work['categoria']) ?>"
                            data-artist="<?= e($work['artista']) ?>"
                            data-description="<?= e($work['descripcion'] ?: 'Una pieza creada para permanecer.') ?>"
                            data-date="<?= e(date('Y', strtotime((string) $work['fecha_realizacion']))) ?>"
                            aria-label="Ampliar <?= e($work['titulo']) ?>"
                        >
                            <?php if ($imageUrl !== null): ?>
                                <img
                                    src="<?= e($imageUrl) ?>"
                                    alt="<?= e($work['texto_alternativo'] ?: $work['titulo']) ?>"
                                    loading="lazy"
                                    width="1024"
                                    height="1536"
                                >
                            <?php else: ?>
                                <span class="gallery-card__fallback" aria-hidden="true">†</span>
                            <?php endif ?>

                            <span class="gallery-card__shade"></span>
                            <span class="gallery-card__number"><?= str_pad((string) ($position + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="gallery-card__zoom">Ver obra <b aria-hidden="true">↗</b></span>
                        </button>

                        <div class="gallery-card__caption">
                            <div>
                                <p><?= e($work['categoria']) ?></p>
                                <h3><?= e($work['titulo']) ?></h3>
                            </div>
                            <a href="../artistas/ver.php?artista=<?= rawurlencode((string) ($work['artista_slug'] ?: $work['id_artista'])) ?>">
                                <?= e($work['artista']) ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>

    <section class="gallery-callout reveal">
        <p class="eyebrow">TU HISTORIA AÚN NO ESTÁ AQUÍ</p>
        <h2>Conviértela en la próxima obra.</h2>
        <a class="button button--blood" href="../cotizaciones/">Solicitar cotización †</a>
    </section>
</main>

<dialog class="gallery-lightbox" id="galleryLightbox" aria-labelledby="lightboxTitle">
    <button class="gallery-lightbox__close" type="button" data-gallery-close aria-label="Cerrar imagen">×</button>
    <div class="gallery-lightbox__image"><img src="" alt="" data-lightbox-image></div>
    <div class="gallery-lightbox__copy">
        <p class="eyebrow" data-lightbox-category></p>
        <h2 id="lightboxTitle" data-lightbox-title></h2>
        <p data-lightbox-description></p>
        <div><span data-lightbox-artist></span><span data-lightbox-date></span></div>
    </div>
</dialog>

<footer class="site-footer">
    <a class="brand" href="../index.php">
        <span class="brand__text"><strong>TINTA VIVA</strong><small>ARTE QUE DEJA HUELLA</small></span>
    </a>
    <p>Personas reales. Historias eternas.</p>
    <p>© <?= date('Y') ?> Tinta Viva</p>
</footer>
</body>
</html>
