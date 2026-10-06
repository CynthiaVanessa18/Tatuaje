<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($module['title']) ?> · Estudio Tattoo</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <script src="../assets/js/app.js" defer></script>
</head>

<body>
    <aside><a class="brand" href="administrador.php">ESTUDIO<br><strong>TATTOO</strong></a>
        <p class="eyebrow">GESTIÓN · PERSONA 2</p>
        <nav aria-label="Módulos">
            <p class="nav-group">Artistas</p>
            <a href="artistas.php">Perfiles de artistas</a>
            <a href="galeria.php">Galería fotográfica</a>
            <a href="cotizaciones.php">Cotizaciones</a>
            <?php $group = '';
            foreach ($modules as $id => $item):
                if ($group !== $item['group']):
                    $group = $item['group']; ?>
                    <p class="nav-group"><?= e($group) ?></p><?php endif ?>
                <a <?= $id === $moduleId ? 'aria-current="page"' : '' ?>
                    href="administrador.php?module=<?= e($id) ?>"><?= e($item['title']) ?></a><?php endforeach ?>
        </nav>
        <form method="post" action="../auth/logout.php"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button
                class="secondary">Cerrar sesión</button></form>
    </aside>
    <main>
        <header>
            <div>
                <p class="eyebrow">PANEL ADMINISTRATIVO</p>
                <h1><?= e($module['title']) ?></h1>
                <p>Consulta y organiza los registros de tu estudio.</p>
            </div><?php if (empty($module['readonly'])): ?><a class="button"
                    href="administrador.php?module=<?= e($moduleId) ?>&amp;mode=create">+ Crear registro</a><?php endif ?>
        </header>
        <?php if ($notice): ?>
            <p class="notice" role="status"><?= e($notice) ?></p><?php endif ?>
        <?php if ($error): ?>
            <p class="notice error" role="alert"><?= e($error) ?></p><?php endif ?>
        <?php if ($moduleId === 'ventas'): ?>
            <p class="hint">Crea una venta pendiente, agrega sus detalles y confírmala para descontar inventario. Los
                totales se calculan automáticamente. Los cobros y reembolsos se gestionan fuera de este módulo.</p>
        <?php endif ?>
        <?php if ($moduleId === 'detalle'): ?>
            <p class="hint">Los precios de productos y planes se toman del catálogo al guardar. Si seleccionas una
                promoción, el descuento se calcula sobre esta línea.</p><?php endif ?>
        <?php if (in_array($moduleId, ['tarjetas', 'membresias'], true)): ?>
            <p class="hint">Selecciona un detalle de venta confirmado del tipo correspondiente. Las fechas se ingresan en
                UTC. La activación es administrativa; verifica el cobro antes de activarlo.</p><?php endif ?>
        <?php if ($mode === 'view' && $record): ?>
            <section class="card">
                <h2>Detalle del registro</h2>
                <dl><?php foreach ($columns as $name => $c): ?>
                        <dt><?= e(label($name)) ?></dt>
                        <dd><?= e(choices($c, $refs)[$record[$name] ?? ''] ?? $record[$name] ?? '—') ?></dd><?php endforeach ?>
                </dl><a href="administrador.php?module=<?= e($moduleId) ?>">Volver</a>
            </section><?php endif ?>
        <?php if (in_array($mode, ['create', 'edit'], true) && empty($module['readonly']) && ($mode === 'create' || $record)): ?>
            <section class="card">
                <h2><?= $mode === 'create' ? 'Crear' : 'Editar' ?> registro</h2>
                <p>Los campos con * son obligatorios. Fechas y horas en UTC.</p>
                <form method="post" class="record-form"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input
                        type="hidden" name="action" value="<?= $mode === 'create' ? 'create' : 'update' ?>">
                    <?php if ($mode === 'edit'):
                        foreach ($module['pk'] as $pk): ?><input type="hidden"
                                name="key[<?= e($pk) ?>]"
                                value="<?= e($_POST['key'][$pk] ?? $record[$pk] ?? '') ?>"><?php endforeach; endif ?>
                    <div class="form-grid">
                        <?php foreach ($columns as $name => $c):
                            if (!$repo->editable($c))
                                continue;
                            $value = $record[$name] ?? ($mode === 'create' ? ($c['Default'] ?? '') : '');
                            $required = $c['Null'] === 'NO';
                            $options = choices($c, $refs); ?>
                            <label><?= e(label($name)) ?><?= $required ? ' *' : '' ?>
                                <?php if ($options !== null): ?><select name="<?= e($name) ?>" <?= $required ? 'required' : '' ?>>
                                        <option value="">Selecciona…</option><?php foreach ($options as $v => $caption): ?>
                                            <option value="<?= e($v) ?>" <?= (string) $value === (string) $v ? 'selected' : '' ?>>
                                                <?= e($caption) ?>                <?= isset($refs[$name]) ? ' (#' . e($v) . ')' : '' ?></option>
                                        <?php endforeach ?>
                                    </select>
                                <?php elseif (str_contains($c['Type'], 'text')): ?><textarea name="<?= e($name) ?>" rows="3"
                                        <?= $required ? 'required' : '' ?>><?= e($value) ?></textarea>
                                <?php else:
                                    $type = str_starts_with($c['Type'], 'datetime') ? 'datetime-local' : ($c['Type'] === 'date' ? 'date' : (preg_match('/int|decimal/', $c['Type']) ? 'number' : (str_contains($name, 'correo') ? 'email' : (str_ends_with($name, '_url') ? 'url' : 'text')))); ?>
                                    <input name="<?= e($name) ?>" type="<?= e($type) ?>"
                                        value="<?= e($type === 'datetime-local' ? str_replace(' ', 'T', (string) $value) : $value) ?>"
                                        <?= $required ? 'required' : '' ?>             <?= $type === 'number' ? 'min="0" step="' . (str_starts_with($c['Type'], 'decimal') ? '0.01' : '1') . '"' : '' ?>             <?php if (preg_match('/^(?:var)?char\((\d+)\)/', $c['Type'], $max)): ?>maxlength="<?= e($max[1]) ?>"
                                        <?php endif ?>><?php endif ?></label>
                        <?php endforeach ?>
                    </div>
                    <div class="actions"><button>Guardar registro</button><a
                            href="administrador.php?module=<?= e($moduleId) ?>">Cancelar</a></div>
                </form>
            </section><?php endif ?>
        <section class="card">
            <form method="get" class="filters"><input type="hidden" name="module"
                    value="<?= e($moduleId) ?>"><label>Buscar<input name="q" placeholder="Nombre, descripción o código"
                        value="<?= e($search) ?>"></label>
                <?php foreach ($filterable as $name => $c): ?><label><?= e(label($name)) ?><select
                            name="filter[<?= e($name) ?>]">
                            <option value="">Todos</option><?php foreach (choices($c, $refs) ?? [] as $v => $caption): ?>
                                <option value="<?= e($v) ?>" <?= (string) ($filters[$name] ?? '') === (string) $v ? 'selected' : '' ?>>
                                    <?= e($caption) ?></option><?php endforeach ?>
                        </select></label><?php endforeach ?><button>Filtrar</button><a
                    href="administrador.php?module=<?= e($moduleId) ?>">Limpiar</a></form>
            <p><?= e($list['total']) ?> registros · Página <?= e($list['page']) ?></p>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr><?php $visible = array_slice($columns, 0, 7, true);
                        foreach ($visible as $name => $c): ?>
                                <th scope="col"><?= e(label($name)) ?></th><?php endforeach ?>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list['rows'] as $row):
                            $query = http_build_query(array_merge(['module' => $moduleId], array_intersect_key($row, array_flip($module['pk'])))); ?>
                            <tr><?php foreach ($visible as $name => $c): ?>
                                    <td><?= e(choices($c, $refs)[$row[$name] ?? ''] ?? $row[$name] ?? '—') ?></td><?php endforeach ?>
                                <td>
                                    <div class="actions"><a
                                            href="administrador.php?<?= e($query) ?>&amp;mode=view">Ver</a><?php if (empty($module['readonly'])): ?><a
                                                href="administrador.php?<?= e($query) ?>&amp;mode=edit">Editar</a>
                                            <form method="post"
                                                data-confirm="¿Eliminar este registro? Si tiene relaciones, la operación será rechazada.">
                                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden"
                                                    name="action" value="delete"><?php foreach ($module['pk'] as $pk): ?><input
                                                        type="hidden" name="key[<?= e($pk) ?>]"
                                                        value="<?= e($row[$pk]) ?>"><?php endforeach ?><button
                                                    class="danger">Eliminar</button></form><?php endif ?>
                                    </div>
                                </td>
                            </tr><?php endforeach ?>
                        <?php if (!$list['rows']): ?>
                            <tr>
                                <td colspan="<?= count($visible) + 1 ?>" class="empty">No hay registros para mostrar. Crea el
                                    primero o ajusta los filtros.</td>
                            </tr><?php endif ?>
                    </tbody>
                </table>
            </div>
            <div class="actions pagination"><?php if ($list['page'] > 1): ?><a
                        href="?<?= e(http_build_query(['module' => $moduleId, 'q' => $search, 'filter' => $filters, 'page' => $list['page'] - 1])) ?>">←
                        Anterior</a><?php endif ?><?php if ($list['page'] * 20 < $list['total']): ?><a
                        href="?<?= e(http_build_query(['module' => $moduleId, 'q' => $search, 'filter' => $filters, 'page' => $list['page'] + 1])) ?>">Siguiente
                        →</a><?php endif ?></div>
        </section>
    </main>
</body>

</html>
