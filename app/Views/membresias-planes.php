<?php
$selectedMembership=(string)($membershipPlans[0]['id_plan']??'');
if ($error && in_array((string)($_POST['id_plan']??''),array_map('strval',array_column($membershipPlans,'id_plan')),true)) $selectedMembership=(string)$_POST['id_plan'];
$membershipIcons=[
'esencial'=>'<path d="m12 3 8 9-8 9-8-9Z"/><path d="m4 12 8-4 8 4-8 4Z"/>',
'plus'=>'<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/>',
'premium'=>'<path d="m3 6 5 5 4-7 4 7 5-5-2 13H5Z"/><path d="M6 16h12"/>',
];
?>
<section class="membership-workspace" data-bs-theme="dark">
<div class="membership-intro"><p class="eyebrow">MEMBRESÍAS DEL ESTUDIO</p><h2>Selecciona un plan</h2><p>Consulta sus beneficios y configura las cuotas mensuales o anuales.</p></div>
<div class="nav nav-pills membership-selector" role="tablist" aria-label="Tipos de membresía">
<?php foreach ($membershipPlans as $plan): $isSelected=(string)$plan['id_plan']===$selectedMembership; ?>
<button class="nav-link membership-option <?= $isSelected?'active':'' ?>" id="membership-tab-<?= e($plan['id_plan']) ?>" data-bs-toggle="pill" data-bs-target="#membership-pane-<?= e($plan['id_plan']) ?>" type="button" role="tab" aria-controls="membership-pane-<?= e($plan['id_plan']) ?>" aria-selected="<?= $isSelected?'true':'false' ?>">
<span class="membership-option-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><?= $membershipIcons[$plan['nivel']] ?></svg></span>
<span class="membership-option-text"><strong><?= e($plan['nombre']) ?></strong><small><?= e(ucfirst($plan['nivel'])) ?></small></span><span class="membership-option-arrow" aria-hidden="true">›</span>
</button>
<?php endforeach ?></div>
<div class="tab-content membership-content">
<?php foreach ($membershipPlans as $plan):
    $form=$error && (string)($_POST['id_plan']??'')===(string)$plan['id_plan'] ? array_merge($plan,$_POST) : $plan;
    $rules=$error && (string)($_POST['id_plan']??'')===(string)$plan['id_plan'] ? array_fill_keys(is_array($_POST['reglas']??null)?array_filter($_POST['reglas'],'is_string'):[],1) : $plan['reglas'];
    $ruleValues=(string)($_POST['id_plan']??'')===(string)$plan['id_plan'] && is_array($_POST['valores']??null) ? $_POST['valores'] : $plan['reglas'];
