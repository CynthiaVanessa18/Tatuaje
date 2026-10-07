<?php
$navigationModules = require __DIR__ . '/../../config/modules.php';
$navigationGroups = [];
foreach ($navigationModules as $id => $item) {
    if (in_array($id, ['promociones', 'planes_beneficios'], true)) continue;
    $navigationGroups[$item['group']]['modules'][] = $id;
    $navigationGroups[$item['group']]['entry'] ??= $id;
}
$navigationGroups['Venta de artículos']['entry'] = 'productos';
$navigationTitles = ['Categorías / filtros'=>'Categorías y especialidades', 'Membresía'=>'Membresías', 'Venta de artículos'=>'Tienda'];
$navigationIcons = ['Cuentas'=>'♙', 'Categorías / filtros'=>'◇', 'Calificaciones'=>'★', 'Membresía'=>'♜', 'Beneficios'=>'✦', 'Tarjetas de regalo'=>'▣', 'Venta de artículos'=>'▤'];
$navigationPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$navigationModule = $navigationPage === 'administrador.php' ? ($moduleId ?? 'categorias') : null;
?>
<aside class="admin-sidebar">
    <a class="admin-brand" href="administrador.php"><span class="admin-brand__sigil">✦</span><span><strong>TINTA VIVA</strong><small>ADMINISTRACIÓN</small></span></a>
    <p class="admin-sidebar__label">GESTIÓN DEL ESTUDIO</p>
    <nav aria-label="Navegación administrativa">
        <?php foreach (['artistas.php'=>['✦','Perfiles de artistas'], 'galeria.php'=>['▧','Galería fotográfica'], 'cotizaciones.php'=>['◆','Cotizaciones y citas']] as $href => [$icon, $title]): ?>
        <a href="<?= e($href) ?>" <?= $navigationPage === $href ? 'aria-current="page"' : '' ?>><span aria-hidden="true"><?= e($icon) ?></span><?= e($title) ?></a>
        <?php endforeach ?>
        <?php foreach ($navigationGroups as $name => $group): ?>
        <a href="administrador.php?module=<?= e($group['entry']) ?>" <?= in_array($navigationModule, $group['modules'], true) ? 'aria-current="page"' : '' ?>><span aria-hidden="true"><?= e($navigationIcons[$name] ?? '◇') ?></span><?= e($navigationTitles[$name] ?? $name) ?></a>
        <?php endforeach ?>
    </nav>
    <div class="admin-sidebar__bottom">
        <p>Sesión de <strong><?= e($account['usuario'] ?? 'administrador') ?></strong></p>
        <a href="../index.php" target="_blank" rel="noopener">Ver sitio público ↗</a>
        <form method="post" action="../auth/logout.php"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button type="submit">Cerrar sesión</button></form>
    </div>
</aside>
