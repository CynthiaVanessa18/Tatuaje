<?php require_once __DIR__.'/cliente-membresia-iconos.php'; ?>
<?php foreach ($currentMemberships as $membership):
    $pending=$membership['estado']==='pendiente';
    $annual=str_ends_with(trim($membership['descripcion']),'| anual');
    $orderedRules=array_replace(array_fill_keys(['brillo','color','hidratacion','kit_regalo','kit_descuento','mercancia_porcentaje','mercancia_monto','prioridad','retoques'],null),$membership['reglas']??[]);
    $paymentId='membership-payment-'.(int)$membership['id_membresia'];
?>
<section class="membership-showcase" aria-label="<?= e($membership['nombre']) ?>">
    <article class="membership-plan-card">
        <span class="membership-corner corner-top-left" aria-hidden="true">❧</span><span class="membership-corner corner-top-right" aria-hidden="true">❧</span>
        <h2><?= e(preg_match('/^Tinta\s+Viva\b/iu',$membership['nombre'])?$membership['nombre']:'Tinta Viva · '.$membership['nombre']) ?></h2><p class="membership-plan-period">Plan <?= $annual?'anual':'mensual' ?></p>
        <img class="membership-plan-emblem" src="<?= e($publicPrefix) ?>assets/images/membership-rose-emblem.png" alt="" width="400" height="400">
        <p class="membership-state <?= $pending?'is-pending':'is-active' ?>"><?= membershipClientIcon($pending?'pendiente':'activa') ?><span><?= $pending?'Pendiente de activación':'Membresía activa' ?></span></p>
        <p class="membership-payment-status"><?= membershipClientIcon('pago') ?> <?= e(label('pago_'.$membership['pago_estado'])) ?></p>
        <?php if ($pending): ?><p class="membership-status-copy">Coordina el pago con el estudio<br>para activar tus beneficios.</p>
        <details class="membership-payment-help" id="<?= e($paymentId) ?>"><summary>Coordinar pago <span aria-hidden="true">→</span></summary><div><p>Indica tu membresía #<?= e($membership['id_membresia']) ?> al coordinar con el estudio.</p><p><strong><?= e(label($membership['metodo'])) ?> · ₡<?= e(number_format((float)$membership['total'],2,',','.')) ?></strong></p><p>El personal confirmará el pago y la fecha de activación.</p><?php $studioPhone=trim((string)getenv('STUDIO_PHONE')); if (preg_match('/^\+?[\d ()-]{7,25}$/D',$studioPhone)): ?><a href="tel:<?= e(preg_replace('/[^+\d]/','',$studioPhone)) ?>">Llamar al estudio</a><?php endif ?></div></details>
        <?php else: ?><p class="membership-status-copy">Disfruta del cuidado de tu arte.</p><p class="membership-validity">Del <?= e((new DateTimeImmutable($membership['fecha_inicio'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Costa_Rica'))->format('d/m/Y')) ?> al <?= e((new DateTimeImmutable($membership['fecha_fin'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Costa_Rica'))->format('d/m/Y')) ?></p><?php endif ?>
        <?php if ($membership['metodo']==='pasarela'): ?><p class="membership-demo-note">Pago de demostración, sin cobro real.</p><?php endif ?>
        <details class="membership-cancel"><summary>Cancelar membresía</summary><p>La cancelación desactiva los beneficios y conserva el historial de pagos. No genera un reembolso automático.</p><form method="post" action="<?= e($clientEndpoint) ?>?section=membresias"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="membership_action" value="cancel"><input type="hidden" name="id_membresia" value="<?= e($membership['id_membresia']) ?>"><label class="membership-cancel-confirm"><input type="checkbox" name="confirm_cancel" value="1" required> Confirmo que quiero cancelar esta membresía.</label><button type="submit" class="secondary">Confirmar cancelación</button></form></details>
        <span class="membership-corner corner-bottom-left" aria-hidden="true">❧</span><span class="membership-corner corner-bottom-right" aria-hidden="true">❧</span><span class="membership-card-star" aria-hidden="true">✧</span>
    </article>
    <article class="membership-benefits-panel">
        <div class="membership-benefits-heading"><h2>Beneficios de tu plan</h2><span aria-hidden="true">✧</span></div>
        <div class="membership-benefit-grid">
        <?php foreach ($orderedRules as $code=>$value): if ($value===null || (float)$value<=0 || !isset(MembershipPlans::RULES[$code])) continue;
            [$defaultName,$unit,$hint]=MembershipPlans::RULES[$code];
            $name=$membership['textos'][$code]['nombre']??(['hidratacion'=>'Hidratación de piel','kit_regalo'=>'Kit de cuidado'][$code]??$defaultName);
            $quantity=(int)$value;
            $formatted=rtrim(rtrim(number_format((float)$value,2,',','.'),'0'),',');
            $caption=match($unit) {'sesiones'=>$quantity.($quantity===1?' sesión':' sesiones').' por período','kits'=>$quantity.($quantity===1?' kit de regalo':' kits de regalo').' por período','porcentaje'=>$formatted.' %','monto'=>'₡'.number_format((float)$value,2,',','.'),default=>'Incluido en tu plan'};
        ?>
            <div class="membership-benefit-tile"><span class="membership-benefit-icon"><?= membershipClientIcon($code) ?></span><h3><?= e($name) ?></h3><p><?= e($caption) ?></p></div>
        <?php endforeach ?>
        <?php if (empty($membership['reglas'])): ?><p class="membership-benefits-empty">Consulta con el estudio los beneficios incluidos en tu plan.</p><?php endif ?>
        </div>
        <p class="membership-benefits-notice"><?= membershipClientIcon('info') ?><span><?= $pending?'Tus beneficios estarán disponibles al confirmar el pago.':'Servicios sujetos a valoración y disponibilidad del estudio.' ?></span></p>
        <?php if (!empty($membership['reglas'])): ?><details class="membership-benefit-conditions"><summary>Ver condiciones de los beneficios</summary><ul><?php foreach ($orderedRules as $code=>$value): if ($value===null || (float)$value<=0 || !isset(MembershipPlans::RULES[$code])) continue; ?><li><strong><?= e($membership['textos'][$code]['nombre']??MembershipPlans::RULES[$code][0]) ?>:</strong> <?= e($membership['textos'][$code]['descripcion']??MembershipPlans::RULES[$code][2]) ?></li><?php endforeach ?></ul></details><?php endif ?>
    </article>
</section>
<?php endforeach ?>
