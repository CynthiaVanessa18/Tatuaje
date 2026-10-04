<section class="card pago-principal shadow-sm">
    <div class="pago-encabezado"><div><p class="eyebrow">ÚLTIMO PASO</p><h2>¿Cómo quieres pagar?</h2><p>Elige tu método de pago para completar el pedido.</p></div><span class="pago-icono" aria-hidden="true">▣</span></div>
    <form id="formulario-finalizar-compra" method="post" action="cliente.php?section=carrito&amp;paso=pago" class="formulario-pago">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="cart_action" value="checkout"><input type="hidden" name="checkout_token" value="<?= e($_SESSION[$tokenKey]) ?>">
        <fieldset class="opciones-pago"><legend>Selecciona un método</legend>
            <?php foreach ([['pasarela','▤','Tarjeta','Pago de demostración, sin cobro real'],['efectivo','₡','Efectivo','Paga al retirar tus artículos'],['transferencia','⇄','Transferencia','Coordinación y verificación con el estudio']] as [$method,$icon,$caption,$detail]): ?>
                <label class="opcion-pago"><input type="radio" name="payment_method" value="<?= e($method) ?>" data-payment-choice required><span class="opcion-pago-icono" aria-hidden="true"><?= e($icon) ?></span><span><strong><?= e($caption) ?></strong><small><?= e($detail) ?></small></span><span class="opcion-pago-check" aria-hidden="true">✓</span></label>
            <?php endforeach ?>
        </fieldset>
        <div class="tarjeta-demo" data-demo-card hidden><h3>Prueba tu pago con tarjeta</h3><p>Selecciona una tarjeta simulada. No se solicitan datos de tarjetas personales.</p><label>Tarjeta de demostración<select name="demo_result"><option value="aprobado">Tarjeta de prueba · pago aprobado</option><option value="rechazado">Tarjeta de prueba · pago rechazado</option></select></label></div>
        <p class="pago-ayuda" data-payment-help>Selecciona una opción para continuar.</p>
        <?php if ($profileMissing): ?><p class="notice error">Completa tu perfil con el administrador para comprar.</p><?php endif ?>
        <a class="pago-volver" href="cliente.php?section=carrito">← Volver y revisar mi carrito</a>
    </form>
</section>
<?php $checkoutBarStep='pago';$checkoutBarTotal=$cartTotal/100;$checkoutBarInfo=$cartCount.' artículos';require __DIR__.'/cliente-compra-barra.php'; ?>
