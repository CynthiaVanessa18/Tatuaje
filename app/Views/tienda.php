<?php
$tiendaTitulos=['productos'=>'Productos e inventario','ventas'=>'Ventas de la tienda','categorias_productos'=>'Categorías','imagenes'=>'Imágenes de artículos','detalle'=>'Detalle de ventas'];
function tiendaIcono(string $nombre): string {
    $paths=[
        'productos'=>'<path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8M7 5.8l9 5"/>',
        'ventas'=>'<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/>',
        'categorias_productos'=>'<path d="M3 3h8l10 10-8 8L3 11z"/><circle cx="7.5" cy="7.5" r="1"/>',
        'imagenes'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="8" r="2"/><path d="m3 17 6-6 4 4 3-3 5 5"/>',
        'detalle'=>'<path d="M8 6h13M8 12h13M8 18h13M3 6h1M3 12h1M3 18h1"/>',
        'stock'=>'<path d="m12 3 10 18H2zM12 9v5M12 17h.01"/>',
        'total'=>'<path d="M5 21v-7h3v7M11 21V9h3v12M17 21V3h3v18"/>',
        'editar'=>'<path d="m15 4 5 5M4 20l4-1L21 6l-4-4L4 15z"/>',
        'ver'=>'<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    ];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">'.($paths[$nombre]??$paths['detalle']).'</svg>';
}
?>
<div class="resumen-tienda">
    <?php foreach ([['productos','Productos activos',$resumenTienda['productos']],['ventas','Ventas pendientes',$resumenTienda['pendientes']]] as [$icon,$caption,$value]): ?>
        <article class="indicador-tienda"><span class="icono-tienda"><?= tiendaIcono($icon) ?></span><div><p><?= e($caption) ?></p><strong><?= e($value) ?></strong></div></article>
    <?php endforeach ?>
    <article class="indicador-tienda"><span class="icono-tienda"><?= tiendaIcono('total') ?></span><div><p>Ventas del mes · UTC</p>
        <?php foreach ($resumenTienda['ventas'] as $importe): ?><strong class="importe-tienda"><?= e($importe['moneda']) ?> <?= e(number_format((float)$importe['importe'],2,',','.')) ?></strong><?php endforeach ?>
        <?php if (!$resumenTienda['ventas']): ?><strong>Sin ventas</strong><?php endif ?>
    </div></article>
    <article class="indicador-tienda"><span class="icono-tienda"><?= tiendaIcono('stock') ?></span><div><p>Stock bajo o agotado</p><strong><?= e($resumenTienda['stock']) ?></strong></div></article>
</div>
<nav class="accesos-tienda" aria-label="Secciones de tienda">
    <?php foreach ($tiendaIds as $id): if ($id==='imagenes') continue; ?><a href="administrador.php?module=<?= e($id) ?>" <?= $id === $moduloSeleccionado || ($id==='productos' && $moduloSeleccionado==='imagenes') ? 'aria-current="page"' : '' ?>><?= tiendaIcono($id) ?><?= e($tiendaTitulos[$id]) ?></a><?php endforeach ?>
</nav>
<?php foreach ($seccionesTienda as $seccion):
    if (in_array($moduloSeleccionado,['productos','imagenes'],true)) {
        if (!in_array($seccion['moduleId'],['productos','imagenes'],true)) continue;
    } elseif ($seccion['moduleId'] !== $moduloSeleccionado) continue;
    extract($seccion,EXTR_OVERWRITE); ?>
    <section id="tienda-<?= e($moduleId) ?>" class="seccion-tienda" aria-labelledby="titulo-<?= e($moduleId) ?>">
        <header><h2 id="titulo-<?= e($moduleId) ?>"><?= tiendaIcono($moduleId) ?><?= e($tiendaTitulos[$moduleId]) ?></h2>
            <a class="button" href="administrador.php?module=<?= e($moduleId) ?>&amp;mode=create">＋ <?= $moduleId==='productos' ? 'Agregar producto' : 'Crear registro' ?></a>
        </header>
        <?php require __DIR__.'/registros.php'; ?>
    </section>
<?php endforeach ?>
