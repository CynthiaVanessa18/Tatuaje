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
    <link rel="stylesheet" href="../assets/css/cliente-panel.css">
<link rel="stylesheet" href="../assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../../public/assets/css/cliente-navegacion.css')) ?>">
<?php responsiveAssets(); ?>
</head>

<body class="client-shared-layout admin-artists client-quotes client-home">
<?php $clientEndpoint='../index.php';$publicPrefix='../';require __DIR__.'/../cliente-navegacion.php'; ?>

<main class="admin-main client-home__main">
    <section class="client-welcome">
        <div class="client-welcome__copy">
            <p class="admin-eyebrow">TU ESPACIO PRIVADO</p>
            <h1>Bienvenido,<br><span><?= e($clientDisplayName) ?></span></h1>
            <p>Aquí conviven tus ideas, respuestas y próximas citas. La galería y los artistas siguen a un paso, sin abandonar tu sesión.</p>
            <div><a class="admin-button admin-button--primary" href="../cotizaciones/">Crear nueva cotización</a><a class="admin-button admin-button--ghost" href="../galeria/">Buscar inspiración</a></div>
        </div>
        <div class="client-welcome__sigil" aria-hidden="true"><span>TV</span></div>
    </section>

    <?php if ($profileMissing): ?><div class="admin-notice admin-notice--warning"><span>!</span>Tu cuenta está activa, pero todavía no tiene un perfil de cliente vinculado. Solicita ayuda al administrador.</div><?php endif ?>

    <section class="client-home-stats" aria-label="Resumen personal">
        <article><span>I</span><div><b><?= e($quoteSummary['total']) ?></b><p>Cotizaciones</p></div></article>
        <article><span>II</span><div><b><?= e($quoteSummary['en_proceso']) ?></b><p>En proceso</p></div></article>
        <article><span>III</span><div><b><?= e($quoteSummary['respondidas']) ?></b><p>Con respuesta</p></div></article>
        <article><span>IV</span><div><b><?= e(count($appointments)) ?></b><p>Próximas citas</p></div></article>
    </section>

    <section class="client-portals">
        <header><div><p class="admin-eyebrow">EXPLORA EL ESTUDIO</p><h2>Todo Tinta Viva desde tu cuenta</h2></div><p>Estas secciones son públicas, pero tu sesión permanece activa mientras las visitas.</p></header>
        <div>
            <a href="../artistas/" class="client-portal client-portal--artists"><span>01</span><small>LOS CREADORES</small><h3>Artistas</h3><p>Conoce sus estilos, historias y obras.</p><b>Entrar →</b></a>
            <a href="../galeria/" class="client-portal client-portal--gallery"><span>02</span><small>EL ARCHIVO DE LA PIEL</small><h3>Galería</h3><p>Explora marcas, símbolos y técnicas.</p><b>Entrar →</b></a>
            <a href="../cotizaciones/" class="client-portal client-portal--quote"><span>03</span><small>EL PRIMER JURAMENTO</small><h3>Cotizar</h3><p>Transforma una visión en un proyecto real.</p><b>Comenzar →</b></a>
        </div>
    </section>

    <div class="client-home-grid">
        <section class="client-home-section" id="proximas-citas">
            <header><div><p class="admin-eyebrow">CALENDARIO</p><h2>Próximas citas</h2></div><span><?= e(count($appointments)) ?></span></header>
            <?php if ($appointments === []): ?><div class="client-home-empty"><b>◷</b><p>No tienes citas próximas.</p><small>Cuando una cotización sea aceptada, el estudio podrá agendarla.</small></div><?php else: ?><div class="client-appointments"><?php foreach ($appointments as $appointment): ?><article><time datetime="<?= e($appointment['fecha_hora_inicio']) ?>"><b><?= e(clientDashboardDate($appointment['fecha_hora_inicio'], 'd')) ?></b><span><?= e(strtoupper(clientDashboardDate($appointment['fecha_hora_inicio'], 'M'))) ?></span></time><div><small><?= e(clientDashboardDate($appointment['fecha_hora_inicio'], 'H:i')) ?> – <?= e(clientDashboardDate($appointment['fecha_hora_fin'], 'H:i')) ?></small><h3><?= e($appointment['artista']) ?></h3><p><?= e(label($appointment['estado'])) ?></p></div></article><?php endforeach ?></div><?php endif ?>
        </section>

        <section class="client-home-section">
            <header><div><p class="admin-eyebrow">ÚLTIMOS PACTOS</p><h2>Cotizaciones recientes</h2></div><a href="mis-cotizaciones.php">Ver todas →</a></header>
            <?php if ($recentQuotes === []): ?><div class="client-home-empty"><b>†</b><p>Aún no has entregado ninguna idea.</p><a href="../cotizaciones/">Crear la primera</a></div><?php else: ?><div class="client-recent-quotes"><?php foreach ($recentQuotes as $quoteItem): ?><a href="mis-cotizaciones.php?id=<?= e($quoteItem['id_cotizacion']) ?>"><div><small><?= e($quoteItem['categoria']) ?> · <?= e($quoteItem['zona_cuerpo']) ?></small><h3>Solicitud #<?= e($quoteItem['id_cotizacion']) ?></h3><p><?= e($quoteItem['descripcion_idea']) ?></p></div><span class="client-status client-status--<?= e($quoteItem['estado']) ?>"><?= e($statusLabels[$quoteItem['estado']] ?? $quoteItem['estado']) ?></span></a><?php endforeach ?></div><?php endif ?>
        </section>
    </div>
</main>
</body>
</html>
