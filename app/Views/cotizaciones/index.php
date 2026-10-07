<?php
declare(strict_types=1);

$isClient = ($account['nombre_rol'] ?? null) === 'cliente';
$accountLink = accountAreaLink($account ?? null, '../');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Solicita una cotización personalizada para tu próximo tatuaje en Tinta Viva.">
    <meta name="theme-color" content="#060405">
    <title>El pacto de tinta · Cotizaciones · Tinta Viva</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,500;1,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/site.css">
    <link rel="stylesheet" href="../assets/css/cotizaciones.css">
    <script src="../assets/js/site.js" defer></script>
    <script src="../assets/js/cotizaciones.js" defer></script>
<link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>">
<?php responsiveAssets(); ?>
</head>

<body class="quote-page">
<div class="cursor-glow" aria-hidden="true"></div>
<a class="skip-link" href="#formulario-cotizacion">Saltar al formulario</a>

<?php if (($account['nombre_rol']??null)==='cliente'): $clientEndpoint='../index.php';$publicPrefix='../';require __DIR__.'/../cliente-navegacion.php'; else: ?>
<header class="site-header" id="siteHeader">
    <div class="nav-shell">
        <a class="brand" href="../index.php" aria-label="Tinta Viva, inicio">
            <svg class="brand__symbol" viewBox="0 0 64 64" aria-hidden="true">
                <path d="M32 4 42 20 60 24 46 37 49 57 32 48 15 57 18 37 4 24 22 20Z"/>
                <circle cx="32" cy="31" r="9"/>
                <path d="M32 12v38M13 31h38"/>
            </svg>
            <span class="brand__text"><strong>TINTA VIVA</strong><small>ARTE QUE DEJA HUELLA</small></span>
        </a>

        <button class="menu-button" id="menuButton" type="button" aria-controls="mainNav" aria-expanded="false" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" id="mainNav" aria-label="Navegación principal">
            <a href="../index.php">Inicio</a>
            <a href="../artistas/">Artistas</a>
            <a href="../galeria/">Galería</a>
            <a href="index.php" aria-current="page">Cotizar</a>
        </nav>

        <a class="button button--login" href="<?= e($accountLink['url']) ?>">
            <?= e($accountLink['label']) ?>
        </a>
    </div>
    <div class="blood-edge" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
</header>
<?php endif ?>

