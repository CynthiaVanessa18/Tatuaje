<?php $cartPayment=($_GET['paso']??'')==='pago'; ?>
<div class="checkout-frame container-fluid px-0">
<?php if (($_GET['paso']??'')==='confirmacion' && $cartReceipt): ?>
    <?php require __DIR__.'/cliente-confirmacion.php'; ?>
<?php else: ?>
<?php if ($cartReceipt && !$cartItems): ?><section class="card">
    <h2>Resumen de tu último pedido</h2>
    <p><?= e($cartReceipt['fecha']) ?> UTC · <?= e(['efectivo'=>'Efectivo al retirar','transferencia'=>'Transferencia','pasarela'=>'Tarjeta de prueba'][$cartReceipt['metodo']]??label($cartReceipt['metodo'])) ?></p>
    <strong>₡<?= e(number_format((float)$cartReceipt['total'],2,',','.')) ?></strong>
    <p>Pago: <?= e(label($cartReceipt['estado'])) ?><?= $cartReceipt['proveedor']==='demo_tienda'?' · Demostración sin cobro real':'' ?></p>
</section><?php endif ?>
<?php if (!$cartItems): ?><section class="card carrito-vacio"><span aria-hidden="true">🛒</span><h2>Tu carrito está vacío</h2><p>Descubre los artículos del estudio y añade tus favoritos.</p><a class="button" href="cliente.php?section=tienda">Ver productos</a></section><?php else: ?>
<ol class="pasos-compra" aria-label="Proceso de compra"><li <?= !$cartPayment?'aria-current="step"':'' ?>><span>1</span> Carrito</li><li <?= $cartPayment?'aria-current="step"':'' ?>><span>2</span> Pago</li><li><span>3</span> Confirmación</li></ol>
<?php if ($cartPayment): ?>
    <?php require __DIR__.'/cliente-pago.php'; ?>
<?php else: ?>
<div class="carrito-layout carrito-sin-resumen row g-0">
<section class="card carrito-articulos col-12">
    <div class="carrito-lista-titulo"><h2>Tu carrito</h2><span><?= e($cartCount) ?> artículos</span></div>
    <?php foreach ($cartItems as $item): ?><div class="linea-carrito">
        <?php if (isset($cartImages[$item['id_producto']])): ?><img class="foto-carrito" src="<?= e(imageUrl($cartImages[$item['id_producto']])) ?>" alt="<?= e($item['nombre']) ?>">
        <?php else: ?><div class="foto-carrito sin-foto-carrito" aria-hidden="true">▣</div><?php endif ?>
        <div class="carrito-articulo-info"><strong><?= e($item['nombre']) ?></strong><p>Precio por unidad: ₡<?= e(number_format((float)($item['precio']??0),2,',','.')) ?></p>
        <form method="post" action="cliente.php?section=carrito" class="actions cantidad-carrito">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="product" value="<?= e($item['id_producto']) ?>">
            <div class="selector-cantidad"><button type="button" data-quantity-change="-1" aria-label="Reducir cantidad de <?= e($item['nombre']) ?>" <?= $item['cantidad']<=1 ? 'disabled' : '' ?>>−</button>
            <label><span class="sr-only">Cantidad de <?= e($item['nombre']) ?></span><input type="number" name="quantity" value="<?= e($item['cantidad']) ?>" min="1" max="<?= e(max(1,min(99,(int)$item['stock_actual']))) ?>" required></label>
            <button type="button" data-quantity-change="1" aria-label="Aumentar cantidad de <?= e($item['nombre']) ?>" <?= $item['cantidad']>=min(99,(int)$item['stock_actual']) ? 'disabled' : '' ?>>＋</button></div>
            <button name="cart_action" value="update" data-update-quantity class="secondary">Actualizar</button><button name="cart_action" value="remove" class="quitar-carrito secondary" formnovalidate>Quitar</button>
        </form>
        </div><strong class="carrito-linea-precio">₡<?= e(number_format($item['centavos']/100,2,',','.')) ?></strong>
    </div><?php endforeach ?>
    <a class="seguir-comprando" href="cliente.php?section=tienda">← Seguir comprando</a>
</section>
</div>
<?php if ($profileMissing): ?><p class="notice error">Completa tu perfil de cliente con el administrador para comprar.</p><?php endif ?>
<?php $checkoutBarStep='carrito';$checkoutBarTotal=$cartTotal/100;$checkoutBarInfo=$cartCount.' artículos';require __DIR__.'/cliente-compra-barra.php'; ?>
<?php endif ?>
<?php endif ?>
<?php endif ?>
</div>
