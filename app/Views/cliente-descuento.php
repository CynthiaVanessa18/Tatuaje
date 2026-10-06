<?php if ($cartDiscount > 0 && $cartPromotion): ?>
<p class="descuento-linea" role="status"><span>Descuento total aplicado</span><strong>−₡<?= e(number_format($cartDiscount/100,2,',','.')) ?></strong></p>
<?php endif ?>
<?php foreach (($cartQuote['pending_promotions'] ?? []) as $pendingOffer): ?>
<section class="card resumen-promocion" aria-label="Condición de la promoción">
    <h2>Tu promoción aún no se aplica</h2>
    <p><?= e($pendingOffer['promotion']['titulo']) ?> · <?= e(StorePromotions::benefit($pendingOffer['promotion'])) ?></p>
    <p>Compra mínima: ₡<?= e(number_format((float)$pendingOffer['promotion']['minimo_compra'],2,',','.')) ?>. Añade ₡<?= e(number_format($pendingOffer['remaining']/100,2,',','.')) ?> al carrito para alcanzar el mínimo.</p>
    <small>El descuento se aplica a los artículos incluidos en esta promoción.</small>
</section>
<?php endforeach ?>
