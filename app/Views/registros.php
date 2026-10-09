        <?php if ($moduleId === 'ventas'): ?>
            <p class="hint">Crea una venta pendiente, agrega sus detalles y confírmala para descontar inventario. Los
                totales se calculan automáticamente. Los cobros y reembolsos se gestionan fuera de este módulo.</p>
        <?php endif ?>
        <?php if ($moduleId === 'detalle'): ?>
            <p class="hint">Los precios de productos y planes se toman del catálogo al guardar. Si seleccionas una
                promoción, el descuento se calcula sobre esta línea.</p><?php endif ?>
        <?php if ($moduleId==='promociones'): ?><p class="hint">Para crear ofertas automáticas por artículos, categorías o grupos de clientes, entra en <a href="administrador.php?module=promociones_tienda">Tienda → Promociones y ofertas</a>. Este apartado conserva las promociones generales anteriores.</p><?php endif ?>
        <?php if ($moduleId==='tarjetas'): ?><p class="hint">Crea la tarjeta indicando el cliente comprador, destinatario, monto y vencimiento opcional. No necesita un artículo ni una venta asociada. Verifica el cobro o la autorización del regalo antes de activarla.</p><?php endif ?>
        <?php if ($moduleId==='membresias'): ?>
            <p class="hint">Selecciona un detalle de venta confirmado del tipo correspondiente. Las fechas se ingresan en
                UTC. La activación es administrativa; verifica el cobro antes de activarlo.</p><?php endif ?>
        <?php if ($mode === 'view' && $record): ?>
            <section class="card">
                <h2><?= adminIcon($moduleId) ?> Detalle del registro</h2>
                <dl><?php foreach ($columns as $name => $c):
                        if (str_starts_with($name,'id_') && !isset($refs[$name])) continue; ?>
                        <dt><?= e(label($name)) ?></dt>
                        <dd><?= e(displayValue($name,$c,$refs,$record[$name]??null)) ?></dd><?php endforeach ?>
                </dl><div class="actions"><a href="administrador.php?module=<?= e($moduleId) ?>">Volver</a><?php if (empty($module['readonly'])): ?><a href="administrador.php?<?= e(http_build_query(['module'=>$moduleId,'mode'=>'edit']+array_intersect_key($record,array_flip($module['pk'])))) ?>">Editar</a><?php endif ?></div>
            </section><?php endif ?>
        <?php if ($mode==='view' && $record) return; ?>
        <?php if (in_array($mode, ['create', 'edit'], true) && empty($module['readonly']) && ($mode === 'create' || $record)): ?>
            <section class="card">
                <p class="eyebrow"><?= $mode === 'create' ? 'NUEVO REGISTRO' : 'ACTUALIZAR INFORMACIÓN' ?></p>
                <h2><?= adminIcon($mode==='create'?'crear':'editar') ?> <?= $mode === 'create' ? 'Crear' : 'Editar' ?> · <?= e($module['title']) ?></h2>
                <p>Los campos con * son obligatorios. Fechas y horas en UTC.</p>
                <form action="administrador.php?module=<?= e($moduleId) ?>" method="post" enctype="multipart/form-data" class="record-form"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input
                        type="hidden" name="action" value="<?= $mode === 'create' ? 'create' : 'update' ?>">
                    <?php if ($mode === 'edit'):
                        foreach ($module['pk'] as $pk): ?><input type="hidden"
                                name="key[<?= e($pk) ?>]"
                                value="<?= e($_POST['key'][$pk] ?? $record[$pk] ?? '') ?>"><?php endforeach; endif ?>
                    <div class="form-grid">
                        <?php if ($moduleId==='tarjetas' && $mode==='create'): ?>
                        <label>Notificación al destinatario<select name="enviar_correo"><option value="1" selected>Enviar aviso y código por correo</option><option value="0">Entregar el código personalmente</option></select><small>El correo solo avisa que recibió una tarjeta y muestra su código.</small></label>
                        <?php endif ?>
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
                            if ($moduleId==='tarjetas' && $mode==='create' && $name==='estado' && !isset($record[$name])) $value='activa';
                            $required = $c['Null'] === 'NO';
                            $options = choices($c, $refs); ?>
                            <label><?= $moduleId === 'imagenes' && $name === 'imagen_url' ? 'Imagen del artículo' : e(label($name)) ?><?= $required && !($moduleId === 'imagenes' && $name === 'imagen_url' && $mode === 'edit') ? ' *' : '' ?>
                                <?php if ($moduleId === 'imagenes' && $name === 'imagen_url'): ?>
                                    <input type="file" name="imagen_archivo" accept="image/jpeg,image/png,image/webp" <?= $mode === 'create' ? 'required' : '' ?>>
                                    <small>Selecciona un archivo JPG, PNG o WebP (máximo 5 MB, sujeto al límite del servidor).<?= $mode === 'edit' ? ' Si no seleccionas otro archivo, se conserva la imagen actual.' : '' ?></small>
                                <?php elseif ($moduleId==='tarjetas' && $name==='correo_destinatario'): ?>
                                    <input type="email" name="correo_destinatario" value="<?= e($value) ?>" list="gift-recipient-emails" maxlength="254" required>
                                    <datalist id="gift-recipient-emails">
                                    <?php foreach ($repo->db->query("SELECT c.correo,CONCAT_WS(' ',cl.nombre,cl.apellidos) AS nombre FROM clientes cl JOIN cuentas c ON c.id_cuenta=cl.id_cuenta WHERE c.estado='activo' ORDER BY cl.nombre,cl.apellidos")->fetchAll() as $recipient): ?>
                                    <option value="<?= e($recipient['correo']) ?>"><?= e($recipient['nombre']) ?></option>
                                    <?php endforeach ?>
                                    </datalist>
                                    <small>Selecciona el correo de la cuenta del destinatario para añadir el regalo automáticamente. El comprador no determina quién recibe la tarjeta.</small>
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
                    <div class="actions"><button><?= adminIcon('activo') ?> Guardar registro</button><a
                            href="administrador.php?module=<?= e($moduleId) ?>">Cancelar</a></div>
                </form>
            </section><?php endif ?>
        <section class="card">
            <form action="administrador.php" method="get" class="filters"><input type="hidden" name="module"
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
                <table data-server-paginated>
                    <thead>
                        <tr><?php $listColumns=require __DIR__.'/../../config/list-columns.php';
                        $visible=[];
                        foreach ($listColumns[$moduleId]??[] as $field) if (isset($columns[$field])) $visible[$field]=$columns[$field];
                        foreach ($visible as $name => $c): ?>
                                <th scope="col"><?= e(label($name)) ?></th><?php endforeach ?>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list['rows'] as $row):
                            $query = http_build_query(array_merge(['module' => $moduleId], array_intersect_key($row, array_flip($module['pk'])))); ?>
                            <tr><?php foreach ($visible as $name => $c): ?>
                                    <td class="list-value">
                                    <?php if ($esTienda && $moduleId === 'productos' && $name === 'nombre'): ?>
                                        <div class="producto-visual">
                                            <?php if (isset($portadas[$row['id_producto']])): ?><img src="<?= e(imageUrl($portadas[$row['id_producto']])) ?>" alt="" loading="lazy">
                                            <?php else: ?><span class="producto-sin-imagen" aria-hidden="true">▣</span><?php endif ?>
                                            <strong><?= e($row[$name]) ?></strong>
                                        </div>
                                    <?php elseif ($esTienda && $moduleId === 'productos' && $name === 'activo'):
                                        $estadoProducto = !$row['activo'] ? 'inactivo' : ((int)$row['stock_actual'] === 0 ? 'agotado' : ((int)$row['stock_actual'] <= (int)$row['stock_minimo'] ? 'bajo' : 'activo')); ?>
                                        <span class="estado-tienda estado-<?= e($estadoProducto) ?>"><?= e(['inactivo'=>'Inactivo','agotado'=>'Agotado','bajo'=>'Stock bajo','activo'=>'Activo'][$estadoProducto]) ?></span>
                                    <?php elseif (in_array($name,['id_venta','id_tarjeta'],true)): ?>
                                        <?= e(($name==='id_venta'?'Venta #':'Tarjeta #').($row[$name]??'—')) ?>
                                    <?php elseif (in_array($name,['monto_inicial','monto','total'],true) && isset($row['moneda'])): ?>
                                        <?= e($row['moneda']) ?> <?= e(displayValue($name,$c,$refs,$row[$name]??null)) ?>
                                    <?php elseif ($esTienda && $name === 'estado'): ?>
                                        <span class="estado-tienda estado-<?= e($row[$name]) ?>"><?= e(label($row[$name])) ?></span>
                                    <?php else: ?><?= e(displayValue($name,$c,$refs,$row[$name]??null)) ?><?php endif ?>
                                    </td><?php endforeach ?>
                                <td>
                                    <div class="actions"><a
                                            href="administrador.php?<?= e($query) ?>&amp;mode=view"><?= $esTienda ? tiendaIcono('ver') : '' ?>Ver</a><?php if (empty($module['readonly'])): ?><a
                                                href="administrador.php?<?= e($query) ?>&amp;mode=edit"><?= $esTienda ? tiendaIcono('editar') : '' ?>Editar</a>
                                            <form action="administrador.php?module=<?= e($moduleId) ?>" method="post"
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
            <?php renderPagination((int)$list['total'], (int)$list['page'], array_replace($_GET, ['module'=>$moduleId])); ?>
</section>
