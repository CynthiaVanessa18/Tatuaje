<?php if ($giftError): ?><p class="notice error" role="alert"><?= e($giftError) ?></p><?php endif ?>
<?php if ($giftNotice): ?><p class="notice" role="status"><?= e($giftNotice) ?></p><?php endif ?>
<a href="<?= e($clientEndpoint) ?>?section=tienda">← Volver a la tienda</a>
<section class="card gift-link"><h2>¿Recibiste una tarjeta?</h2><p>Vincúlala con el código que te entregaron. Las tarjetas enviadas a tu correo verificado aparecen automáticamente.</p>
<form method="post" action="<?= e($clientEndpoint) ?>?section=tarjetas"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><label>Código de regalo<input type="text" name="gift_code" maxlength="35" minlength="12" required autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="XXXX-XXXX-XXXX"><small>Con o sin guiones. También aceptamos los códigos anteriores.</small></label><button type="submit">Añadir tarjeta</button></form>
<?php if ($profileMissing): ?><p><a href="<?= e($clientEndpoint) ?>?section=cuenta">Completa tu cuenta</a> para vincular una tarjeta.</p><?php endif ?></section>
<div class="gift-card-list">
<?php foreach ($giftCards as $card): $usable=ClientGiftCards::usable($card);$expired=$card['fecha_vencimiento'] && $card['fecha_vencimiento']<=gmdate('Y-m-d H:i:s'); ?>
<article class="card gift-card"><p class="eyebrow">TINTA VIVA · TARJETA DE REGALO #<?= e($card['id_tarjeta']) ?></p><h2>Para <?= e($card['nombre_destinatario']) ?></h2>
<?php if ($card['mensaje']): ?><p><?= e($card['mensaje']) ?></p><?php endif ?>
<p>Saldo disponible <strong class="gift-balance"><?= e($card['moneda']) ?> <?= e(number_format((float)$card['saldo'],2,',','.')) ?></strong></p>
<p>Estado: <?= e(label($expired && $card['estado']!=='cancelada'?'vencida':$card['estado'])) ?> · <?= $card['fecha_vencimiento']?'Vence el '.e(date('d/m/Y',strtotime($card['fecha_vencimiento']))):'Sin vencimiento' ?></p>
<?php if ($usable): ?><form method="post" action="<?= e($clientEndpoint) ?>?section=carrito"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="cart_action" value="gift_apply"><input type="hidden" name="gift_card" value="<?= e($card['id_tarjeta']) ?>"><button>Usar en la tienda →</button></form><?php else: ?><small>Esta tarjeta no está disponible para pagar en la tienda.</small><?php endif ?>
</article>
<?php endforeach ?></div>
<?php if (!$giftCards && !$profileMissing): ?><p class="notice">Todavía no tienes tarjetas vinculadas. Añade una con su código para verla aquí.</p><?php endif ?>
