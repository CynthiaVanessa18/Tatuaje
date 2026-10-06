<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/Core/bootstrap.php';
$clientEndpoint = 'index.php';
$publicPrefix = '';
if (in_array($_GET['section'] ?? '', ['citas', 'tienda', 'carrito','membresias','cuenta','tarjetas'], true)) {
    $requiredRole = 'cliente';
    try {
        require __DIR__ . '/../app/Controllers/RoleController.php';
        require __DIR__ . '/../app/Views/role-home.php';
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        http_response_code(503);
        require __DIR__ . '/../app/Views/unavailable.php';
    }
    exit;
}
try {
    $account=currentAccount();
} catch (PDOException $exception) {
    error_log('Sesión del sitio público: '.$exception->getMessage());$account=null;
}
$accountLink=accountAreaLink($account);
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
        content="Tinta Viva: estudio de tatuajes, artistas, galería, cuidados y cotizaciones."
    >

    <meta name="theme-color" content="#060405">

    <title>Tinta Viva · El arte sobrevive a la carne</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,500&family=Montserrat:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/css/site.css"
    >

    <script
        src="assets/js/site.js"
        defer
    ></script>
<?php if (($_SESSION['role']??'')==='cliente'): ?><link rel="stylesheet" href="assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/assets/css/cliente-navegacion.css')) ?>"><?php endif ?>
</head>
<?php /* La cuenta de cliente comparte la misma navegación en todas las páginas. */ ?>

<body>
<div class="cursor-glow" aria-hidden="true"></div>

<a class="skip-link" href="#contenido">
    Saltar al contenido
</a>

