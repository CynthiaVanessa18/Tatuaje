<?php
declare(strict_types=1);

$statusLabels = [
    'solicitada' => 'Recibida',
    'en_revision' => 'En revisión',
    'enviada' => 'Respuesta lista',
    'aceptada' => 'Aceptada',
    'rechazada' => 'No viable',
    'vencida' => 'Vencida',
    'cancelada' => 'Cancelada',
];

function clientDashboardDate(string $value, string $format = 'd/m/Y · H:i'): string
{
    return (new DateTimeImmutable($value, new DateTimeZone('America/Costa_Rica')))
        ->format($format);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080506">
    <title>Mi espacio · Tinta Viva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-artistas.css">
    <link rel="stylesheet" href="../assets/css/cliente-cotizaciones.css">
    <link rel="stylesheet" href="../assets/css/cliente-panel.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-panel.css')) ?>">
<link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>">
<?php responsiveAssets(); ?>
</head>

<body class="client-shared-layout admin-artists client-quotes client-home">
<?php $clientEndpoint='../index.php';$publicPrefix='../';require __DIR__.'/../cliente-navegacion.php'; ?>

<main class="admin-main client-home__main">
    <section class="client-ritual-hero" aria-labelledby="client-hero-title">
        <div class="client-ritual-hero__art" aria-hidden="true"></div>
        <div class="client-ritual-hero__veil" aria-hidden="true"></div>
        <div class="client-ritual-hero__rail" aria-hidden="true">
            <span>ARCHIVO PRIVADO</span><i></i><small>CR · <?= date('Y') ?></small>
        </div>
        <div class="client-ritual-hero__copy">
            <p class="client-kicker"><span>✦</span> CUADERNO DE PIEL · TINTA VIVA</p>
            <h1 id="client-hero-title"><span>Tu historia</span><strong>merece tinta.</strong></h1>
            <p class="client-ritual-hero__welcome">Bienvenido, <em><?= e($clientDisplayName) ?></em></p>
            <p class="client-ritual-hero__lead">Este es el lugar donde una idea deja de ser imaginada y empieza a convertirse en una marca hecha para sobrevivirte.</p>
            <div class="client-ritual-hero__actions">
                <a class="client-action client-action--blood" href="../cotizaciones/"><span>Iniciar mi próxima obra</span><b aria-hidden="true">†</b></a>
                <a class="client-action client-action--line" href="../galeria/"><span>Entrar al archivo visual</span><b aria-hidden="true">↗</b></a>
            </div>
        </div>
        <div class="client-ritual-hero__caption" aria-hidden="true">
            <span>I</span><p>La piel recuerda<br>lo que el tiempo intenta borrar.</p>
        </div>
    </section>

    <?php if ($profileMissing): ?><div class="admin-notice admin-notice--warning"><span>!</span>Tu cuenta está activa, pero todavía no tiene un perfil de cliente vinculado. Solicita ayuda al administrador.</div><?php endif ?>

    <?php if ($pendingRatingAppointments): ?>
    <section class="client-home-section">
        <header><div><p class="client-kicker">DESPUÉS DE TU SESIÓN</p><h2>Pendiente de calificar</h2></div><a href="mis-calificaciones.php">Calificar mi experiencia →</a></header>
        <p>Tienes <?= e(count($pendingRatingAppointments)) ?> sesión(es) finalizada(s) sin calificar. Cuéntanos cómo fue tu experiencia con el artista.</p>
    </section>
    <?php endif ?>
    <section class="client-home-stats" aria-label="Resumen personal">
        <article><span>I</span><div><b><?= e($quoteSummary['total']) ?></b><p>Ideas entregadas</p></div><small>Archivo</small></article>
        <article><span>II</span><div><b><?= e($quoteSummary['en_proceso']) ?></b><p>En el taller</p></div><small>Proceso</small></article>
        <article><span>III</span><div><b><?= e($quoteSummary['respondidas']) ?></b><p>Propuestas listas</p></div><small>Respuesta</small></article>
        <article><span>IV</span><div><b><?= e(count($appointments)) ?></b><p>Rituales próximos</p></div><small>Agenda</small></article>
    </section>

    <section class="client-manifesto" aria-label="Manifiesto Tinta Viva">
        <div class="client-manifesto__mark" aria-hidden="true">†</div>
        <p>NO ELEGIMOS UNA IMAGEN.<br><span>ELEGIMOS LO QUE QUEREMOS RECORDAR.</span></p>
        <div class="client-manifesto__seal" aria-hidden="true"><i></i><span>TV</span></div>
    </section>

    <section class="client-portals">
        <header>
            <div><p class="client-kicker"><span>✦</span> EXPLORA EL ESTUDIO</p><h2>No mires solamente.<br><em>Encuentra tu próxima obsesión.</em></h2></div>
            <p>Recorre técnicas, símbolos y artistas. Tu sesión permanece activa mientras construyes la idea que llevarás en la piel.</p>
        </header>
        <div class="client-portals__grid">
            <a href="../artistas/" class="client-portal client-portal--artists">
                <img src="../assets/images/artistas/nocturna.png" alt="" loading="lazy">
                <span class="client-portal__shade" aria-hidden="true"></span><b class="client-portal__number">01</b>
                <div class="client-portal__copy"><small>LOS CREADORES</small><h3>Conoce la mano detrás de la marca.</h3><p>Perfiles, lenguajes visuales y la historia de cada artista.</p><strong>Descubrir artistas <i>↗</i></strong></div>
            </a>
            <a href="../galeria/" class="client-portal client-portal--gallery">
                <img src="../assets/images/galeria/08-angel-caido.webp" alt="" loading="lazy">
                <span class="client-portal__shade" aria-hidden="true"></span><b class="client-portal__number">02</b>
                <div class="client-portal__copy"><small>EL ARCHIVO DE LA PIEL</small><h3>Galería de marcas eternas.</h3><p>Obras reales para despertar algo que todavía no tiene nombre.</p><strong>Abrir el archivo <i>↗</i></strong></div>
            </a>
            <a href="../cotizaciones/" class="client-portal client-portal--quote">
                <img src="../assets/images/galeria/10-corazon-vitrales.webp" alt="" loading="lazy">
                <span class="client-portal__shade" aria-hidden="true"></span><b class="client-portal__number">03</b>
                <div class="client-portal__copy"><small>EL PRIMER JURAMENTO</small><h3>Convierte una visión en proyecto.</h3><p>Cuéntanos la historia, el lugar de la piel y el estilo. Nosotros trazamos el camino.</p><strong>Crear cotización <i>†</i></strong></div>
            </a>
        </div>
    </section>

    <div class="client-home-grid">
        <section class="client-home-section" id="proximas-citas">
            <header><div><p class="client-kicker"><span>✦</span> CALENDARIO</p><h2>Tu próxima sesión</h2></div><span><?= e(count($appointments)) ?></span></header>
            <?php if ($appointments === []): ?><div class="client-home-empty"><b>◷</b><p>No tienes citas próximas.</p><small>Cuando una cotización sea aceptada, el estudio podrá agendarla.</small></div><?php else: ?><div class="client-appointments"><?php foreach ($appointments as $appointment): ?><article><time datetime="<?= e($appointment['fecha_hora_inicio']) ?>"><b><?= e(clientDashboardDate($appointment['fecha_hora_inicio'], 'd')) ?></b><span><?= e(strtoupper(clientDashboardDate($appointment['fecha_hora_inicio'], 'M'))) ?></span></time><div><small><?= e(clientDashboardDate($appointment['fecha_hora_inicio'], 'H:i')) ?> – <?= e(clientDashboardDate($appointment['fecha_hora_fin'], 'H:i')) ?></small><h3><?= e($appointment['artista']) ?></h3><p><?= e(label($appointment['estado'])) ?></p></div></article><?php endforeach ?></div><?php endif ?>
        </section>

        <section class="client-home-section">
            <header><div><p class="client-kicker"><span>✦</span> IDEAS EN PROCESO</p><h2>Tus últimos trazos</h2></div><a href="mis-cotizaciones.php">Ver archivo →</a></header>
            <?php if ($recentQuotes === []): ?><div class="client-home-empty"><b>†</b><p>Aún no has entregado ninguna idea.</p><a href="../cotizaciones/">Crear la primera</a></div><?php else: ?><div class="client-recent-quotes"><?php foreach ($recentQuotes as $quoteItem): ?><a href="mis-cotizaciones.php?id=<?= e($quoteItem['id_cotizacion']) ?>"><div><small><?= e($quoteItem['categoria']) ?> · <?= e($quoteItem['zona_cuerpo']) ?></small><h3>Solicitud #<?= e($quoteItem['id_cotizacion']) ?></h3><p><?= e($quoteItem['descripcion_idea']) ?></p></div><span class="client-status client-status--<?= e($quoteItem['estado']) ?>"><?= e($statusLabels[$quoteItem['estado']] ?? $quoteItem['estado']) ?></span></a><?php endforeach ?></div><?php endif ?>
        </section>
    </div>
</main>
</body>
</html>
