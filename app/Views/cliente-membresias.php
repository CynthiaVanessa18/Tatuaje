<?php if ($membershipError): ?><p class="notice error" role="alert"><?= e($membershipError) ?></p><?php endif ?>
<?php if ($membershipNotice??null): ?><p class="notice" role="status"><?= e($membershipNotice) ?></p><?php endif ?>
<?php
$currentMemberships=[];
foreach ($clientMembershipList as $owned) {
    if ($owned['estado']==='pendiente' || ($owned['estado']==='activa' && $owned['fecha_fin']>gmdate('Y-m-d H:i:s'))) $currentMemberships[]=$owned;
}
$hasCurrentMembership=(bool)$currentMemberships;
?>
<?php if ($membershipReceipt): ?>
<section class="card membership-confirmation" role="status">
<h2><?= $membershipReceipt['pago_estado']==='rechazado'?'Pago de prueba rechazado':($membershipReceipt['estado']==='activa'?'Membresía activada':'Solicitud de membresía registrada') ?></h2>
<p><?= e($membershipReceipt['descripcion']) ?> · ₡<?= e(number_format((float)$membershipReceipt['total'],2,',','.')) ?></p>
<p><?= $membershipReceipt['pago_estado']==='rechazado'?'La membresía quedó cancelada. Puedes seleccionar el plan e intentar de nuevo.':($membershipReceipt['estado']==='activa'?'Pago simulado aprobado, sin cobro real. Tu membresía de demostración está activa.':'Tu membresía está pendiente de pago y activación. Coordina el pago con el estudio; el personal verificará el pago y la fecha de inicio.') ?></p>
<a class="button" href="<?= e($clientEndpoint) ?>?section=membresias">Ver mis membresías</a>
</section>
<?php elseif ($membershipQuote): ?>
<section class="card membership-checkout">
<?php require_once __DIR__.'/cliente-membresia-iconos.php'; ?>
<div class="membership-checkout-benefits">
<p class="eyebrow">CONFIRMAR MEMBRESÍA</p><h2><?= e($membershipQuote['plan']['nombre']) ?></h2>
<p><?= e($membershipQuote['plan']['descripcion']) ?></p>
<?php foreach ($membershipPlans as $selectedPlan): if ((int)$selectedPlan['id_plan']!==(int)$membershipQuote['plan']['id_plan']) continue; ?>
<ul class="membership-checkout-benefit-list"><?php foreach ($selectedPlan['reglas'] as $code=>$value): if (!isset(MembershipPlans::RULES[$code]) || (float)$value<=0) continue; ?><li><?= membershipClientIcon($code) ?><div><?= e(MembershipPlans::describe($code,(string)$value,$selectedPlan['textos'][$code]['nombre']??null)) ?><?php if (isset($selectedPlan['textos'][$code]['descripcion'])): ?><small><?= e($selectedPlan['textos'][$code]['descripcion']) ?></small><?php endif ?></div></li><?php endforeach ?></ul>
<?php endforeach ?>
<p>Modalidad: <strong><?= e(ucfirst($membershipQuote['mode'])) ?></strong> · Duración: <?= e($membershipQuote['days']) ?> días</p>
</div>
<div class="membership-checkout-payment">
<p class="membership-total"><?= membershipClientIcon('pago') ?><span>Total a pagar:</span> <strong>₡<?= e(number_format($membershipQuote['total']/100,2,',','.')) ?></strong></p>
<form method="post" action="<?= e($clientEndpoint) ?>?<?= e(http_build_query(['section'=>'membresias','plan'=>$membershipPlanId,'modalidad'=>$membershipMode])) ?>">
<input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="checkout_token" value="<?= e($_SESSION[$membershipTokenKey]) ?>"><input type="hidden" name="expected_total" value="<?= e($membershipQuote['total']) ?>">
<input type="hidden" name="membership_action" value="subscribe">
<input type="hidden" name="payment_method" value="pasarela">
<p>Método de pago: <strong>Tarjeta de demostración (sin cobro real)</strong></p>
<label>Resultado de la tarjeta de demostración<select name="demo_result"><option value="aprobado">Pago simulado aprobado</option><option value="rechazado">Pago simulado rechazado</option></select></label>
<p>La membresía se activa automáticamente cuando el pago con tarjeta es aprobado.</p>
<section class="membership-renewal">
<div class="membership-renewal-heading"><h3>Renovación automática</h3><span>Opcional</span></div>
<fieldset class="membership-renewal-options" data-renewal-options>
<legend>Elige cómo continuar al vencer</legend>
<label><input type="radio" name="renewal_enabled" value="1" required <?= ($_POST['renewal_enabled']??'0')==='1'?'checked':'' ?>><span>Activar renovación automática</span></label>
<label><input type="radio" name="renewal_enabled" value="0" required <?= ($_POST['renewal_enabled']??'0')==='0'?'checked':'' ?>><span>No renovar automáticamente</span></label>
</fieldset>
<details><summary>Términos y condiciones</summary><ul>
<li>El pago inicial y las renovaciones son de demostración, sin cobros reales ni almacenamiento de datos de tarjetas.</li>
<li>Al autorizar la renovación, aceptas un pago simulado de ₡<?= e(number_format($membershipQuote['total']/100,2,',','.')) ?> cada <?= e($membershipQuote['days']) ?> días. El importe autorizado se conserva mientras la renovación esté activa.</li>
<li>Al vencer, el sistema revisa la renovación cada hora mientras la computadora y MySQL estén disponibles. El nuevo período comienza al aprobarse el pago simulado. No se cobran períodos anteriores si el sistema estuvo apagado.</li>
<li>Puedes desactivar la renovación antes del vencimiento y conservar los beneficios hasta terminar el período pagado. Sin renovación, la membresía vence al finalizar ese período.</li>
<li>Si el pago es rechazado o el plan ya no está disponible, no se renueva y se detienen los reintentos automáticos.</li>
<li>Cancelar la membresía desactiva los beneficios inmediatamente y detiene las renovaciones. No genera reembolsos automáticos.</li>
</ul></details>
<label class="membership-renewal-consent"><input type="checkbox" name="accept_renewal_terms" value="1" <?= ($_POST['accept_renewal_terms']??'')==='1'?'checked':'' ?>><span>Acepto los términos de renovación (obligatorio si la activo).</span></label>
</section>
<?php if ($profileMissing): ?><p class="notice error">Completa tus datos antes de confirmar. <a href="<?= e($clientEndpoint) ?>?section=cuenta">Completar mi cuenta</a></p><?php endif ?>
<button class="membership-checkout-submit" type="submit" <?= $profileMissing || $hasCurrentMembership?'disabled':'' ?>>Confirmar suscripción <span aria-hidden="true">→</span></button>
<?php if ($hasCurrentMembership): ?><p>Ya tienes una membresía activa o pendiente. Puedes administrarla en Mis membresías.</p><?php endif ?>
<a class="membership-checkout-back" href="<?= e($clientEndpoint) ?>?section=membresias"><span aria-hidden="true">‹</span> Volver a los planes</a>
</form></div></section>
<?php else: ?>
<?php if ($hasCurrentMembership): require __DIR__.'/cliente-membresia-actual.php'; ?>
<?php else: ?>
<?php if ($profileMissing): ?><p class="notice">Puedes revisar los planes. Antes de pagar, <a href="<?= e($clientEndpoint) ?>?section=cuenta">completa los datos de tu cuenta</a>.</p><?php endif ?>
<?php require __DIR__.'/cliente-membresias-comparacion.php'; ?>
<?php if (!$membershipPlans): ?><p class="notice">Estamos preparando nuestras membresías. Consulta al estudio para conocer su disponibilidad.</p><?php endif ?>
<?php endif ?>

<?php endif ?>
