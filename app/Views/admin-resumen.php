<?php
// El nombre de tabla procede exclusivamente del catálogo cerrado de módulos.
$dashboardTotal = (int)conectarBaseDatos()->query('SELECT COUNT(*) FROM `'.$module['table'].'`')->fetchColumn();
?>
<section class="module-overview" aria-label="Resumen de <?= e($module['title']) ?>">
    <article class="dashboard-stat"><span class="dashboard-stat-icon"><?= adminIcon($moduleId) ?></span><div><small>Total de registros</small><strong><?= e($dashboardTotal) ?></strong></div></article>
    <article class="dashboard-stat"><span class="dashboard-stat-icon"><?= adminIcon('buscar') ?></span><div><small>Resultados de la consulta</small><strong><?= e($list['total']??$dashboardTotal) ?></strong></div></article>
    <article class="dashboard-guide"><p class="eyebrow">GESTIÓN DE <?= e($adminGroups[$activeGroup]['title']) ?></p><p><?= empty($module['readonly'])?'Consulta los registros, filtra los resultados o abre un formulario para actualizar la información.':'Consulta los registros y sus detalles desde este apartado.' ?></p><a href="administrador.php?module=<?= e($moduleId) ?>"><?= adminIcon('registros') ?> Ver todos los registros →</a></article>
</section>