<main>
    <section class="quote-hero">
        <div class="quote-hero__fog" aria-hidden="true"></div>
        <div class="quote-hero__copy reveal">
            <p class="eyebrow">EL PRIMER JURAMENTO</p>
            <h1>El pacto<br><span>de tinta</span></h1>
            <p class="quote-hero__lead">Toda marca eterna comienza como un secreto. Entréganos la idea; nuestros artistas le darán forma, tiempo y valor.</p>
            <a class="quote-scroll" href="#formulario-cotizacion"><span>Descender al ritual</span><b aria-hidden="true">↓</b></a>
        </div>

        <div class="quote-hero__seal" aria-hidden="true">
            <div class="quote-hero__seal-ring"><span>TV</span></div>
            <p>IDEA · CARNE · ETERNIDAD</p>
        </div>

        <ol class="quote-hero__steps" aria-label="Proceso de cotización">
            <li><span>01</span><b>Confiesa</b><small>Tu idea y referencias</small></li>
            <li><span>02</span><b>Interpretamos</b><small>Estilo, tiempo y artista</small></li>
            <li><span>03</span><b>Confirmamos</b><small>Cita definitiva por correo</small></li>
        </ol>
    </section>

    <section class="quote-ritual" id="formulario-cotizacion">
        <div class="quote-ritual__intro reveal">
            <p class="eyebrow">SOLICITUD DE COTIZACIÓN</p>
            <h2>Describe la marca que aún no existe</h2>
            <p>No necesitas tener todo resuelto. Cuanto más nos cuentes, mejor podremos calcular el trabajo y elegir al artista indicado.</p>
        </div>

        <?php if ($notice !== null): ?>
            <div class="quote-success reveal" role="status">
                <span class="quote-success__number">#<?= e($submittedQuoteId) ?></span>
                <div><p class="eyebrow">EL PACTO FUE SELLADO</p><h2>Tu solicitud entró al archivo</h2><p><?= e($notice) ?></p></div>
                <?php if ($isClient): ?><a href="../panel/mis-cotizaciones.php">Seguir mi solicitud →</a><?php endif ?>
            </div>
        <?php endif ?>

        <?php if ($error !== null): ?>
            <div class="quote-alert" role="alert"><b aria-hidden="true">!</b><span><?= e($error) ?></span></div>
        <?php endif ?>

        <form class="quote-form" method="post" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <label class="quote-honeypot" aria-hidden="true">Sitio web<input name="sitio_web" tabindex="-1" autocomplete="off"></label>

            <section class="quote-form__chapter reveal" aria-labelledby="capitulo-contacto">
                <header><span>I</span><div><p>EL NOMBRE DEL INVOCANTE</p><h3 id="capitulo-contacto">¿Cómo llegamos hasta ti?</h3></div></header>
                <div class="quote-fields">
                    <label><span>Nombre completo *</span><input name="nombre_contacto" maxlength="180" autocomplete="name" value="<?= e($form['nombre_contacto'] ?? '') ?>" required></label>
                    <label><span>Correo de tu cuenta *</span><input type="email" name="correo_contacto" maxlength="254" autocomplete="email" value="<?= e($form['correo_contacto'] ?? '') ?>" readonly required><small>La confirmación definitiva de la cita llegará a este correo.</small></label>
                    <label><span>Teléfono</span><input type="tel" name="telefono_contacto" maxlength="25" autocomplete="tel" value="<?= e($form['telefono_contacto'] ?? '') ?>" placeholder="Ej. 8888-8888"></label>
                    <div class="quote-contact-policy"><b>Todo el seguimiento ocurre en tu cuenta</b><p>La cotización y sus cambios no generan correos. Solo recibirás un correo cuando el administrador confirme una cita definitiva.</p></div>
                </div>
            </section>

            <section class="quote-form__chapter reveal" aria-labelledby="capitulo-obra">
                <header><span>II</span><div><p>LA VISIÓN</p><h3 id="capitulo-obra">Dale palabras a la imagen</h3></div></header>
                <div class="quote-fields">
                    <label><span>Estilo principal *</span><select name="id_categoria" required><option value="">Selecciona un estilo</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category['id_categoria']) ?>" <?= (string) ($form['id_categoria'] ?? '') === (string) $category['id_categoria'] ? 'selected' : '' ?>><?= e($category['nombre']) ?></option>
                        <?php endforeach ?>
                    </select></label>
                    <label><span>Artista preferido</span><select name="id_artista"><option value="">Que el estudio elija por mí</option>
                        <?php foreach ($artists as $quoteArtist): ?>
                            <option value="<?= e($quoteArtist['id_artista']) ?>" <?= (string) ($form['id_artista'] ?? '') === (string) $quoteArtist['id_artista'] ? 'selected' : '' ?>><?= e($quoteArtist['nombre_publico']) ?><?= $quoteArtist['especialidades'] ? ' · ' . e($quoteArtist['especialidades']) : '' ?></option>
                        <?php endforeach ?>
                    </select></label>
                    <label class="quote-fields__wide"><span>La historia de tu idea *</span><textarea name="descripcion_idea" rows="7" minlength="20" maxlength="5000" data-character-count required placeholder="Símbolos, atmósfera, referencias, significado y cualquier detalle que no deba perderse..."><?= e($form['descripcion_idea'] ?? '') ?></textarea><small><b data-character-output>0</b> / 5000 caracteres</small></label>
                </div>
            </section>

            <section class="quote-form__chapter reveal" aria-labelledby="capitulo-cuerpo">
                <header><span>III</span><div><p>EL LIENZO</p><h3 id="capitulo-cuerpo">Medidas del ritual</h3></div></header>
                <div class="quote-fields quote-fields--measurements">
                    <label><span>Zona del cuerpo *</span><input name="zona_cuerpo" maxlength="100" value="<?= e($form['zona_cuerpo'] ?? '') ?>" placeholder="Antebrazo, espalda, costillas..." required></label>
                    <label><span>Tamaño aproximado</span><input name="tamano_descripcion" maxlength="120" value="<?= e($form['tamano_descripcion'] ?? '') ?>" placeholder="Pequeño, media manga..."></label>
                    <label><span>Ancho en cm</span><input type="number" name="ancho_cm" min="0.1" max="250" step="0.1" value="<?= e($form['ancho_cm'] ?? '') ?>" placeholder="12"></label>
                    <label><span>Alto en cm</span><input type="number" name="alto_cm" min="0.1" max="250" step="0.1" value="<?= e($form['alto_cm'] ?? '') ?>" placeholder="18"></label>
                    <label><span>Fecha ideal</span><input type="date" name="fecha_preferida" min="<?= e(date('Y-m-d')) ?>" value="<?= e($form['fecha_preferida'] ?? '') ?>"></label>
                    <label class="quote-color-choice"><input type="checkbox" name="a_color" value="1" <?= !empty($form['a_color']) ? 'checked' : '' ?>><span aria-hidden="true"></span><b>Quiero explorar tinta a color</b></label>
                </div>
            </section>

            <section class="quote-form__chapter reveal" aria-labelledby="capitulo-referencias">
                <header><span>IV</span><div><p>LOS PRESAGIOS</p><h3 id="capitulo-referencias">Añade referencias visuales</h3></div></header>
                <label class="quote-upload" data-upload-zone>
                    <input type="file" name="referencias[]" accept="image/jpeg,image/png,image/webp" multiple data-file-input>
                    <span class="quote-upload__sigil" aria-hidden="true">✦</span>
                    <strong>Arrastra imágenes o abre tu archivo</strong>
                    <small>Hasta 3 imágenes · JPG, PNG o WEBP · máximo 6 MB cada una</small>
                </label>
                <div class="quote-previews" data-file-previews aria-live="polite"></div>
            </section>

            <footer class="quote-form__seal reveal">
                <label class="quote-consent"><input type="checkbox" name="acepta_contacto" value="1" required><span aria-hidden="true"></span><b>Autorizo el uso de mis datos para gestionar esta solicitud y recibir por correo la confirmación de una cita definitiva. *</b></label>
                <p>Enviar la solicitud no reserva una cita ni obliga a aceptar la cotización.</p>
                <button class="button button--blood" type="submit">Sellar la solicitud <span aria-hidden="true">†</span></button>
            </footer>
        </form>
    </section>

    <section class="quote-aftercare reveal">
        <p class="eyebrow">DESPUÉS DEL ENVÍO</p>
        <h2>La respuesta no será automática.<br><em>Será humana.</em></h2>
        <div><p><b>01</b> Revisamos la complejidad, ubicación y medidas.</p><p><b>02</b> Publicamos precio, artista y condiciones dentro de tu cuenta.</p><p><b>03</b> Si aceptas, el administrador agenda y confirma la cita.</p><p><b>04</b> Solo la cita confirmada genera un correo real.</p></div>
    </section>
</main>

<footer class="site-footer">
    <a class="brand" href="../index.php"><span class="brand__text"><strong>TINTA VIVA</strong><small>ARTE QUE DEJA HUELLA</small></span></a>
    <p>Personas reales. Historias eternas.</p><p>© <?= date('Y') ?> Tinta Viva</p>
</footer>
</body>
</html>
