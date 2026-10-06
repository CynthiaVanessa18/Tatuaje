<?php
$availableGifts=array_values(array_filter($cartGiftCards,static fn(array $gift)=>ClientGiftCards::usable($gift) || ($gift['estado']==='pendiente' && (!$gift['fecha_vencimiento'] || $gift['fecha_vencimiento']>gmdate('Y-m-d H:i:s')))));
?>
<?php if ($availableGifts): ?>
<section class="store-gifts" aria-labelledby="store-gifts-title">
<div class="store-gifts-heading"><div><span class="store-gifts-icon" aria-hidden="true">🎁</span><h2 id="store-gifts-title">Tienes <?= count($availableGifts) ?> <?= count($availableGifts)===1?'tarjeta de regalo por usar':'tarjetas de regalo por usar' ?></h2><p>Elige tu regalo y aprovecha su saldo al comprar.</p></div><a href="<?= e($clientEndpoint) ?>?section=tarjetas">Ver todas →</a></div>
<div class="store-gifts-list">
<?php foreach ($availableGifts as $gift): $selected=(int)$gift['id_tarjeta']===$cartGiftId;$usable=ClientGiftCards::usable($gift); ?>
<article class="store-gift <?= $selected?'store-gift-selected':'' ?>">
<div class="store-gift-value"><span>Tarjeta #<?= e($gift['id_tarjeta']) ?></span><strong>₡<?= e(number_format((float)$gift['saldo'],2,',','.')) ?></strong><small><?= $gift['fecha_vencimiento']?'Vence '.e(date('d/m/Y',strtotime($gift['fecha_vencimiento']))):'Sin vencimiento' ?></small></div>
<?php if ($usable): ?>
<form method="post" action="<?= e($clientEndpoint) ?>?<?= e(http_build_query(['section'=>'tienda','categoria'=>$storeCategory,'q'=>$storeSearch,'page'=>$storePage])) ?>"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="cart_action" value="gift_apply"><input type="hidden" name="gift_card" value="<?= $selected?'0':e($gift['id_tarjeta']) ?>"><button type="submit" class="<?= $selected?'secondary':'' ?>"><?= $selected?'✓ Seleccionada · Quitar':'Usar tarjeta' ?></button></form>
<?php else: ?><span class="store-gift-pending">Pendiente de activación</span><?php endif ?>
</article>
<?php endforeach ?></div>
<?php if ($cartGiftId && $cartGiftCredit>0): ?><p class="store-gifts-applied" role="status">✓ Tu regalo cubre ₡<?= e(number_format($cartGiftCredit/100,2,',','.')) ?> del carrito actual.</p><?php elseif ($cartGiftId): ?><p class="store-gifts-applied" role="status">✓ Tarjeta seleccionada. Añade productos y usaremos su saldo al confirmar tu pedido.</p><?php endif ?>
</section>
<?php endif ?>
