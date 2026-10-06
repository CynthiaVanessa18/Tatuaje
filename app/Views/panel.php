<?php
$adminGroups = [];
foreach ($modules as $id => $item) {
    $adminGroups[$item['group']]['modules'][$id] = $item['title'];
}
foreach ($adminGroups as $groupName => &$group) {
    $group['title'] = ['Venta de artículos'=>'Tienda', 'Membresía'=>'Membresías'][$groupName] ?? $groupName;
    $group['entry'] = $groupName === 'Venta de artículos' ? 'productos' : array_key_first($group['modules']);
}
unset($group);
$activeGroup = $module['group'];
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($module['title']) ?> · Estudio Tattoo</title>
    <?php if ($moduleId==='planes'): ?>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/bootstrap.min.css">
    <script src="../assets/vendor/bootstrap/bootstrap.bundle.min.js" defer></script>
    <?php endif ?>
    <link rel="stylesheet" href="../assets/css/app.css">
    <?php if ($esTienda): ?><link rel="stylesheet" href="../assets/css/tienda.css"><?php endif ?>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,500&family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="../assets/css/site.css">
    <link rel="stylesheet" href="../assets/css/admin-tinta-viva.css">
    <?php if ($moduleId==='planes'): ?><link rel="stylesheet" href="../assets/css/membresias-admin.css"><?php endif ?>
    <script src="../assets/js/app.js" defer></script>
</head>

<body class="admin-tinta-viva <?= $esTienda ? 'pagina-tienda' : '' ?>">
    <aside><a class="brand" href="administrador.php">ESTUDIO<br><strong>TATTOO</strong></a>
        <p class="eyebrow">GESTIÓN · PERSONA 2</p>
        <nav aria-label="Apartados de administración">
            <a href="artistas.php">Perfiles de artistas</a>
            <a href="galeria.php">Galería fotográfica</a>
            <a href="cotizaciones.php">Cotizaciones y citas</a>
            <?php foreach ($adminGroups as $groupName => $group): ?>
                <a <?= $groupName === $activeGroup ? 'aria-current="page"' : '' ?>
                    href="administrador.php?module=<?= e($group['entry']) ?>"><?= e($group['title']) ?></a>
            <?php endforeach ?>
        </nav>
        <form method="post" action="../auth/logout.php"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button
                class="secondary">Cerrar sesión</button></form>
    </aside>
    <main>
        <header>
            <div>
                <p class="eyebrow">PANEL ADMINISTRATIVO</p>
                <h1><?= $esTienda ? 'Administración de la tienda' : e($module['title']) ?></h1>
                <p><?= $esTienda ? 'Gestiona productos, existencias y ventas en un solo lugar.' : 'Consulta y organiza los registros de tu estudio.' ?></p>
            </div><?php if (empty($module['readonly']) && $moduleId!=='planes'):
                $createModule = $moduleId==='imagenes' ? 'productos' : $moduleId;
                $createTitle = ['cuentas'=>'Crear cuenta','productos'=>'Agregar producto','promociones_tienda'=>'Crear promoción','grupos_clientes'=>'Crear grupo','clientes_grupos'=>'Asignar cliente'][$createModule] ?? 'Crear registro';
            ?><a class="button"
                    href="administrador.php?module=<?= e($createModule) ?>&amp;mode=create">＋ <?= e($createTitle) ?></a><?php endif ?>
        </header>
        <?php if (!$esTienda): ?>
            <nav class="accesos-admin" aria-label="Subapartados de <?= e($adminGroups[$activeGroup]['title']) ?>">
                <?php foreach ($adminGroups[$activeGroup]['modules'] as $id => $title): ?>
                    <a href="administrador.php?module=<?= e($id) ?>" <?= $id === $moduleId ? 'aria-current="page"' : '' ?>><?= e($title) ?></a>
                <?php endforeach ?>
            </nav>
        <?php endif ?>
        <?php if ($notice): ?>
            <p class="notice" role="status"><?= e($notice) ?></p><?php endif ?>
        <?php if ($error): ?>
            <p class="notice error" role="alert"><?= e($error) ?></p><?php endif ?>
        <?php
        if ($moduleId === 'cuentas') {
            require __DIR__.'/cuentas-admin.php';
        } elseif ($moduleId === 'planes') {
            require __DIR__.'/membresias-planes.php';
        } elseif ($moduleId === 'calificaciones') {
            require __DIR__.'/calificaciones.php';
        } elseif ($esTienda) {
            require __DIR__.'/tienda.php';
        } else {
            require __DIR__.'/registros.php';
        }
        ?>
    </main>
</body>

</html>
