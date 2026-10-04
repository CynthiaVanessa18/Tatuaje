<div class="barra-pago-carrito" role="region" aria-label="Total y acciones de compra">
    <div class="barra-pago-contenido">
        <div class="barra-pago-info"><span><?= e($checkoutBarInfo) ?></span><small>Retiro en el estudio</small></div>
        <div class="barra-pago-total"><span>Total</span><strong>₡<?= e(number_format($checkoutBarTotal,2,',','.')) ?></strong></div>
        <?php if ($checkoutBarStep==='confirmacion'): ?>
            <a class="button btn btn-primary continuar-pago" href="cliente.php?section=tienda">Seguir comprando →</a>
        <?php elseif ($checkoutBarStep==='pago'): ?>
            <button type="submit" form="formulario-finalizar-compra" class="btn btn-primary continuar-pago" data-confirm-order <?= $cartError || $profileMissing ? 'disabled' : '' ?>>Confirmar pedido →</button>
        <?php elseif ($cartError || $profileMissing): ?>
            <button class="continuar-pago" disabled>Continuar al pago →</button>
        <?php else: ?>
            <a class="button btn btn-primary continuar-pago" href="cliente.php?section=carrito&amp;paso=pago">Continuar al pago →</a>
        <?php endif ?>
    </div>
</div>
