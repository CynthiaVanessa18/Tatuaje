<div class="barra-pago-carrito" role="region" aria-label="Total y acciones de compra">
    <div class="barra-pago-contenido">
        <div class="barra-pago-info"><span><?= e($checkoutBarInfo) ?></span><small>Retiro en el estudio</small></div>
        <div class="barra-pago-total">
            <?php if ($checkoutBarStep!=='confirmacion' && ($cartDiscount ?? 0)>0): ?>
                <small>Subtotal: ₡<?= e(number_format($cartSubtotal/100,2,',','.')) ?></small>
                <small>Descuento: −₡<?= e(number_format($cartDiscount/100,2,',','.')) ?></small>
            <?php endif ?>
            <?php if ($checkoutBarStep!=='confirmacion' && ($cartGiftCredit??0)>0): ?><small>Tarjeta de regalo: −₡<?= e(number_format($cartGiftCredit/100,2,',','.')) ?></small><?php endif ?>
            <span><?= $checkoutBarStep!=='confirmacion'?(($cartGiftCredit??0)>0?'Total a pagar':(($cartDiscount??0)>0?'Total con descuento':'Total')):'Total' ?></span><strong>₡<?= e(number_format($checkoutBarTotal,2,',','.')) ?></strong>
        </div>
        <?php if ($checkoutBarStep==='confirmacion'): ?>
            <a class="button btn btn-primary continuar-pago" href="<?= e($clientEndpoint ?? '../index.php') ?>?section=tienda">Seguir comprando →</a>
        <?php elseif ($checkoutBarStep==='pago'): ?>
            <button type="submit" form="formulario-finalizar-compra" class="btn btn-primary continuar-pago" data-confirm-order <?= $cartError || $profileMissing ? 'disabled' : '' ?>>Confirmar pedido →</button>
        <?php elseif ($cartError || $profileMissing): ?>
            <button class="continuar-pago" disabled>Continuar al pago →</button>
        <?php else: ?>
            <a class="button btn btn-primary continuar-pago" href="<?= e($clientEndpoint ?? '../index.php') ?>?section=carrito&amp;paso=pago">Continuar al pago →</a>
        <?php endif ?>
    </div>
</div>
