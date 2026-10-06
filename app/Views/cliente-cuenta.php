<?php if ($accountNotice): ?><p class="notice" role="status"><?= e($accountNotice) ?></p><?php endif ?>
<?php if ($accountError): ?><p class="notice error" role="alert"><?= e($accountError) ?></p><?php endif ?>
<section class="card cuenta-formulario"><h2>Datos de mi cuenta</h2>
<form method="post" action="<?= e($clientEndpoint) ?>?section=cuenta">
<input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
<div class="form-grid">
<?php foreach (['nombre'=>['Nombre',100,'given-name'],'apellidos'=>['Apellidos',150,'family-name'],'usuario'=>['Usuario',80,'username'],'correo'=>['Correo electrónico',254,'email'],'telefono'=>['Teléfono (opcional)',25,'tel']] as $key=>[$caption,$max,$autocomplete]): ?>
<label><?= e($caption) ?><input type="<?= $key==='correo'?'email':($key==='telefono'?'tel':'text') ?>" name="<?= e($key) ?>" maxlength="<?= e($max) ?>" autocomplete="<?= e($autocomplete) ?>" <?= $key!=='telefono'?'required':'' ?> value="<?= e($clientAccountData[$key]??'') ?>"></label>
<?php endforeach ?></div>
<h3>Contraseña</h3><p>Para cambiar tu usuario, correo o contraseña, ingresa tu contraseña actual.</p>
<label>Contraseña actual<input type="password" name="clave_actual" autocomplete="current-password"></label>
<details class="cuenta-cambiar-clave"><summary>Cambiar mi contraseña</summary><p>Deja estos campos vacíos si quieres conservar tu contraseña. La nueva debe tener al menos 12 caracteres.</p>
<label>Nueva contraseña<input type="password" name="nueva_clave" minlength="12" maxlength="72" autocomplete="new-password"></label>
<label>Confirmar nueva contraseña<input type="password" name="confirmar_clave" minlength="12" maxlength="72" autocomplete="new-password"></label>
</details><button type="submit">Guardar cambios</button>
</form></section>
