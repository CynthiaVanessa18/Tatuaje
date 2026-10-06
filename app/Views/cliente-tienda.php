<?php require __DIR__.'/cliente-regalos-disponibles.php'; ?>
<section class="card controles-catalogo">
    <form method="get" action="<?= e($clientEndpoint ?? '../index.php') ?>" class="buscador-cliente">
        <input type="hidden" name="section" value="tienda">
        <label>Tipo de artículo<select name="categoria"><option value="">Todos los artículos</option><?php foreach ($storeCategories as $category): ?><option value="<?= e($category['id_categoria_producto']) ?>" <?= (string)$category['id_categoria_producto']===$storeCategory?'selected':'' ?>><?= e($category['nombre']) ?></option><?php endforeach ?></select></label>
        <label>Buscar artículos<input type="search" name="q" value="<?= e($storeSearch) ?>" placeholder="¿Qué estás buscando?"></label>
        <button><svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></svg> Buscar</button><a href="<?= e($clientEndpoint ?? '../index.php') ?>?section=tienda">Limpiar filtros</a>
    </form>

</section>
<?php if ($storePromotions): ?>
<section class="card ofertas-catalogo" aria-label="Promociones de la tienda">
    <h2>Ofertas para ti</h2>
    <p>Los descuentos aplicables se suman automáticamente en el carrito.</p>
    <div class="ofertas-catalogo-lista">
    <?php foreach ($storePromotions as $offer): ?>
        <article class="oferta-catalogo">
            <strong><?= e($offer['titulo']) ?></strong><span><?= e(StorePromotions::benefit($offer)) ?></span>
            <?php if ($offer['descripcion']): ?><p><?= nl2br(e($offer['descripcion'])) ?></p><?php endif ?>
            <small><?= e(['tienda'=>'Toda la tienda','productos'=>'Artículos seleccionados','categorias'=>'Categorías seleccionadas'][$offer['alcance']]) ?><?= StorePromotions::cents($offer['minimo_compra'])?' · Compra mínima: ₡'.e(number_format((float)$offer['minimo_compra'],2,',','.')):'' ?></small>
            <small>Válida hasta <?= e((new DateTimeImmutable($offer['fecha_fin'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Costa_Rica'))->format('d/m/Y H:i')) ?> · Costa Rica</small>
        </article>
    <?php endforeach ?>
    </div>
</section>
<?php endif ?>
<div class="cabecera-resultados"><h2><?= $storeCategory==='' ? 'Descubre nuestros artículos' : 'Artículos de esta categoría' ?></h2><p role="status"><?= e($storeTotal) ?> artículos</p></div>
<div class="catalogo-cliente">
    <?php foreach ($storeProducts as $product): ?>
        <article class="producto-cliente" data-product-card>
            <?php $image=trim((string)($product['imagen']??''));
                $safeImage=$image!=='' && (!preg_match('~^(?:[a-z][a-z0-9+.-]*:|//)~i',$image) || preg_match('~^https?://~i',$image)); ?>
            <?php if ($safeImage): ?><img src="<?= e(imageUrl($image)) ?>" alt="<?= e($product['nombre']) ?>" loading="lazy">
            <?php else: ?><div class="sin-foto-cliente" aria-label="Sin imagen disponible">▣</div><?php endif ?>
            <div class="producto-cliente-info">
                <p class="eyebrow"><?= e($product['categoria']) ?></p>
                <h2><button type="button" class="producto-abrir" data-product-open><?= e($product['nombre']) ?></button></h2>
                <p class="precio-cliente">₡<?= e(number_format((float)$product['precio'],2,',','.')) ?></p>
                <?php if ($product['ofertas']):
                    $offerPercent=0;$offerAmount=0;$offerConditions=[];
                    foreach ($product['ofertas'] as $offer) {
                        if ($offer['tipo_descuento']==='porcentaje') $offerPercent+=StorePromotions::cents((string)$offer['valor_descuento']);
                        else $offerAmount+=StorePromotions::cents((string)$offer['valor_descuento']);
                        $offerConditions[]=$offer['titulo'].(StorePromotions::cents((string)$offer['minimo_compra'])?' · Compra mínima: ₡'.number_format((float)$offer['minimo_compra'],2,',','.'):'');
                    }
                    $offerBenefits=[];
                    if ($offerPercent) $offerBenefits[]=rtrim(rtrim(number_format(min(10000,$offerPercent)/100,2,'.',''),'0'),'.').'%';
                    if ($offerAmount) $offerBenefits[]='₡'.number_format($offerAmount/100,2,',','.').' por compra';
                ?>
                    <p class="oferta-producto"><strong>Hasta <?= e(implode(' + ',$offerBenefits)) ?> de descuento</strong><small><?= e(implode(' · ',$offerConditions)) ?></small></p>
                <?php endif ?>
                <span class="estado-tienda <?= (int)$product['stock_actual']>0 ? 'estado-activo' : 'estado-agotado' ?>"><?= (int)$product['stock_actual']>0 ? 'Disponible · Retiro en el estudio' : 'Agotado' ?></span>
                <?php if ($product['descripcion']): ?><details><summary>Ver descripción</summary><p><?= nl2br(e($product['descripcion'])) ?></p></details><?php endif ?>
                <form method="post" action="<?= e($clientEndpoint ?? '../index.php') ?>?<?= e(http_build_query(['section'=>'tienda','categoria'=>$storeCategory,'q'=>$storeSearch,'page'=>$storePage])) ?>" class="agregar-carrito">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="cart_action" value="add"><input type="hidden" name="product" value="<?= e($product['id_producto']) ?>">
                    <button type="submit" class="producto-agregar-icono" aria-label="<?= e((int)$product['stock_actual']<1 ? 'Agotado: '.$product['nombre'] : 'Añadir '.$product['nombre'].' al carrito') ?>" title="<?= (int)$product['stock_actual']<1 ? 'Agotado' : 'Añadir al carrito' ?>" <?= (int)$product['stock_actual']<1 ? 'disabled' : '' ?>><svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h3l2.5 12h11L21 7H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M14 3v6m-3-3h6"/></svg></button>
                </form>
            </div>
        </article>
    <?php endforeach ?>
</div>
<dialog class="producto-detalle" data-product-dialog aria-labelledby="producto-detalle-titulo">
    <button type="button" class="producto-cerrar" data-product-close aria-label="Cerrar detalle">×</button>
    <div class="producto-detalle-contenido" data-product-content></div>
    <h2>Descubre otros productos</h2>
    <div class="producto-relacionados" data-product-related></div>
</dialog>
<?php if (!$storeProducts): ?><section class="card empty">No hay artículos disponibles con estos filtros.</section><?php endif ?>
<?php if ($storePages>1): ?><nav class="actions pagination" aria-label="Páginas del catálogo">
    <?php foreach ([$storePage-1=>'Anterior',$storePage+1=>'Siguiente'] as $page=>$caption): if ($page<1 || $page>$storePages) continue; ?>
        <a class="button" href="<?= e($clientEndpoint ?? '../index.php') ?>?<?= e(http_build_query(['section'=>'tienda','q'=>$storeSearch,'categoria'=>$storeCategory,'page'=>$page])) ?>"><?= e($caption) ?></a>
    <?php endforeach ?><span>Página <?= e($storePage) ?> de <?= e($storePages) ?></span>
</nav><?php endif ?>