?>
<section class="tab-pane fade <?= (string)$plan['id_plan']===$selectedMembership?'show active':'' ?>" id="membership-pane-<?= e($plan['id_plan']) ?>" role="tabpanel" aria-labelledby="membership-tab-<?= e($plan['id_plan']) ?>" tabindex="0">
<div class="membership-panel">
<form method="post" action="administrador.php?module=planes" class="membership-editor" data-membership-editor>
<input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="configure"><input type="hidden" name="id_plan" value="<?= e($plan['id_plan']) ?>">
<div class="membership-panel-heading"><div><p class="eyebrow">EDITAR MEMBRESÍA</p><h3><?= e($plan['nombre']) ?></h3><p>Activa las reglas que incluye este plan y ajusta sus cantidades en la tabla.</p></div><span class="badge rounded-pill membership-state <?= $plan['activo']?'enabled':'' ?>"><?= $plan['activo']?'Activa':'Sin activar' ?></span></div>
<div class="row g-3 membership-fields">
<div class="col-12 col-md-6"><label class="form-label" for="plan-name-<?= e($plan['id_plan']) ?>">Nombre de la membresía</label><input class="form-control" id="plan-name-<?= e($plan['id_plan']) ?>" name="nombre" maxlength="100" required value="<?= e($form['nombre']) ?>"></div>
<div class="col-6 col-md-3"><label class="form-label" for="plan-mode-<?= e($plan['id_plan']) ?>">Modalidad</label><select class="form-select" id="plan-mode-<?= e($plan['id_plan']) ?>" name="modalidad"><?php foreach (['ambas'=>'Mensual y anual','mensual'=>'Solo mensual','anual'=>'Solo anual'] as $key=>$text): ?><option value="<?= e($key) ?>" <?= $form['modalidad']===$key?'selected':'' ?>><?= e($text) ?></option><?php endforeach ?></select></div>
<div class="col-6 col-md-3"><label class="form-label" for="plan-status-<?= e($plan['id_plan']) ?>">Estado</label><select class="form-select" id="plan-status-<?= e($plan['id_plan']) ?>" name="activo"><option value="0" <?= !$form['activo']?'selected':'' ?>>Inactiva</option><option value="1" <?= $form['activo']?'selected':'' ?>>Activa</option></select></div>
<div class="col-12 col-sm-6"><label class="form-label" for="plan-month-<?= e($plan['id_plan']) ?>">Cuota mensual (₡)</label><input class="form-control" id="plan-month-<?= e($plan['id_plan']) ?>" name="cuota_mensual" type="number" min="0" step="0.01" required value="<?= e($form['cuota_mensual']) ?>"></div>
<div class="col-12 col-sm-6"><label class="form-label" for="plan-year-<?= e($plan['id_plan']) ?>">Cuota anual total (₡)</label><input class="form-control" id="plan-year-<?= e($plan['id_plan']) ?>" name="cuota_anual" type="number" min="0" step="0.01" required value="<?= e($form['cuota_anual']) ?>"></div>
<div class="col-12"><label class="form-label" for="plan-description-<?= e($plan['id_plan']) ?>">Descripción para el cliente</label><textarea class="form-control" id="plan-description-<?= e($plan['id_plan']) ?>" name="descripcion" rows="2" maxlength="5000"><?= e($form['descripcion']) ?></textarea></div>
</div>
<div class="membership-rules-heading"><h4>Beneficios del plan</h4><span>Marca para incluir · Desmarca para quitar</span></div>
<div class="table-responsive"><table class="table align-middle membership-benefits membership-edit-table">
<caption class="visually-hidden">Editar beneficios de <?= e($plan['nombre']) ?></caption><thead><tr><th scope="col">Incluir</th><th scope="col">Regla precargada</th><th scope="col">Valor o cantidad</th></tr></thead><tbody>
<?php foreach (MembershipPlans::RULES as $code=>[$name,$unit,$description]): $included=isset($rules[$code]); $ruleId='plan-rule-'.$plan['id_plan'].'-'.$code; ?>
<tr class="<?= $included?'rule-selected':'' ?>" data-membership-rule>
<td><div class="form-check"><input class="form-check-input" type="checkbox" id="<?= e($ruleId) ?>" name="reglas[]" value="<?= e($code) ?>" <?= $included?'checked':'' ?> aria-label="Incluir <?= e($name) ?>" data-membership-toggle></div></td>
<th scope="row"><label for="<?= e($ruleId) ?>"><?= e($name) ?></label><small><?= e($description) ?></small></th>
<td><?php if ($unit!=='prioridad'): ?><label class="visually-hidden" for="<?= e($ruleId) ?>-value">Valor de <?= e($name) ?></label><div class="membership-rule-value"><input class="form-control" id="<?= e($ruleId) ?>-value" type="number" name="valores[<?= e($code) ?>]" min="<?= in_array($unit,['kits','sesiones'],true)?'1':'0.01' ?>" <?= $unit==='porcentaje'?'max="100"':'' ?> step="<?= in_array($unit,['kits','sesiones'],true)?'1':'0.01' ?>" value="<?= e($ruleValues[$code]??1) ?>" <?= $included?'required':'disabled' ?> data-membership-value><span><?= e(['porcentaje'=>'%','monto'=>'₡ por compra','sesiones'=>'sesiones','kits'=>'kits'][$unit]) ?></span></div><?php else: ?><span class="membership-priority-note">Prioridad de horario</span><?php endif ?></td>
</tr>
<?php endforeach ?></tbody></table></div>
<p class="membership-period-note">Las sesiones y kits corresponden al período contratado. Selecciona al menos un beneficio.</p>
<div class="membership-savebar"><span data-membership-save-status role="status">Los cambios se aplican al guardar.</span><button class="btn" type="submit">Guardar cambios</button></div>
</form>
</div></section>
<?php endforeach ?></div></section>
