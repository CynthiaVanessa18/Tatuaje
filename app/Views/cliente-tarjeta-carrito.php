<section class="card gift-cart"><h2>Tarjeta de regalo</h2>
<form method="post" action="<?= e($clientEndpoint) ?>?section=carrito<?= $cartPayment?'&amp;paso=pago':'' ?>"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="cart_action" value="gift_apply">
<label>Usar saldo<select name="gift_card"><option value="0">Sin tarjeta de regalo</option><?php foreach ($cartGiftCards as $gift): if (!ClientGiftCards::usable($gift)) continue; ?><option value="<?= e($gift['id_tarjeta']) ?>" <?= (int)$gift['id_tarjeta']===$cartGiftId?'selected':'' ?>>Tarjeta #<?= e($gift['id_tarjeta']) ?> · saldo ₡<?= e(number_format((float)$gift['saldo'],2,',','.')) ?></option><?php endforeach ?></select></label><button type="submit" class="secondary">Aplicar</button>
</form>
<?php if ($cartGiftCredit>0): ?><p role="status">Tarjeta aplicada: <strong>−₡<?= e(number_format($cartGiftCredit/100,2,',','.')) ?></strong>. El saldo se descontará al confirmar el pedido.</p><?php endif ?>
<a href="<?= e($clientEndpoint) ?>?section=tarjetas">Ver mis tarjetas o añadir una con su código</a></section>
