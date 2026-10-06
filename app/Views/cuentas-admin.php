<?php if (in_array($mode,['create','edit'],true)): $editing=$mode==='edit'; ?>
<section class="card"><h2><?= $editing?'Editar cuenta':'Crear cuenta' ?></h2>
<form method="post" action="administrador.php?module=cuentas" class="form-grid">
<input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="<?= $editing?'update':'create' ?>">
<?php if ($editing): ?><input type="hidden" name="id_cuenta" value="<?= e($record['id_cuenta']??'') ?>"><?php endif ?>
<label>Usuario<input name="usuario" maxlength="80" autocomplete="off" required value="<?= e($record['usuario']??'') ?>"></label>
<label>Correo electrónico<input type="email" name="correo" maxlength="254" required value="<?= e($record['correo']??'') ?>"></label>
<label>Tipo de cuenta<select name="rol" data-account-role required><?php foreach (['cliente'=>'Cliente','administrador'=>'Administrador'] as $value=>$caption): ?><option value="<?= e($value) ?>" <?= ($record['rol']??'cliente')===$value?'selected':'' ?>><?= e($caption) ?></option><?php endforeach ?></select></label>
<label>Estado<select name="estado" required><?php foreach (['activo','inactivo','bloqueado'] as $state): ?><option value="<?= e($state) ?>" <?= ($record['estado']??'activo')===$state?'selected':'' ?>><?= e(ucfirst($state)) ?></option><?php endforeach ?></select></label>
<label><?= $editing?'Nueva contraseña (opcional)':'Contraseña' ?><input type="password" name="clave" autocomplete="new-password" minlength="12" maxlength="72" <?= !$editing?'required':'' ?>></label>
<label>Confirmar contraseña<input type="password" name="confirmar_clave" autocomplete="new-password" minlength="12" maxlength="72" <?= !$editing?'required':'' ?>></label>
<p><?= $editing?'Deja las contraseñas vacías para conservar la actual. ':'' ?>Usa al menos 12 caracteres.</p>
<fieldset data-client-account-fields><legend>Datos del cliente</legend>
<label>Nombre<input name="nombre" maxlength="100" data-client-required value="<?= e($record['nombre']??'') ?>"></label>
<label>Apellidos<input name="apellidos" maxlength="150" data-client-required value="<?= e($record['apellidos']??'') ?>"></label>
<label>Teléfono (opcional)<input type="tel" name="telefono" maxlength="25" value="<?= e($record['telefono']??'') ?>"></label>
</fieldset>
<div class="actions"><button type="submit">Guardar cuenta</button><a class="button secondary" href="administrador.php?module=cuentas">Cancelar</a></div>
</form></section>
<?php else: ?>
<section class="card">
<form method="get" action="administrador.php" class="actions">
<input type="hidden" name="module" value="cuentas">
<label>Buscar usuario o correo<input type="search" name="q" value="<?= e($search) ?>"></label>
<label>Rol<select name="rol"><option value="">Todos</option value="administrador" <?= $accountRoleFilter==='administrador'?'selected':'' ?>>Administrador</option><option value="cliente" <?= $accountRoleFilter==='cliente'?'selected':'' ?>>Cliente</option></select></label>
<label>Estado<select name="estado"><option value="">Todos</option><?php foreach (['activo','inactivo','bloqueado'] as $state): ?><option value="<?= e($state) ?>" <?= $accountStateFilter===$state?'selected':'' ?>><?= e(ucfirst($state)) ?></option><?php endforeach ?></select></label>
<button>Buscar</button><a href="administrador.php?module=cuentas">Limpiar filtros</a>
</form>
<p><?= e($list['total']) ?> cuentas</p>
<div class="table-scroll"><table><thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($list['rows'] as $row): ?><tr><td><?= e($row['usuario']) ?></td><td><?= e($row['correo']) ?></td><td><?= e(ucfirst($row['rol'])) ?></td><td><?= e(ucfirst($row['estado'])) ?></td><td><a class="button secondary" href="administrador.php?module=cuentas&amp;mode=edit&amp;id_cuenta=<?= e($row['id_cuenta']) ?>">Editar</a></td></tr><?php endforeach ?>
<?php if (!$list['rows']): ?><tr><td colspan="5">No hay cuentas con estos filtros.</td></tr><?php endif ?>
</tbody></table></div>
<nav class="actions" aria-label="Páginas de cuentas"><?php foreach ([$list['page']-1=>'Anterior',$list['page']+1=>'Siguiente'] as $page=>$caption): if ($page<1 || $page>max(1,(int)ceil($list['total']/20))) continue; ?><a class="button secondary" href="administrador.php?<?= e(http_build_query(['module'=>'cuentas','q'=>$search,'rol'=>$accountRoleFilter,'estado'=>$accountStateFilter,'page'=>$page])) ?>"><?= e($caption) ?></a><?php endforeach ?></nav>
</section>
<?php endif ?>
