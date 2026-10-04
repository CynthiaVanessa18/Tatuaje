<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($module['title']) ?> · Estudio Tattoo</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <?php if ($esTienda): ?><link rel="stylesheet" href="assets/css/tienda.css"><?php endif ?>
    <script src="assets/js/app.js" defer></script>
</head>

<body class="<?= $esTienda ? 'pagina-tienda' : '' ?>">
    <aside><a class="brand" href="index.php">ESTUDIO<br><strong>TATTOO</strong></a>
        <p class="eyebrow">GESTIÓN · PERSONA 2</p>
        <nav aria-label="Módulos">
            <?php $group = '';
            foreach ($modules as $id => $item):
                if ($id === 'imagenes') continue;
                if ($group !== $item['group']):
                    $group = $item['group']; ?>
                    <p class="nav-group"><?= e($group) ?></p><?php endif ?>
                <a <?= $id === $moduleId || ($id === 'productos' && $moduleId === 'imagenes') ? 'aria-current="page"' : '' ?>
                    href="index.php?module=<?= e($id) ?>"><?= e($item['title']) ?></a><?php endforeach ?>
        </nav>
        <form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button
                class="secondary">Cerrar sesión</button></form>
    </aside>
    <main>
        <header>
            <div>
                <p class="eyebrow">PANEL ADMINISTRATIVO</p>
                <h1><?= $esTienda ? 'Administración de la tienda' : e($module['title']) ?></h1>
                <p><?= $esTienda ? 'Gestiona productos, existencias y ventas en un solo lugar.' : 'Consulta y organiza los registros de tu estudio.' ?></p>
            </div><?php if (empty($module['readonly'])): ?><a class="button"
                    href="index.php?module=<?= e($esTienda ? 'productos' : $moduleId) ?>&amp;mode=create">＋ <?= $esTienda ? 'Agregar producto' : 'Crear registro' ?></a><?php endif ?>
        </header>
        <?php if ($notice): ?>
            <p class="notice" role="status"><?= e($notice) ?></p><?php endif ?>
        <?php if ($error): ?>
            <p class="notice error" role="alert"><?= e($error) ?></p><?php endif ?>
        <?php require __DIR__.($esTienda ? '/tienda.php' : '/registros.php'); ?>
    </main>
</body>

</html>
