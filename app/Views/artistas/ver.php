<?php
declare(strict_types=1);

$accountLink = accountAreaLink($account ?? null, '../');

function artistProfileImage(?string $value): ?string
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

function artistProfileLink(?string $value): ?string
{
    if ($value === null || trim($value) === '') {
        return null;
    }

    $value = trim($value);

    return filter_var($value, FILTER_VALIDATE_URL) !== false
        && in_array(
            strtolower((string) parse_url($value, PHP_URL_SCHEME)),
            ['http', 'https'],
            true
        )
            ? $value
            : null;
}

function artistProfileYear(?string $date): string
{
    if ($date === null || $date === '') {
        return '';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? '' : date('Y', $timestamp);
}

$pageTitle = $artist !== null
    ? ($artist['nombre_publico'] . ' · Tinta Viva')
    : 'Artista no encontrado · Tinta Viva';

$profileImage = $artist !== null
    ? artistProfileImage($artist['foto_url'] ?? null)
    : null;

$websiteUrl = $artist !== null
    ? artistProfileLink($artist['sitio_web_url'] ?? null)
    : null;
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
        content="Perfil, especialidades y trabajos de artista en Tinta Viva."
    >

    <meta name="theme-color" content="#060405">

    <title><?= e($pageTitle) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,500&family=Montserrat:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/site.css">
    <link rel="stylesheet" href="../assets/css/artistas.css">
    <script src="../assets/js/site.js" defer></script>
<link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>">
</head>

<body class="artist-profile-page">
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
    <?php if ($loadError !== null || $artist === null): ?>
        <section class="profile-unavailable">
            <p class="eyebrow">EL ARCHIVO PERMANECE CERRADO</p>

            <h1>
                <?= $loadError !== null
                    ? 'No pudimos invocar este perfil'
                    : 'Este artista no habita aquí' ?>
            </h1>

            <p>
                <?= e(
                    $loadError
                    ?? 'El perfil solicitado no existe o ya no se encuentra disponible.'
                ) ?>
            </p>

            <a class="button button--blood" href="index.php">
                Volver a los artistas
            </a>
        </section>
    <?php else: ?>
        <section class="profile-hero">
            <div class="profile-hero__portrait reveal">
                <?php if ($profileImage !== null): ?>
                    <img
                        src="<?= e($profileImage) ?>"
                        alt="Retrato de <?= e($artist['nombre_publico']) ?>"
                    >
                <?php else: ?>
                    <div class="profile-hero__fallback" aria-hidden="true">†</div>
                <?php endif ?>

                <div class="profile-hero__portrait-shade"></div>
                <span class="profile-hero__portrait-mark" aria-hidden="true">V</span>
            </div>

            <div class="profile-hero__content reveal">
                <a class="profile-back" href="index.php">
                    ← Volver al archivo de artistas
                </a>

                <p class="eyebrow">MAESTRO DE LA TINTA</p>

                <p class="profile-hero__real-name">
                    <?= e(trim($artist['nombre'] . ' ' . $artist['apellidos'])) ?>
                </p>

                <h1><?= e($artist['nombre_publico']) ?></h1>

                <div class="profile-hero__specialties">
                    <?php if ($specialties !== []): ?>
                        <?php foreach ($specialties as $specialty): ?>
                            <span><?= e($specialty['nombre']) ?></span>
                        <?php endforeach ?>
                    <?php else: ?>
                        <span>Estilo en construcción</span>
                    <?php endif ?>
                </div>

                <p class="profile-hero__biography">
                    <?= e(
                        $artist['biografia']
                        ?: 'Una visión propia de la tinta, el símbolo y la piel.'
                    ) ?>
                </p>

                <div class="profile-hero__metrics">
                    <div>
                        <strong>
                            <?= (int) $artist['cantidad_calificaciones'] > 0
                                ? '★ ' . e(number_format(
                                    (float) $artist['promedio'],
                                    1,
                                    ',',
                                    '.'
                                ))
                                : '—' ?>
                        </strong>

                        <span>
                            <?= (int) $artist['cantidad_calificaciones'] > 0
                                ? e($artist['cantidad_calificaciones']) . ' reseñas'
                                : 'Sin reseñas' ?>
                        </span>
                    </div>

                    <div>
                        <strong><?= e($artist['total_trabajos']) ?></strong>
                        <span>Obras publicadas</span>
                    </div>

                    <div>
                        <strong><?= e(count($certifications)) ?></strong>
                        <span>Certificaciones</span>
                    </div>
                </div>

                <div class="profile-hero__actions">
                    <a class="button button--blood" href="../cotizaciones/?artista=<?= rawurlencode((string) ($artist['slug'] ?: $artist['id_artista'])) ?>">
                        Solicitar cotización
                    </a>

                    <?php if ($websiteUrl !== null): ?>
                        <a
                            class="ghost-link"
                            href="<?= e($websiteUrl) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Portafolio de ejemplo ↗
                        </a>
                    <?php endif ?>
                </div>
            </div>
        </section>

        <section class="profile-section profile-portfolio">
            <div class="profile-section__heading reveal">
                <div>
                    <p class="eyebrow">OBRAS SOBRE LA PIEL</p>
                    <h2>Portafolio del artista</h2>
                </div>

                <p>
                    Trabajos publicados con autorización de sus clientes.
                    Cada pieza fue diseñada para una historia distinta.
                </p>
            </div>

            <?php if ($portfolio === []): ?>
                <div class="profile-empty">
                    El portafolio todavía espera su primera obra pública.
                </div>
            <?php else: ?>
                <div class="profile-work-grid">
                    <?php foreach ($portfolio as $position => $work): ?>
                        <?php $workImage = artistProfileImage($work['imagen_url'] ?? null); ?>

                        <article class="profile-work reveal">
                            <div class="profile-work__image">
                                <?php if ($workImage !== null): ?>
                                    <img
                                        src="<?= e($workImage) ?>"
                                        alt="<?= e(
                                            $work['texto_alternativo']
                                            ?: $work['titulo']
                                        ) ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <span aria-hidden="true">✦</span>
                                <?php endif ?>

                                <strong>
                                    <?= e(str_pad(
                                        (string) ($position + 1),
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    )) ?>
                                </strong>
                            </div>

                            <div class="profile-work__body">
                                <p><?= e($work['categoria']) ?></p>
                                <h3><?= e($work['titulo']) ?></h3>
                                <span><?= e(artistProfileYear($work['fecha_realizacion'])) ?></span>
                            </div>
                        </article>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>

        <section class="profile-section profile-credentials">
            <div class="profile-section__heading reveal">
                <div>
                    <p class="eyebrow">OFICIO Y DISCIPLINA</p>
                    <h2>Certificaciones</h2>
                </div>

                <p>
                    Formación técnica que respalda una práctica responsable,
                    precisa y segura.
                </p>
            </div>

            <?php if ($certifications === []): ?>
                <div class="profile-empty">
                    No hay certificaciones públicas registradas.
                </div>
            <?php else: ?>
                <div class="credential-list">
                    <?php foreach ($certifications as $position => $certification): ?>
                        <article class="credential reveal">
                            <span class="credential__number">
                                <?= e(str_pad(
                                    (string) ($position + 1),
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                )) ?>
                            </span>

                            <div>
                                <h3><?= e($certification['nombre']) ?></h3>
                                <p><?= e($certification['institucion']) ?></p>
                            </div>

                            <p class="credential__date">
                                Emitida en
                                <?= e(artistProfileYear($certification['fecha_emision'])) ?>
                            </p>
                        </article>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>

        <section class="profile-section profile-reviews">
            <div class="profile-section__heading reveal">
                <div>
                    <p class="eyebrow">VOCES QUE PERMANECEN</p>
                    <h2>Experiencias de clientes</h2>
                </div>

                <p>
                    Opiniones publicadas después de sesiones finalizadas.
                </p>
            </div>

            <?php if ($reviews === []): ?>
                <div class="profile-empty">
                    Este artista todavía no tiene comentarios publicados.
                </div>
            <?php else: ?>
                <div class="profile-review-grid">
                    <?php foreach ($reviews as $review): ?>
                        <blockquote class="profile-review reveal">
                            <div class="profile-review__stars" aria-label="<?= e($review['puntuacion']) ?> de 5 estrellas">
                                <?= e(str_repeat('★', (int) $review['puntuacion'])) ?>
                            </div>

                            <p>“<?= e($review['comentario']) ?>”</p>

                            <footer>
                                <strong><?= e($review['autor']) ?></strong>
                                <span>Cliente verificado</span>
                            </footer>
                        </blockquote>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>

        <section class="profile-invitation reveal">
            <span aria-hidden="true">✦</span>
            <p class="eyebrow">TU HISTORIA PUEDE SER LA SIGUIENTE</p>
            <h2>Convierte una idea oscura en una marca eterna.</h2>
            <a class="button button--blood" href="../cotizaciones/?artista=<?= rawurlencode((string) ($artist['slug'] ?: $artist['id_artista'])) ?>">
                Iniciar mi proyecto
            </a>
        </section>
    <?php endif ?>
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
