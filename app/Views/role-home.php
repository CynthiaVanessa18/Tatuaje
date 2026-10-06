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
</head>
<body class="<?= $requiredRole === 'cliente' ? 'cliente-tinta-viva ' : '' ?><?= $clientMemberships ? 'pagina-membresias ' : '' ?><?= $clientCart ? 'pagina-carrito ' : '' ?><?= $clientStore ? 'pagina-tienda tienda-cliente' : '' ?>" <?= $clientStore ? 'data-bs-theme="dark"' : '' ?>>
<?php if ($requiredRole==='cliente'): require __DIR__.'/cliente-navegacion.php'; else: ?>
<aside>
    <a class="brand" href="<?= e(roleRoutes()[$requiredRole]) ?>">ESTUDIO<br><strong>TATTOO</strong></a>
    <p class="eyebrow"><?= e(label($requiredRole)) ?></p>
    <nav aria-label="Navegación"><a href="<?= e(roleRoutes()[$requiredRole]) ?>" aria-current="page"><?= e($title) ?></a></nav>
    <form method="post" action="<?= e($publicPrefix) ?>auth/logout.php"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button class="secondary">Cerrar sesión</button></form>
</aside>
<?php endif ?>
<main>
    <header>
        <div>
            <p class="eyebrow">BIENVENIDO, <?= e($account['usuario']) ?></p>
            <h1><?= e($title) ?></h1>
            <p><?= e($description) ?></p>
        </div>
        <?php if ($clientStore && !$clientCart): ?><div class="store-actions"><a class="button secondary" href="<?= e($clientEndpoint) ?>?section=tarjetas">Mis tarjetas de regalo</a><a class="button carrito-cabecera" href="<?= e($clientEndpoint) ?>?section=carrito"><span aria-hidden="true">🛒</span> Mi carrito (<?= e($cartCount) ?>)</a></div><?php endif ?>
    </header>
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
    <section class="card">
        <h2>Próximas citas</h2>
        <p>Se muestran hasta 50 citas. Las horas se presentan en hora de Costa Rica.</p>
        <div class="table-scroll">
            <table>
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
    </section>
    <?php endif ?>
</main>
</body>
</html>
