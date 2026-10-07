<?php if ($membershipPlans):
    $firstPlan=$membershipPlans[0];
    $defaultMode=$firstPlan['modalidad']==='anual'?'anual':'mensual';
    $formatMembershipPrice=static fn($value)=>'₡'.number_format((float)$value,2,',','.');
?>
<form class="membership-comparison card" method="get" action="<?= e($clientEndpoint) ?>" data-membership-comparison>
<input type="hidden" name="section" value="membresias">
<p class="membership-scroll-hint" id="membership-scroll-hint">Desliza la tabla hacia los lados para comparar los planes.</p>
<div class="table-scroll" tabindex="0" role="region" aria-label="Comparación de membresías" aria-describedby="membership-scroll-hint">
<table class="membership-table">
<caption>Compara los beneficios y selecciona tu plan</caption>
<thead><tr><th scope="col">Beneficios incluidos</th>
<?php foreach ($membershipPlans as $index=>$plan): ?>
<th scope="col" data-plan-column="<?= e($plan['id_plan']) ?>">
<label class="membership-plan-choice">
<input type="radio" name="plan" value="<?= e($plan['id_plan']) ?>" required <?= $index===0?'checked':'' ?> data-mode="<?= e($plan['modalidad']) ?>" data-name="<?= e($plan['nombre']) ?>" data-monthly="<?= e($formatMembershipPrice($plan['cuota_mensual'])) ?>" data-yearly="<?= e($formatMembershipPrice($plan['cuota_anual'])) ?>">
<span><?= e(ucfirst($plan['nivel'])) ?></span>
</label>
<?php if ($plan['modalidad']!=='anual'): ?><p class="membership-plan-price"><strong><?= e($formatMembershipPrice($plan['cuota_mensual'])) ?></strong><small> / mes</small></p><?php endif ?>
<?php if ($plan['modalidad']!=='mensual'): ?><p class="membership-plan-price"><strong><?= e($formatMembershipPrice($plan['cuota_anual'])) ?></strong><small> / año</small></p><?php endif ?>
</th>
<?php endforeach ?></tr></thead>
<tbody>
<?php foreach (MembershipPlans::RULES as $code=>[$benefit,$unit,$hint]): ?>
<tr><th scope="row"><?= e($benefit) ?></th>
<?php foreach ($membershipPlans as $plan):
    $value=$plan['reglas'][$code]??'0';$included=(float)$value>0;
    $amount=match($unit) {
        'porcentaje'=>rtrim(rtrim(number_format((float)$value,2,',','.'),'0'),',').' %',
        'monto'=>$formatMembershipPrice($value),
        'sesiones'=>number_format((float)$value,0,',','.').' sesión(es)',
        'kits'=>number_format((float)$value,0,',','.').' kit(s)',
        default=>'Incluido',
    };
?>
<td data-plan-column="<?= e($plan['id_plan']) ?>"><?php if ($included): ?><span class="membership-check" aria-hidden="true">✓</span><span class="membership-benefit-value"><?= e($amount) ?></span><?php if (isset($plan['textos'][$code])): ?><small><strong><?= e($plan['textos'][$code]['nombre']) ?></strong><br><?= e($plan['textos'][$code]['descripcion']) ?></small><?php endif ?><?php else: ?><span class="membership-excluded">No incluido</span><?php endif ?></td>
<?php endforeach ?></tr>
<?php endforeach ?>
</tbody></table></div>
<div class="membership-purchase">
<label>Modalidad de pago<select name="modalidad" required>
<option value="mensual" <?= $defaultMode==='mensual'?'selected':'' ?>>Mensual</option>
<option value="anual" <?= $defaultMode==='anual'?'selected':'' ?>>Anual</option>
</select></label>
<p class="membership-selection" role="status" aria-live="polite" data-membership-summary><?= e($firstPlan['nombre']) ?> · <?= e($formatMembershipPrice($firstPlan[$defaultMode==='mensual'?'cuota_mensual':'cuota_anual'])) ?> / <?= $defaultMode==='mensual'?'mes':'año' ?></p>
<button type="submit">Comprar membresía →</button>
</div>
<p class="membership-terms">Las cantidades de sesiones y kits corresponden al período contratado. Los servicios están sujetos a valoración y disponibilidad. Sin renovación automática.</p>
<details class="membership-benefit-details"><summary>Ver condiciones de los beneficios</summary><?php foreach ($membershipPlans as $plan): ?><h3><?= e($plan['nombre']) ?></h3><ul><?php foreach ($plan['reglas'] as $code=>$value): if (!isset(MembershipPlans::RULES[$code])) continue; ?><li><strong><?= e($plan['textos'][$code]['nombre']??MembershipPlans::RULES[$code][0]) ?>:</strong> <?= e($plan['textos'][$code]['descripcion']??MembershipPlans::RULES[$code][2]) ?></li><?php endforeach ?></ul><?php endforeach ?></details>
<noscript><p>Elige una modalidad disponible para tu plan según las cuotas de la tabla. El importe se confirma en el siguiente paso.</p></noscript>
</form>
<?php endif ?>
