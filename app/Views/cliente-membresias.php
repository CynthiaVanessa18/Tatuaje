<?php if ($membershipError): ?><p class="notice error" role="alert"><?= e($membershipError) ?></p><?php endif ?>
<?php if ($membershipNotice??null): ?><p class="notice" role="status"><?= e($membershipNotice) ?></p><?php endif ?>
<?php
$currentMemberships=[];$pastMemberships=[];
foreach ($clientMembershipList as $owned) {
    if ($owned['estado']==='pendiente' || ($owned['estado']==='activa' && $owned['fecha_fin']>gmdate('Y-m-d H:i:s'))) $currentMemberships[]=$owned;
    else $pastMemberships[]=$owned;
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
<p class="eyebrow">CONFIRMAR MEMBRESÍA</p><h2><?= e($membershipQuote['plan']['nombre']) ?></h2>
<p><?= e($membershipQuote['plan']['descripcion']) ?></p>
<?php foreach ($membershipPlans as $selectedPlan): if ((int)$selectedPlan['id_plan']!==(int)$membershipQuote['plan']['id_plan']) continue; ?>
<ul><?php foreach ($selectedPlan['reglas'] as $code=>$value): if (!isset(MembershipPlans::RULES[$code])) continue; ?><li><?= e(MembershipPlans::describe($code,(string)$value,$selectedPlan['textos'][$code]['nombre']??null)) ?><?php if (isset($selectedPlan['textos'][$code]['descripcion'])): ?><small><?= e($selectedPlan['textos'][$code]['descripcion']) ?></small><?php endif ?></li><?php endforeach ?></ul>
<?php endforeach ?>
<p>Modalidad: <strong><?= e(ucfirst($membershipQuote['mode'])) ?></strong> · Duración: <?= e($membershipQuote['days']) ?> días</p>
<p class="membership-total">Total a pagar: <strong>₡<?= e(number_format($membershipQuote['total']/100,2,',','.')) ?></strong></p>
<p>Compra por un período. No hay renovación ni cobro automático.</p>
<form method="post" action="<?= e($clientEndpoint) ?>?<?= e(http_build_query(['section'=>'membresias','plan'=>$membershipPlanId,'modalidad'=>$membershipMode])) ?>">
<input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="checkout_token" value="<?= e($_SESSION[$membershipTokenKey]) ?>"><input type="hidden" name="expected_total" value="<?= e($membershipQuote['total']) ?>">
<input type="hidden" name="membership_action" value="subscribe">
<label>Método de pago<select name="payment_method" required><option value="" selected disabled>Selecciona una opción</option value="pasarela">Tarjeta de demostración (sin cobro real)</option><option value="efectivo">Efectivo en el estudio</option><option value="transferencia">Transferencia coordinada con el estudio</option></select></label>
<label>Resultado de la tarjeta de demostración<select name="demo_result"><option value="aprobado">Pago simulado aprobado</option><option value="rechazado">Pago simulado rechazado</option></select></label>
<p>Para efectivo o transferencia, la membresía queda pendiente hasta que el estudio confirme el pago.</p>
<?php if ($profileMissing): ?><p class="notice error">Completa tus datos antes de confirmar. <a href="<?= e($clientEndpoint) ?>?section=cuenta">Completar mi cuenta</a></p><?php endif ?>
<button type="submit" <?= $profileMissing || $hasCurrentMembership?'disabled':'' ?>>Confirmar suscripción</button>
<?php if ($hasCurrentMembership): ?><p>Ya tienes una membresía activa o pendiente. Puedes administrarla en Mis membresías.</p><?php endif ?>
<a href="<?= e($clientEndpoint) ?>?section=membresias">Volver a los planes</a>
</form></section>
<?php else: ?>
<?php if ($hasCurrentMembership): require __DIR__.'/cliente-membresia-actual.php'; ?>
<?php else: ?>
<?php if ($profileMissing): ?><p class="notice">Puedes revisar los planes. Antes de pagar, <a href="<?= e($clientEndpoint) ?>?section=cuenta">completa los datos de tu cuenta</a>.</p><?php endif ?>
<?php require __DIR__.'/cliente-membresias-comparacion.php'; ?>
<?php if (!$membershipPlans): ?><p class="notice">Estamos preparando nuestras membresías. Consulta al estudio para conocer su disponibilidad.</p><?php endif ?>
<?php endif ?>
<?php if ($pastMemberships): ?><details class="card membership-history"><summary>Historial de membresías</summary><?php foreach ($pastMemberships as $past): ?><article class="membership-owned"><h3><?= e($past['descripcion']) ?></h3><p><?= e(label($past['estado']==='activa'?'vencida':$past['estado'])) ?> · ₡<?= e(number_format((float)$past['total'],2,',','.')) ?></p></article><?php endforeach ?></details><?php endif ?>
<?php endif ?>