<?php if (($_SESSION['role']??'')==='cliente'): require __DIR__.'/../app/Views/cliente-navegacion.php'; else: ?>
<header class="site-header" id="siteHeader">
    <div class="nav-shell">
        <a class="brand" href="index.php" aria-label="Tinta Viva, inicio">
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
            <a href="#inicio" aria-current="page">Inicio</a>
            <a href="artistas/">Artistas</a>
            <a href="galeria/">Galería</a>
            <a href="cotizaciones/">Cotizar</a>
            <a href="#cuidados">Cuidados</a>
            <a href="index.php?section=membresias">Membresía</a>
            <a href="#calificaciones_artistas">Calificaciones de Artistas</a>

            <a href="index.php?section=tienda">Tienda</a>
            <a href="index.php?section=citas">Mis citas</a>
        </nav>

        <?php if (!empty($_SESSION['account'])): ?>
            <a class="button button--login" href="<?= e($accountLink['url']) ?>"><?= e($accountLink['label']) ?></a>
            <form class="session-actions" method="post" action="auth/logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <button class="button button--login" type="submit">Cerrar sesión</button>
            </form>
        <?php else: ?>
            <a class="button button--login" href="auth/login.php">
                Ingresar
            </a>
        <?php endif ?>
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
    <section class="hero" id="inicio">
        <div class="hero__fog hero__fog--one" aria-hidden="true"></div>
        <div class="hero__fog hero__fog--two" aria-hidden="true"></div>

        <svg class="bat bat--one" viewBox="0 0 100 45" aria-hidden="true">
            <path d="M50 36C43 23 31 15 4 12c7 8 10 16 8 27 11-7 20-8 38 3 18-11 27-10 38-3-2-11 1-19 8-27-27 3-39 11-46 24Z"/>
        </svg>

        <svg class="bat bat--two" viewBox="0 0 100 45" aria-hidden="true">
            <path d="M50 36C43 23 31 15 4 12c7 8 10 16 8 27 11-7 20-8 38 3 18-11 27-10 38-3-2-11 1-19 8-27-27 3-39 11-46 24Z"/>
        </svg>

        <div class="hero__content">
            <p class="eyebrow">
                ESTUDIO DE TATUAJES · COSTA RICA
            </p>

            <h1>
                <span>El arte</span>
                <span>sobrevive</span>
                <span class="blood-word">a la carne.</span>
            </h1>

            <p class="hero__phrase">
                Cada marca guarda un secreto.
            </p>

            <p class="hero__description">
                Diseños nacidos entre sombras, tinta y ritual.
                Transformamos tus historias en piezas creadas
                para perseguirte toda la vida.
            </p>

            <div class="hero__actions">
                <a class="button button--blood" href="cotizaciones/">
                    Cotiza tu ritual
                    <span aria-hidden="true">†</span>
                </a>

                <a class="ghost-link" href="galeria/">
                    Explorar trabajos
                </a>
            </div>

            <div class="hero__warning">
                <span aria-hidden="true">✦</span>
                <p>
                    No todos los fantasmas viven en casas.
                    Algunos viven bajo la piel.
                </p>
            </div>
        </div>

        <div class="scroll-indicator" aria-hidden="true">
            <span>DESCENDER</span>
            <i></i>
        </div>
    </section>

    <div class="torn-divider" aria-hidden="true"></div>

    <section class="works section reveal" id="galeria">
        <div class="section-heading">
            <div>
                <p class="eyebrow">TRABAJOS RECIENTES</p>
                <h2>Marcas para la eternidad</h2>
            </div>

            <p class="section-heading__text">
                Cada obra encierra una historia que ya no puede ser borrada.
            </p>
        </div>

        <div class="tarot-grid">
            <article class="tarot-card">
                <div class="tarot-card__corners" aria-hidden="true"></div>

                <header>
                    <span>I</span>
                    <p>BLACKWORK</p>
                </header>

                <div class="tarot-card__art">
                    <svg viewBox="0 0 240 280" aria-hidden="true">
                        <path d="M65 100c0-48 24-75 55-75s55 27 55 75c0 35-13 55-32 66v43h-46v-43c-19-11-32-31-32-66Z"/>
                        <path d="M80 105c8-15 25-17 34-2-8 17-27 18-34 2Zm46-2c9-15 26-13 34 2-7 16-26 15-34-2Z"/>
                        <path d="m120 112-10 25h20Z"/>
                        <path d="M96 169h48M102 180h36M108 191h24"/>
                        <path d="M45 72 18 44M195 72l27-28M54 200l-31 36M186 200l31 36"/>
                        <circle cx="120" cy="118" r="89"/>
                        <circle cx="120" cy="118" r="104"/>
                    </svg>
                </div>

                <footer>
                    <p>El peso de lo eterno</p>
                    <span>Ver obra →</span>
                </footer>
            </article>

            <article class="tarot-card tarot-card--featured">
                <div class="tarot-card__corners" aria-hidden="true"></div>

                <header>
                    <span>II</span>
                    <p>REALISMO OSCURO</p>
                </header>

                <div class="tarot-card__art">
                    <svg viewBox="0 0 240 280" aria-hidden="true">
                        <circle cx="120" cy="114" r="78"/>
                        <path d="M120 25v178M31 114h178"/>
                        <path d="M62 114c16-29 40-44 58-44s42 15 58 44c-16 29-40 44-58 44s-42-15-58-44Z"/>
                        <circle cx="120" cy="114" r="26"/>
                        <circle cx="120" cy="114" r="9"/>
                        <path d="M86 205c8-18 20-27 34-27s26 9 34 27c-8 23-20 36-34 50-14-14-26-27-34-50Z"/>
                        <path d="m93 203 27 52 27-52"/>
                    </svg>
                </div>

                <footer>
                    <p>El ojo que nunca duerme</p>
                    <span>Ver obra →</span>
                </footer>
            </article>

            <article class="tarot-card">
                <div class="tarot-card__corners" aria-hidden="true"></div>

                <header>
                    <span>III</span>
                    <p>LÍNEA FINA</p>
                </header>

                <div class="tarot-card__art">
                    <svg viewBox="0 0 240 280" aria-hidden="true">
                        <circle cx="120" cy="118" r="82"/>
                        <path d="M120 35c-17 16-24 34-19 53 8-14 20-20 36-18-15 7-23 18-24 34"/>
                        <path d="M120 104c-39-29-67 4-48 35 11 17 29 25 48 44 19-19 37-27 48-44 19-31-9-64-48-35Z"/>
                        <path d="M120 183v67M91 217l29-34 29 34"/>
                        <path d="M53 66 28 42M187 66l25-24M53 171l-25 25M187 171l25 25"/>
                    </svg>
                </div>

                <footer>
                    <p>Una rosa para los muertos</p>
                    <span>Ver obra →</span>
                </footer>
            </article>
        </div>
    </section>

    <section class="artists section reveal" id="artistas">
        <div class="artists__visual" aria-hidden="true">
            <div class="artists__moon"></div>

            <svg viewBox="0 0 400 520">
                <path d="M200 35 330 215 200 485 70 215Z"/>
                <circle cx="200" cy="235" r="105"/>
                <circle cx="200" cy="235" r="72"/>
                <path d="M200 130v210M95 235h210"/>
                <path d="M140 235c17-31 39-47 60-47s43 16 60 47c-17 31-39 47-60 47s-43-16-60-47Z"/>
                <circle cx="200" cy="235" r="18"/>
            </svg>
        </div>

        <div class="artists__content">
            <p class="eyebrow">MAESTROS DE LA TINTA</p>

            <h2>
                Conoce a quienes dan vida a tus pesadillas.
            </h2>

            <p>
                Cada artista posee una técnica, una visión y una forma
                distinta de convertir tus ideas en una marca permanente.
            </p>

            <ul>
                <li>Perfiles y especialidades</li>
                <li>Portafolios individuales</li>
                <li>Disponibilidad por artista</li>
                <li>Experiencia y certificaciones</li>
            </ul>

            <a class="ghost-link" href="artistas/">
                Descubrir artistas →
            </a>
        </div>
    </section>

    <section class="ritual section reveal" id="cuidados">
        <div class="section-heading">
            <div>
                <p class="eyebrow">EL RITUAL</p>
                <h2>Antes, durante y después</h2>
            </div>
        </div>

        <div class="ritual-grid">
            <article>
                <span>01</span>
                <h3>La invocación</h3>
                <p>
                    Cuéntanos tu idea, estilo, tamaño y zona del cuerpo
                    para preparar una cotización.
                </p>
            </article>

            <article>
                <span>02</span>
                <h3>La marca</h3>
                <p>
                    Elige a tu artista, reserva una fecha y presencia
                    cómo la idea toma forma sobre tu piel.
                </p>
            </article>

            <article>
                <span>03</span>
                <h3>La protección</h3>
                <p>
                    Sigue las recomendaciones de cuidado para que la
                    obra cicatrice y conserve toda su intensidad.
                </p>
            </article>
        </div>
    </section>

    <section class="summoning reveal" id="cotizar">
        <div class="summoning__ornament" aria-hidden="true">✦</div>

        <p class="eyebrow">TU PRÓXIMA HISTORIA</p>

        <h2>¿Te atreves a llevarla contigo?</h2>

        <p>
            Cuéntanos la idea, el estilo y el lugar de la piel.
            El estudio convertirá tu visión en una propuesta real.
        </p>

        <a class="button button--blood" href="cotizaciones/">
            Comenzar el pacto
            <span aria-hidden="true">†</span>
        </a>
    </section>
</main>

<footer class="site-footer">
    <a class="brand" href="index.php">
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
