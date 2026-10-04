<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Estudio Tattoo</title>
    <?php if ($clientStore): ?><link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css"><script src="assets/vendor/bootstrap/bootstrap.bundle.min.js" defer></script><?php endif ?>
    <link rel="stylesheet" href="assets/css/app.css">
    <?php if ($clientStore): ?><link rel="stylesheet" href="assets/css/tienda.css"><link rel="stylesheet" href="assets/css/cliente-tienda.css"><?php endif ?>
    <?php if ($clientStore): ?><script src="assets/js/cliente-tienda.js" defer></script><?php endif ?>
</head>
<body class="<?= $clientStore ? 'pagina-tienda tienda-cliente' : '' ?>" <?= $clientStore ? 'data-bs-theme="dark"' : '' ?>>
<aside>
    <a class="brand" href="<?= e(roleRoutes()[$requiredRole]) ?>">ESTUDIO<br><strong>TATTOO</strong></a>
    <p class="eyebrow"><?= e(label($requiredRole)) ?></p>
    <nav aria-label="Navegación">
        <a href="<?= e(roleRoutes()[$requiredRole]) ?>" <?= !$clientStore ? 'aria-current="page"' : '' ?>><?= $requiredRole==='cliente' ? 'Mis citas' : e($title) ?></a>
        <?php if ($requiredRole==='cliente'): ?><a href="cliente.php?section=tienda" <?= $clientStore && !$clientCart ? 'aria-current="page"' : '' ?>>Tienda</a><a href="cliente.php?section=carrito" <?= $clientCart ? 'aria-current="page"' : '' ?>>Carrito<?= $clientStore ? ' ('.e($cartCount).')' : '' ?></a><?php endif ?>
    </nav>
    <form method="post" action="logout.php">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <button class="secondary">Cerrar sesión</button>
    </form>
</aside>
<main>
    <header>
        <div>
            <p class="eyebrow">BIENVENIDO, <?= e($account['usuario']) ?></p>
            <h1><?= e($title) ?></h1>
            <p><?= e($description) ?></p>
        </div>
        <?php if ($clientStore && !$clientCart): ?><a class="button carrito-cabecera" href="cliente.php?section=carrito"><span aria-hidden="true">🛒</span> Mi carrito (<?= e($cartCount) ?>)</a><?php endif ?>
    </header>
    <?php if ($clientStore): ?>
        <?php if ($cartNotice): ?><p class="notice" role="status"><?= e($cartNotice) ?></p><?php endif ?>
        <?php if ($cartError): ?><p class="notice error" role="alert"><?= e($cartError) ?></p><?php endif ?>
        <?php require __DIR__.($clientCart?'/cliente-carrito.php':'/cliente-tienda.php'); ?>
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
                            <td><?= e((new DateTimeImmutable($appointment[$dateField], new DateTimeZone('UTC')))
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
