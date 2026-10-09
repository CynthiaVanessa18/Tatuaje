<?php require_once __DIR__.'/cliente-membresia-iconos.php'; ?>
<?php if ($giftError): ?><p class="notice error" role="alert"><?= e($giftError) ?></p><?php endif ?>
<?php if ($giftNotice): ?><p class="notice" role="status"><?= e($giftNotice) ?></p><?php endif ?>
<a class="gift-back" href="<?= e($clientEndpoint) ?>?section=tienda">← Volver a la tienda</a>
<section class="card gift-link">
<div class="gift-link-heading"><span class="gift-link-icon"><?= membershipClientIcon('kit_regalo') ?></span><div><h2>¿Recibiste una <span>tarjeta?</span></h2><p>Vincúlala con el código que te entregaron. Las tarjetas enviadas a tu correo verificado aparecen automáticamente.</p></div></div>
<form method="post" action="<?= e($clientEndpoint) ?>?section=tarjetas">
<input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
<label for="gift-code">Código de regalo</label>
<div class="gift-code-row"><div class="gift-code-input"><?= membershipClientIcon('kit_descuento') ?><input id="gift-code" type="text" name="gift_code" maxlength="35" minlength="12" required autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="XXXX-XXXX-XXXX"></div><button type="submit"><?= membershipClientIcon('kit_regalo') ?> Añadir tarjeta</button></div>
<small>Con o sin guiones. También aceptamos los códigos anteriores.</small>
</form>
<?php if ($profileMissing): ?><p><a href="<?= e($clientEndpoint) ?>?section=cuenta">Completa tu cuenta</a> para vincular una tarjeta.</p><?php endif ?>
</section>
<h2 class="gift-list-title">Mis tarjetas de regalo</h2>
<div class="gift-card-list">
<?php foreach ($giftCards as $index=>$card):
$usable=ClientGiftCards::usable($card);
$expired=$card['fecha_vencimiento'] && $card['fecha_vencimiento']<=gmdate('Y-m-d H:i:s');
$displayState=$expired && $card['estado']!=='cancelada'?'vencida':$card['estado'];
$giftDate=static fn(string $value):string=>(new DateTimeImmutable($value,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Costa_Rica'))->format('d/m/Y');
?>
<article class="card gift-card gift-card--<?= e($displayState) ?>">
<div class="gift-card-art <?= $index%2?'gift-card-art--butterfly':'' ?>" aria-hidden="true"><span class="gift-art-mark"><?= $index%2?'ஐ':'❧' ?></span><strong>TINTA <span>VIVA</span></strong><small>TARJETA DE REGALO</small><span class="gift-art-flower">✦</span></div>
<div class="gift-card-content"><p class="eyebrow">TINTA VIVA · TARJETA DE REGALO #<?= e($card['id_tarjeta']) ?></p><h2>Para <?= e($card['nombre_destinatario']) ?></h2>
<?php if ($card['mensaje']): ?><p class="gift-message"><?= e($card['mensaje']) ?></p><?php endif ?>
<div class="gift-card-facts">
<div class="gift-fact gift-fact--balance"><?= membershipClientIcon('mercancia_monto') ?><div><span>Saldo disponible</span><strong class="gift-balance"><?= e($card['moneda']) ?> <?= e(number_format((float)$card['saldo'],2,',','.')) ?></strong></div></div>
<div class="gift-fact"><?= membershipClientIcon('prioridad') ?><div><span>Fecha de emisión</span><strong><?= e($giftDate($card['fecha_emision'])) ?></strong></div></div>
<div class="gift-fact"><?= membershipClientIcon('pendiente') ?><div><span>Fecha de vencimiento</span><strong><?= $card['fecha_vencimiento']?e($giftDate($card['fecha_vencimiento'])):'Sin vencimiento' ?></strong></div></div>
<?php if ($usable): ?><form method="post" action="<?= e($clientEndpoint) ?>?section=carrito"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="cart_action" value="gift_apply"><input type="hidden" name="gift_card" value="<?= e($card['id_tarjeta']) ?>"><button type="submit"><?= membershipClientIcon('mercancia_porcentaje') ?> Usar en la tienda</button></form><?php endif ?>
</div>
<p class="gift-status"><span class="gift-status-dot" aria-hidden="true"></span>Estado: <strong><?= e(label($displayState)) ?></strong><span class="gift-status-divider">|</span><?= $card['fecha_vencimiento']?'Vence el '.e($giftDate($card['fecha_vencimiento'])):'Sin vencimiento' ?></p>
<?php if (!$usable): ?><small class="gift-unavailable">Esta tarjeta no está disponible para pagar en la tienda.</small><?php endif ?>
</div></article>
<?php endforeach ?></div>
<?php if (($giftCardTotal??0)>0): renderPagination($giftCardTotal,$giftCardPage,array_replace($_GET,['section'=>'tarjetas']),'gift_page',$giftCardPageSize); endif ?>
<?php if (!$giftCards && !$profileMissing): ?><p class="notice">No tienes tarjetas activas con saldo disponible. Si recibiste un regalo, añádelo con su código para verlo aquí.</p><?php endif ?>
