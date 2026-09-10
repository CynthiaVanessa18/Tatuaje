<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Estudio Tattoo</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<aside>
    <a class="brand" href="<?= e(roleRoutes()[$requiredRole]) ?>">ESTUDIO<br><strong>TATTOO</strong></a>
    <p class="eyebrow"><?= e(label($requiredRole)) ?></p>
    <nav aria-label="Navegación">
        <a href="<?= e(roleRoutes()[$requiredRole]) ?>" aria-current="page"><?= e($title) ?></a>
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
    </header>
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
</main>
</body>
</html>
