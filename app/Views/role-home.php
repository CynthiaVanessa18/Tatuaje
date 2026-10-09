<?php $publicPrefix = $publicPrefix ?? '../'; $clientEndpoint = $clientEndpoint ?? '../index.php'; ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= $requiredRole === 'cliente' ? 'Tinta Viva' : 'Estudio Tattoo' ?></title>
    <?php if ($clientStore): ?><link rel="stylesheet" href="<?= e($publicPrefix) ?>assets/vendor/bootstrap/bootstrap.min.css"><script src="<?= e($publicPrefix) ?>assets/vendor/bootstrap/bootstrap.bundle.min.js" defer></script><?php endif ?>
    <link rel="stylesheet" href="<?= e($publicPrefix) ?>assets/css/app.css">
    <?php if ($clientStore): ?><link rel="stylesheet" href="<?= e($publicPrefix) ?>assets/css/tienda.css"><link rel="stylesheet" href="<?= e($publicPrefix) ?>assets/css/cliente-tienda.css"><?php endif ?>
    <?php if ($clientStore): ?><script src="<?= e($publicPrefix) ?>assets/js/cliente-tienda.js?v=<?= e(filemtime(__DIR__.'/../../public/assets/js/cliente-tienda.js')) ?>" defer></script><?php endif ?>
    <?php if ($clientMemberships): ?><script src="<?= e($publicPrefix) ?>assets/js/cliente-membresias.js?v=<?= e(filemtime(__DIR__.'/../../public/assets/js/cliente-membresias.js')) ?>" defer></script><?php endif ?>
<?php if ($requiredRole === 'cliente'): ?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,500&family=Montserrat:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e($publicPrefix) ?>assets/css/site.css">
<link rel="stylesheet" href="<?= e($publicPrefix) ?>assets/css/cliente-tinta-viva.css?v=<?= e(filemtime(__DIR__.'/../../public/assets/css/cliente-tinta-viva.css')) ?>">
<link rel="stylesheet" href="<?= e($publicPrefix) ?>assets/css/cliente-navegacion.css?v=<?= e(filemtime(__DIR__.'/../../public/assets/css/cliente-navegacion.css')) ?>">
<?php endif ?>
<?php responsiveAssets($publicPrefix); ?>
<?php if ($clientMemberships): ?><link rel="stylesheet" href="<?= e($publicPrefix) ?>assets/css/cliente-membresia-visual.css?v=<?= e(filemtime(__DIR__.'/../../public/assets/css/cliente-membresia-visual.css')) ?>"><?php endif ?>
<?php if ($clientStore): ?><link rel="stylesheet" href="<?= e($publicPrefix) ?>assets/css/cliente-tienda-visual.css?v=<?= e(filemtime(__DIR__.'/../../public/assets/css/cliente-tienda-visual.css')) ?>"><?php endif ?>
<?php if ($clientGiftCards): ?><link rel="stylesheet" href="<?= e($publicPrefix) ?>assets/css/cliente-tarjetas-visual.css?v=<?= e(filemtime(__DIR__.'/../../public/assets/css/cliente-tarjetas-visual.css')) ?>"><?php endif ?>
</head>
<body class="<?= $requiredRole === 'cliente' ? 'cliente-tinta-viva ' : '' ?><?= $clientGiftCards ? 'pagina-tarjetas ' : '' ?><?= $clientMemberships ? 'pagina-membresias ' : '' ?><?= $clientCart ? 'pagina-carrito ' : '' ?><?= $clientStore ? 'pagina-tienda tienda-cliente tienda-visual' : '' ?>" <?= $clientStore ? 'data-bs-theme="dark"' : '' ?>>
<?php if ($requiredRole==='cliente'): require __DIR__.'/cliente-navegacion.php'; else: ?>
<aside>
    <a class="brand" href="<?= e(roleRoutes()[$requiredRole]) ?>">ESTUDIO<br><strong>TATTOO</strong></a>
    <p class="eyebrow"><?= e(label($requiredRole)) ?></p>
    <nav aria-label="Navegación"><a href="<?= e(roleRoutes()[$requiredRole]) ?>" aria-current="page"><?= e($title) ?></a></nav>
    <form method="post" action="<?= e($publicPrefix) ?>auth/logout.php"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button class="secondary">Cerrar sesión</button></form>
