<section class="card controles-catalogo">
    <form method="get" action="cliente.php" class="buscador-cliente">
        <input type="hidden" name="section" value="tienda">
        <input type="hidden" name="categoria" value="<?= e($storeCategory) ?>">
        <label>Buscar artículos<input type="search" name="q" value="<?= e($storeSearch) ?>" placeholder="¿Qué estás buscando?"></label>
        <button><svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></svg> Buscar</button><a href="cliente.php?section=tienda">Limpiar filtros</a>
    </form>
    <p class="tipos-articulos-titulo">Tipos de artículos</p>
    <nav class="tipos-articulos" aria-label="Filtrar por tipo de artículo">
        <a href="cliente.php?<?= e(http_build_query(['section'=>'tienda','q'=>$storeSearch])) ?>" <?= $storeCategory==='' ? 'aria-current="page"' : '' ?>>Todos los artículos</a>
        <?php foreach ($storeCategories as $category): ?>
            <a href="cliente.php?<?= e(http_build_query(['section'=>'tienda','q'=>$storeSearch,'categoria'=>$category['id_categoria_producto']])) ?>" <?= (string)$category['id_categoria_producto']===$storeCategory ? 'aria-current="page"' : '' ?>><?= e($category['nombre']) ?></a>
        <?php endforeach ?>
    </nav>
</section>
<div class="cabecera-resultados"><h2><?= $storeCategory==='' ? 'Descubre nuestros artículos' : 'Artículos de esta categoría' ?></h2><p role="status"><?= e($storeTotal) ?> artículos</p></div>
<div class="catalogo-cliente">
    <?php foreach ($storeProducts as $product): ?>
        <article class="producto-cliente">
            <?php $image=trim((string)($product['imagen']??''));
                $safeImage=$image!=='' && (!preg_match('~^(?:[a-z][a-z0-9+.-]*:|//)~i',$image) || preg_match('~^https?://~i',$image)); ?>
            <?php if ($safeImage): ?><img src="<?= e(imageUrl($image)) ?>" alt="<?= e($product['nombre']) ?>" loading="lazy">
            <?php else: ?><div class="sin-foto-cliente" aria-label="Sin imagen disponible">▣</div><?php endif ?>
            <div class="producto-cliente-info">
                <p class="eyebrow"><?= e($product['categoria']) ?></p>
                <h2><?= e($product['nombre']) ?></h2>
                <p class="precio-cliente">₡<?= e(number_format((float)$product['precio'],2,',','.')) ?></p>
                <span class="estado-tienda <?= (int)$product['stock_actual']>0 ? 'estado-activo' : 'estado-agotado' ?>"><?= (int)$product['stock_actual']>0 ? 'Disponible · Retiro en el estudio' : 'Agotado' ?></span>
                <?php if ($product['descripcion']): ?><details><summary>Ver descripción</summary><p><?= nl2br(e($product['descripcion'])) ?></p></details><?php endif ?>
                <form method="post" action="cliente.php?<?= e(http_build_query(['section'=>'tienda','categoria'=>$storeCategory,'q'=>$storeSearch,'page'=>$storePage])) ?>" class="agregar-carrito">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="cart_action" value="add"><input type="hidden" name="product" value="<?= e($product['id_producto']) ?>">
                    <button <?= (int)$product['stock_actual']<1 ? 'disabled' : '' ?>><?= (int)$product['stock_actual']<1 ? 'Agotado' : '＋ Añadir al carrito' ?></button>
                </form>
            </div>
        </article>
    <?php endforeach ?>
</div>
<?php if (!$storeProducts): ?><section class="card empty">No hay artículos disponibles con estos filtros.</section><?php endif ?>
<?php if ($storePages>1): ?><nav class="actions pagination" aria-label="Páginas del catálogo">
    <?php foreach ([$storePage-1=>'Anterior',$storePage+1=>'Siguiente'] as $page=>$caption): if ($page<1 || $page>$storePages) continue; ?>
        <a class="button" href="cliente.php?<?= e(http_build_query(['section'=>'tienda','q'=>$storeSearch,'categoria'=>$storeCategory,'page'=>$page])) ?>"><?= e($caption) ?></a>
    <?php endforeach ?><span>Página <?= e($storePage) ?> de <?= e($storePages) ?></span>
</nav><?php endif ?>
