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
                <dl><?php foreach ($columns as $name => $c):
                        if (str_starts_with($name,'id_') && !isset($refs[$name])) continue; ?>
                        <dt><?= e(label($name)) ?></dt>
                        <dd><?= e(displayValue($name,$c,$refs,$record[$name]??null)) ?></dd><?php endforeach ?>
                </dl><a href="index.php?module=<?= e($moduleId) ?>">Volver</a>
            </section><?php endif ?>
        <?php if (in_array($mode, ['create', 'edit'], true) && empty($module['readonly']) && ($mode === 'create' || $record)): ?>
            <section class="card">
                <h2><?= $mode === 'create' ? 'Crear' : 'Editar' ?> registro</h2>
                <p>Los campos con * son obligatorios. Fechas y horas en UTC.</p>
                <form action="index.php?module=<?= e($moduleId) ?>" method="post" enctype="multipart/form-data" class="record-form"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input
                        type="hidden" name="action" value="<?= $mode === 'create' ? 'create' : 'update' ?>">
                    <?php if ($mode === 'edit'):
                        foreach ($module['pk'] as $pk): ?><input type="hidden"
                                name="key[<?= e($pk) ?>]"
                                value="<?= e($_POST['key'][$pk] ?? $record[$pk] ?? '') ?>"><?php endforeach; endif ?>
                    <div class="form-grid">
                        <?php if ($moduleId === 'productos'): ?>
                            <label>Imagen del producto
                                <input type="file" name="imagen_archivo" accept="image/jpeg,image/png,image/webp">
                                <small>Opcional. Selecciona una imagen JPG, PNG o WebP. Se guardará como portada del producto.</small>
                            </label>
                        <?php endif ?>
                        <?php foreach ($columns as $name => $c):
                            if (!$repo->editable($c))
                                continue;
                            $value = $record[$name] ?? ($mode === 'create' ? ($c['Default'] ?? '') : '');
                            $required = $c['Null'] === 'NO';
                            $options = choices($c, $refs); ?>
                            <label><?= $moduleId === 'imagenes' && $name === 'imagen_url' ? 'Imagen del artículo' : e(label($name)) ?><?= $required && !($moduleId === 'imagenes' && $name === 'imagen_url' && $mode === 'edit') ? ' *' : '' ?>
                                <?php if ($moduleId === 'imagenes' && $name === 'imagen_url'): ?>
                                    <input type="file" name="imagen_archivo" accept="image/jpeg,image/png,image/webp" <?= $mode === 'create' ? 'required' : '' ?>>
                                    <small>Selecciona un archivo JPG, PNG o WebP (máximo 5 MB, sujeto al límite del servidor).<?= $mode === 'edit' ? ' Si no seleccionas otro archivo, se conserva la imagen actual.' : '' ?></small>
                                <?php elseif ($options !== null): ?><select name="<?= e($name) ?>" <?= $required ? 'required' : '' ?>>
                                        <option value="">Selecciona…</option><?php foreach ($options as $v => $caption): ?>
                                            <option value="<?= e($v) ?>" <?= (string) $value === (string) $v ? 'selected' : '' ?>>
                                                <?= e($caption) ?></option>
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
                            href="index.php?module=<?= e($moduleId) ?>">Cancelar</a></div>
                </form>
            </section><?php endif ?>
        <section class="card">
            <form action="index.php" method="get" class="filters"><input type="hidden" name="module"
                    value="<?= e($moduleId) ?>"><label>Buscar<input name="q" placeholder="Nombre, descripción o código"
                        value="<?= e($search) ?>"></label>
                <?php foreach ($filterable as $name => $c): ?><label><?= e(label($name)) ?><select
                            name="filter[<?= e($name) ?>]">
                            <option value="">Todos</option><?php foreach (choices($c, $refs) ?? [] as $v => $caption): ?>
                                <option value="<?= e($v) ?>" <?= (string) ($filters[$name] ?? '') === (string) $v ? 'selected' : '' ?>>
                                    <?= e($caption) ?></option><?php endforeach ?>
                        </select></label><?php endforeach ?><button>Filtrar</button><a
                    href="index.php?module=<?= e($moduleId) ?>">Limpiar</a></form>
            <p><?= e($list['total']) ?> registros · Página <?= e($list['page']) ?></p>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr><?php $visible = array_slice(array_filter($columns,fn($c)=>!str_starts_with($c['Field'],'id_') || isset($refs[$c['Field']])), 0, 7, true);
                        if ($esTienda && $moduleId === 'productos') {
                            $visible = array_intersect_key(array_replace(array_flip(['nombre','id_categoria_producto','precio','stock_actual','activo']),$columns),array_flip(['nombre','id_categoria_producto','precio','stock_actual','activo']));
                        }
                        foreach ($visible as $name => $c): ?>
                                <th scope="col"><?= e(label($name)) ?></th><?php endforeach ?>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list['rows'] as $row):
                            $query = http_build_query(array_merge(['module' => $moduleId], array_intersect_key($row, array_flip($module['pk'])))); ?>
                            <tr><?php foreach ($visible as $name => $c): ?>
                                    <td>
                                    <?php if ($esTienda && $moduleId === 'productos' && $name === 'nombre'): ?>
                                        <div class="producto-visual">
                                            <?php if (isset($portadas[$row['id_producto']])): ?><img src="<?= e($portadas[$row['id_producto']]) ?>" alt="" loading="lazy">
                                            <?php else: ?><span class="producto-sin-imagen" aria-hidden="true">▣</span><?php endif ?>
                                            <strong><?= e($row[$name]) ?></strong>
                                        </div>
                                    <?php elseif ($esTienda && $moduleId === 'productos' && $name === 'activo'):
                                        $estadoProducto = !$row['activo'] ? 'inactivo' : ((int)$row['stock_actual'] === 0 ? 'agotado' : ((int)$row['stock_actual'] <= (int)$row['stock_minimo'] ? 'bajo' : 'activo')); ?>
                                        <span class="estado-tienda estado-<?= e($estadoProducto) ?>"><?= e(['inactivo'=>'Inactivo','agotado'=>'Agotado','bajo'=>'Stock bajo','activo'=>'Activo'][$estadoProducto]) ?></span>
                                    <?php elseif ($esTienda && $name === 'estado'): ?>
                                        <span class="estado-tienda estado-<?= e($row[$name]) ?>"><?= e(label($row[$name])) ?></span>
                                    <?php else: ?><?= e(displayValue($name,$c,$refs,$row[$name]??null)) ?><?php endif ?>
                                    </td><?php endforeach ?>
                                <td>
                                    <div class="actions"><a
                                            href="index.php?<?= e($query) ?>&amp;mode=view"><?= $esTienda ? tiendaIcono('ver') : '' ?>Ver</a><?php if (empty($module['readonly'])): ?><a
                                                href="index.php?<?= e($query) ?>&amp;mode=edit"><?= $esTienda ? tiendaIcono('editar') : '' ?>Editar</a>
                                            <form action="index.php?module=<?= e($moduleId) ?>" method="post"
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