</aside>
<?php endif ?>
<main>
    <?php if ($clientGiftCards): ?>
    <header class="gift-hero"><h1>Mis tarjetas de <span>regalo</span></h1><p>Consulta tus regalos y usa su saldo en la tienda.</p><div class="gift-hero-ornament" aria-hidden="true"><span>✦</span></div></header>
    <?php elseif ($clientMemberships): ?>
    <header class="membership-hero"><div><p class="eyebrow"><span aria-hidden="true">✧</span> MI MEMBRESÍA</p><h1>Tu membresía</h1><p>Tu arte merece cuidado.</p><div class="membership-hero-ornament" aria-hidden="true"><span>✧</span></div></div></header>
    <?php elseif ($clientStore && !$clientCart): require_once __DIR__.'/cliente-tienda-iconos.php'; ?>
    <header class="store-hero"><div class="store-hero-copy"><h1>Tienda <span>del estudio</span></h1><div class="store-hero-ornament" aria-hidden="true"><span>✧</span></div><p class="store-hero-tagline">Arte que llevas contigo.</p><p class="store-hero-description">Ropa, accesorios y cuidado para tus tatuajes.</p><div class="store-actions"><a class="button secondary" href="<?= e($clientEndpoint) ?>?section=tarjetas"><?= clientStoreIcon('regalo') ?> Mis tarjetas de regalo</a><a class="button carrito-cabecera" href="<?= e($clientEndpoint) ?>?section=carrito"><?= clientStoreIcon('carrito') ?> Mi carrito · <?= e($cartCount) ?></a></div></div></header>
    <?php else: ?><header>
        <div>
            <p class="eyebrow">BIENVENIDO, <?= e($account['usuario']) ?></p>
            <h1><?= e($title) ?></h1>
            <p><?= e($description) ?></p>
        </div>
        <?php if ($clientStore && !$clientCart): ?><div class="store-actions"><a class="button secondary" href="<?= e($clientEndpoint) ?>?section=tarjetas">Mis tarjetas de regalo</a><a class="button carrito-cabecera" href="<?= e($clientEndpoint) ?>?section=carrito"><span aria-hidden="true">🛒</span> Mi carrito (<?= e($cartCount) ?>)</a></div><?php endif ?>
    </header><?php endif ?>
    <?php if ($clientStore): ?>
        <?php if ($cartNotice): ?><div class="notice carrito-aviso" role="status" aria-live="polite" data-cart-notice><span><?= e($cartNotice) ?></span><a href="<?= e($clientEndpoint) ?>?section=carrito">Ver carrito</a><button type="button" aria-label="Cerrar mensaje" data-dismiss-cart-notice>×</button></div><?php endif ?>
        <?php if ($cartError): ?><p class="notice error" role="alert"><?= e($cartError) ?></p><?php endif ?>
        <?php require __DIR__.($clientCart?'/cliente-carrito.php':'/cliente-tienda.php'); ?>
    <?php elseif ($clientGiftCards): ?>
        <?php require __DIR__.'/cliente-tarjetas.php'; ?>
    <?php elseif ($clientAccount): ?>
        <?php require __DIR__.'/cliente-cuenta.php'; ?>
    <?php elseif ($clientMemberships): ?>
        <?php require __DIR__.'/cliente-membresias.php'; ?>
    <?php else: ?>
    <?php if ($profileMissing): ?>
        <p class="notice" role="status">Tu cuenta ya puede ingresar. Solicita al administrador que vincule tu perfil para mostrar tus citas.</p>
    <?php endif ?>
    <?php if ($requiredRole === 'cliente'): ?>
    <section class="card">
        <h2>Sesiones finalizadas</h2>
        <p>El estudio finaliza la cita después de la sesión. Luego puedes valorar tu experiencia con el artista.</p>
        <?php if (!$clientRatingAppointments): ?><p>Aún no tienes sesiones finalizadas.</p><?php endif ?>
        <?php foreach ($clientRatingAppointments as $finishedAppointment): ?>
        <p><strong><?= e($finishedAppointment['artista']) ?></strong> · <?= e((new DateTimeImmutable($finishedAppointment['fecha_hora_inicio']))->format('d/m/Y H:i')) ?> ·
        <?php if ($finishedAppointment['id_calificacion'] === null): ?>Pendiente de calificar · <a href="<?= e($publicPrefix) ?>panel/mis-calificaciones.php#cita-<?= e($finishedAppointment['id_cita']) ?>">Calificar mi experiencia</a>
        <?php else: ?>Experiencia calificada · <?= e($finishedAppointment['puntuacion']) ?>/5 · <a href="<?= e($publicPrefix) ?>panel/mis-calificaciones.php#cita-<?= e($finishedAppointment['id_cita']) ?>">Ver mi valoración</a><?php endif ?></p>
        <?php endforeach ?>
    </section>
    <?php endif ?>
    <section class="card">
        <h2>Próximas citas</h2>
        <p>Se muestran hasta 50 citas. Las horas se presentan en hora de Costa Rica.</p>
        <div class="table-scroll">
            <table data-server-paginated>
                <thead><tr>
                    <th>Inicio</th><th>Fin</th><th>Cliente</th><th>Artista</th><th>Estado</th>
                </tr></thead>
                <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <?php foreach (['fecha_hora_inicio', 'fecha_hora_fin'] as $dateField): ?>
                            <td><?= e((new DateTimeImmutable($appointment[$dateField], new DateTimeZone('America/Costa_Rica')))
                                ->setTimezone(new DateTimeZone('America/Costa_Rica'))->format('d/m/Y H:i')) ?></td>
                        <?php endforeach ?>
                        <td><?= e($appointment['cliente']) ?></td>
                        <td><?= e($appointment['artista']) ?></td>
                        <td><?= e(label($appointment['estado'])) ?></td>
                    </tr>
                <?php endforeach ?>
                <?php if (!$appointments): ?>
                    <tr><td colspan="5" class="empty">No tienes próximas citas para mostrar.</td></tr>
                <?php endif ?>
                </tbody>
            </table>
        </div>
        <?php renderPagination($appointmentTotal,$appointmentPage,$_GET,'appointments_page'); ?>
    </section>
    <?php endif ?>
</main>
</body>
</html>
