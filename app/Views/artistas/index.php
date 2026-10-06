<?php
declare(strict_types=1);

$accountLink = accountAreaLink($account ?? null, '../');

function artistPublicImage(?string $value): ?string
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    $value = trim(str_replace('\\', '/', $value));
    $scheme = parse_url($value, PHP_URL_SCHEME);

    if ($scheme !== null) {
        if (
            !in_array(strtolower($scheme), ['http', 'https'], true)
            || filter_var($value, FILTER_VALIDATE_URL) === false
        ) {
            return null;
        }

        return $value;
    }

    if (str_starts_with($value, '/')) {
        return $value;
    }

    return '../' . ltrim($value, '/');
}

function artistSummary(?string $biography): string
{
    $text = trim(
        preg_replace(
            '/\s+/u',
            ' ',
            $biography ?? ''
        ) ?? ''
    );

    if ($text === '') {
        return 'Cada artista posee una historia y una forma distinta de convertir la tinta en una obra permanente.';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth(
            $text,
            0,
            175,
            '…',
            'UTF-8'
        );
    }

    return strlen($text) > 175
        ? substr($text, 0, 172) . '...'
        : $text;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="description"
        content="Conoce a los artistas de Tinta Viva, sus especialidades y trabajos publicados."
    >

    <meta name="theme-color" content="#060405">

    <title>Artistas · Tinta Viva</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,500&family=Montserrat:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../assets/css/site.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/artistas.css"
    >

    <script
        src="../assets/js/site.js"
        defer
    ></script>
<link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>">
</head>

<body class="artists-page">
<div class="cursor-glow" aria-hidden="true"></div>

<a class="skip-link" href="#contenido">
    Saltar al contenido
</a>

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

        <button
            class="menu-button"
            id="menuButton"
            type="button"
            aria-controls="mainNav"
            aria-expanded="false"
            aria-label="Abrir menú"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="main-nav" id="mainNav" aria-label="Navegación principal">
            <a href="../index.php#inicio">Inicio</a>
            <a href="index.php" aria-current="page">Artistas</a>
            <a href="../galeria/">Galería</a>
            <a href="../cotizaciones/">Cotizar</a>
            <a href="../index.php#cuidados">Cuidados</a>
        </nav>

        <a class="button button--login" href="<?= e($accountLink['url']) ?>">
            <?= e($accountLink['label']) ?>
        </a>
    </div>

    <div class="blood-edge" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
</header>
<?php endif ?>

<main id="contenido">
    <section class="directory-hero">
        <div class="directory-hero__content">
            <p class="eyebrow">ARCHIVO DE LOS ELEGIDOS</p>

            <h1>
                Maestros de
                <span>la tinta</span>
            </h1>

            <p>
                Conoce las historias, especialidades y obras de quienes
                convierten tus ideas más oscuras en marcas eternas.
            </p>

            <div class="directory-hero__manifesto">
                <span class="directory-hero__sigil" aria-hidden="true">✦</span>

                <p>
                    <strong>Cada piel guarda un secreto.</strong>
                    <span>
                        Encuentra al artista capaz de convertirlo
                        en una obra eterna.
                    </span>
                </p>
            </div>
        </div>
    </section>

    <section class="artist-directory">
        <div class="directory-heading">
            <div>
                <p class="eyebrow">ENCUENTRA TU ARTISTA</p>
                <h2>Elige quién marcará tu historia</h2>
            </div>

            <p>
                Busca por nombre o filtra según la especialidad
                que mejor represente tu idea.
            </p>
        </div>

        <form
            class="artist-filters"
            method="get"
            action="index.php"
        >
            <label>
                <span>Buscar artista o estilo</span>

                <input
                    type="search"
                    name="buscar"
                    maxlength="100"
                    placeholder="Ej. Nocturna, blackwork..."
                    value="<?= e($search) ?>"
                >
            </label>

            <label>
                <span>Especialidad</span>

                <select name="categoria">
                    <option value="">Todas las especialidades</option>

                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= e($category['id_categoria']) ?>"
                            <?= $categoryId === (int) $category['id_categoria']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($category['nombre']) ?>
                            (<?= e($category['total_artistas']) ?>)
                        </option>
                    <?php endforeach ?>
                </select>
            </label>

            <button class="button button--blood" type="submit">
                Buscar
            </button>

            <?php if ($search !== '' || $categoryId !== null): ?>
                <a class="clear-filter" href="index.php">
                    Limpiar filtros
                </a>
            <?php endif ?>
        </form>

        <?php if ($loadError !== null): ?>
            <div class="directory-message directory-message--error" role="alert">
                <span aria-hidden="true">†</span>

                <div>
                    <h2>El archivo permanece cerrado</h2>
                    <p><?= e($loadError) ?></p>
                </div>
            </div>

        <?php elseif ($artists === []): ?>
            <div class="directory-message">
                <span aria-hidden="true">✦</span>

                <div>
                    <h2>No encontramos artistas</h2>
                    <p>
                        Prueba con otro nombre o selecciona una
                        especialidad diferente.
                    </p>
                </div>
            </div>

        <?php else: ?>
            <div class="artist-grid">
                <?php foreach ($artists as $position => $artist): ?>
                    <?php
                    $image = artistPublicImage(
                        $artist['imagen_principal'] ?? null
                    );

                    $specialties = !empty($artist['especialidades'])
                        ? explode('||', $artist['especialidades'])
                        : [];

                    $ratingCount =
                        (int) $artist['cantidad_calificaciones'];

                    $workCount =
                        (int) $artist['total_trabajos'];

                    $artistReference = trim(
                        (string) ($artist['slug'] ?? '')
                    );

                    if ($artistReference === '') {
                        $artistReference = (string) $artist['id_artista'];
                    }
                    ?>

                    <article class="artist-card reveal">
                        <div class="artist-card__portrait">
                            <?php if ($image !== null): ?>
                                <img
                                    src="<?= e($image) ?>"
                                    alt="Retrato de <?= e($artist['nombre_publico']) ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <div class="artist-card__fallback" aria-hidden="true">
                                    <svg viewBox="0 0 260 330">
                                        <circle cx="130" cy="123" r="74"/>
                                        <path d="M71 125c0-55 25-88 59-88s59 33 59 88c0 37-15 59-35 72v43h-48v-43c-20-13-35-35-35-72Z"/>
                                        <path d="M89 127c8-17 26-19 36-2-9 17-28 18-36 2Zm46-2c10-17 28-15 36 2-8 16-27 15-36-2Z"/>
                                        <path d="m130 139-11 25h22Z"/>
                                        <path d="M105 200h50M111 212h38M117 224h26"/>
                                        <path d="M130 15v22M44 123H18M242 123h-26"/>
                                    </svg>
                                </div>
                            <?php endif ?>

                            <div class="artist-card__shade"></div>

                            <span class="artist-card__number">
                                <?= e(str_pad(
                                    (string) ($position + 1),
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                )) ?>
                            </span>

                            <span class="artist-card__status">
                                Artista activo
                            </span>
                        </div>

                        <div class="artist-card__body">
                            <header>
                                <div>
                                    <p class="artist-card__real-name">
                                        <?= e(
                                            trim(
                                                $artist['nombre']
                                                . ' '
                                                . $artist['apellidos']
                                            )
                                        ) ?>
                                    </p>

                                    <h2>
                                        <?= e($artist['nombre_publico']) ?>
                                    </h2>
                                </div>

                                <span class="artist-card__sigil" aria-hidden="true">
                                    ✦
                                </span>
                            </header>

                            <div class="artist-card__metrics">
                                <p>
                                    <?php if ($ratingCount > 0): ?>
                                        <strong>
                                            ★
                                            <?= e(number_format(
                                                (float) $artist['promedio'],
                                                1,
                                                ',',
                                                '.'
                                            )) ?>
                                        </strong>

                                        <span>
                                            <?= e($ratingCount) ?>
                                            reseñas
                                        </span>
                                    <?php else: ?>
                                        <strong>—</strong>
                                        <span>Sin reseñas aún</span>
                                    <?php endif ?>
                                </p>

                                <p>
                                    <strong><?= e($workCount) ?></strong>
                                    <span>
                                        <?= $workCount === 1
                                            ? 'obra publicada'
                                            : 'obras publicadas' ?>
                                    </span>
                                </p>
                            </div>

                            <p class="artist-card__biography">
                                <?= e(artistSummary($artist['biografia'])) ?>
                            </p>

                            <div
                                class="artist-card__specialties"
                                aria-label="Especialidades"
                            >
                                <?php if ($specialties !== []): ?>
                                    <?php foreach ($specialties as $specialty): ?>
                                        <span><?= e($specialty) ?></span>
                                    <?php endforeach ?>
                                <?php else: ?>
                                    <span>Estilo por definir</span>
                                <?php endif ?>
                            </div>

                            <a
                                class="artist-card__profile-link"
                                href="ver.php?artista=<?= e(urlencode($artistReference)) ?>"
                            >
                                Conocer su historia
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
</main>

<footer class="site-footer">
    <a class="brand" href="../index.php">
        <span class="brand__text">
            <strong>TINTA VIVA</strong>
            <small>ARTE QUE DEJA HUELLA</small>
        </span>
    </a>

    <p>Personas reales. Historias eternas.</p>

    <p>© <?= date('Y') ?> Tinta Viva</p>
</footer>
</body>
</html>
