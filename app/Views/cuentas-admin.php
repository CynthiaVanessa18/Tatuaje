<?php if ($mode==='view' && $record): ?>
<section class="card"><h2><?= adminIcon('cuentas') ?> Detalle de la cuenta</h2><dl>
<?php foreach (['usuario'=>'Usuario','correo'=>'Correo electrónico','rol'=>'Rol','estado'=>'Estado','nombre'=>'Nombre','apellidos'=>'Apellidos','telefono'=>'Teléfono'] as $field=>$caption): ?>
<dt><?= e($caption) ?></dt><dd><?= e($record[$field]??'—') ?></dd>
<?php endforeach ?></dl><div class="actions"><a href="administrador.php?module=cuentas">Volver</a><a href="administrador.php?module=cuentas&amp;mode=edit&amp;id_cuenta=<?= e($record['id_cuenta']) ?>"><?= adminIcon('editar') ?> Editar</a></div></section>
<?php return; endif ?>
<section class="accounts-dashboard" aria-label="Resumen de cuentas">
<?php foreach ([['total','Todas las cuentas','cuentas',[]],['activos','Cuentas activas','activo',['estado'=>'activo']],['bloqueados','Cuentas bloqueadas','bloqueado',['estado'=>'bloqueado']],['clientes','Clientes','cuentas',['rol'=>'cliente']]] as [$key,$caption,$icon,$query]): ?>
<a class="dashboard-stat" href="administrador.php?<?= e(http_build_query(['module'=>'cuentas']+$query)) ?>"><span class="dashboard-stat-icon"><?= adminIcon($icon) ?></span><div><small><?= e($caption) ?></small><strong><?= e($accountSummary[$key]) ?></strong><span>Consultar cuentas →</span></div></a>
<?php endforeach ?>
</section>
<div class="dashboard-shortcuts"><a href="administrador.php?module=cuentas&amp;mode=create"><?= adminIcon('crear') ?> Crear cuenta</a><a href="administrador.php?module=cuentas&amp;rol=administrador"><?= adminIcon('cuentas') ?> Administradores <b><?= e($accountSummary['administradores']) ?></b></a><a href="administrador.php?module=cuentas&amp;estado=inactivo"><?= adminIcon('bloqueado') ?> Inactivas <b><?= e($accountSummary['inactivos']) ?></b></a></div>
<?php if (in_array($mode,['create','edit'],true)): $editing=$mode==='edit'; ?>
<section class="card account-editor"><div class="dashboard-section-heading"><span class="dashboard-stat-icon"><?= adminIcon($editing?'editar':'crear') ?></span><div><p class="eyebrow">IDENTIDAD Y ACCESO</p><h2><?= $editing?'Editar cuenta':'Crear cuenta' ?></h2><p>Completa los datos de acceso y el perfil de la persona.</p></div></div>
<form method="post" action="administrador.php?module=cuentas" class="form-grid">
<input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="<?= $editing?'update':'create' ?>">
<?php if ($editing): ?><input type="hidden" name="id_cuenta" value="<?= e($record['id_cuenta']??'') ?>"><?php endif ?>
<label>Usuario<input name="usuario" maxlength="80" autocomplete="off" required value="<?= e($record['usuario']??'') ?>"></label>
<label>Correo electrónico<input type="email" name="correo" maxlength="254" required value="<?= e($record['correo']??'') ?>"></label>
<label>Tipo de cuenta<select name="rol" data-account-role required><?php foreach (['cliente'=>'Cliente','administrador'=>'Administrador'] as $value=>$caption): ?><option value="<?= e($value) ?>" <?= ($record['rol']??'cliente')===$value?'selected':'' ?>><?= e($caption) ?></option><?php endforeach ?></select></label>
<label>Estado<select name="estado" required><?php foreach (['activo','inactivo','bloqueado'] as $state): ?><option value="<?= e($state) ?>" <?= ($record['estado']??'activo')===$state?'selected':'' ?>><?= e(ucfirst($state)) ?></option><?php endforeach ?></select></label>
<label><?= $editing?'Nueva contraseña (opcional)':'Contraseña' ?><input type="password" name="clave" autocomplete="new-password" minlength="12" maxlength="72" <?= !$editing?'required':'' ?>></label>
<label>Confirmar contraseña<input type="password" name="confirmar_clave" autocomplete="new-password" minlength="12" maxlength="72" <?= !$editing?'required':'' ?>></label>
<p class="form-help"><?= $editing?'Deja las contraseñas vacías para conservar la actual. ':'' ?>Usa al menos 12 caracteres.</p>
<fieldset data-client-account-fields><legend>Datos del cliente</legend>
<label>Nombre<input name="nombre" maxlength="100" data-client-required value="<?= e($record['nombre']??'') ?>"></label>
<label>Apellidos<input name="apellidos" maxlength="150" data-client-required value="<?= e($record['apellidos']??'') ?>"></label>
<label>Teléfono (opcional)<input type="tel" name="telefono" maxlength="25" value="<?= e($record['telefono']??'') ?>"></label>
</fieldset>
<div class="actions form-footer"><button type="submit"><?= adminIcon('activo') ?> Guardar cuenta</button><a class="button secondary" href="administrador.php?module=cuentas">Cancelar</a></div>
</form></section>
<?php else: ?>
<section class="card">
<div class="dashboard-section-heading"><span class="dashboard-stat-icon"><?= adminIcon('cuentas') ?></span><div><p class="eyebrow">DIRECTORIO DEL ESTUDIO</p><h2>Gestionar cuentas</h2><p>Busca por usuario o correo y filtra por rol o estado.</p></div></div>
<form method="get" action="administrador.php" class="filters accounts-filters">
<input type="hidden" name="module" value="cuentas">
<label>Buscar usuario o correo<input type="search" name="q" value="<?= e($search) ?>"></label>
<label>Rol<select name="rol"><option value="">Todos</option value="administrador" <?= $accountRoleFilter==='administrador'?'selected':'' ?>>Administrador</option><option value="cliente" <?= $accountRoleFilter==='cliente'?'selected':'' ?>>Cliente</option></select></label>
<label>Estado<select name="estado"><option value="">Todos</option><?php foreach (['activo','inactivo','bloqueado'] as $state): ?><option value="<?= e($state) ?>" <?= $accountStateFilter===$state?'selected':'' ?>><?= e(ucfirst($state)) ?></option><?php endforeach ?></select></label>
<button><?= adminIcon('buscar') ?> Buscar</button><a href="administrador.php?module=cuentas">Limpiar filtros</a>
</form>
<p><?= e($list['total']) ?> cuentas</p>
<div class="table-scroll"><table data-server-paginated><thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($list['rows'] as $row): ?><tr><td class="list-value"><div class="account-identity"><span class="account-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($row['usuario'],0,1))) ?></span><strong><?= e($row['usuario']) ?></strong></div></td><td><span class="account-role"><?= e(ucfirst($row['rol'])) ?></span></td><td><span class="account-status status-<?= e($row['estado']) ?>"><i aria-hidden="true"></i><?= e(ucfirst($row['estado'])) ?></span></td><td><div class="actions"><a href="administrador.php?module=cuentas&amp;mode=view&amp;id_cuenta=<?= e($row['id_cuenta']) ?>">Ver</a><a href="administrador.php?module=cuentas&amp;mode=edit&amp;id_cuenta=<?= e($row['id_cuenta']) ?>"><?= adminIcon('editar') ?> Editar</a></div></td></tr><?php endforeach ?>
<?php if (!$list['rows']): ?><tr><td colspan="4">No hay cuentas con estos filtros.</td></tr><?php endif ?>
</tbody></table></div>
<?php renderPagination((int)$list['total'], (int)$list['page'], array_replace($_GET, ['module'=>$moduleId])); ?>
</section>
<?php endif ?>
