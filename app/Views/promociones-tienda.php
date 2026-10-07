<?php
$scopeLabels=['tienda'=>'Toda la tienda','productos'=>'Artículos seleccionados','categorias'=>'Categorías seleccionadas'];
$form=$promotionForm;
?>
<p class="hint">Las promociones se aplican automáticamente. Si coinciden varias, se usa la que dé mayor ahorro sin acumularlas. El monto fijo se descuenta una vez por compra sobre los artículos elegibles. La compra mínima se calcula sobre el subtotal completo antes de descuentos.</p>
<?php if ($mode==='view' && !$error): ?>
<section class="card"><h2>Detalle de la promoción</h2><dl>
<dt>Nombre</dt><dd><?= e($form['titulo']) ?></dd>
<dt>Descripción</dt><dd><?= nl2br(e($form['descripcion']?:'Sin descripción')) ?></dd>
<dt>Estado</dt><dd><?= $form['activo']?'Activa':'Inactiva' ?></dd>
<dt>Descuento</dt><dd><?= e(StorePromotions::benefit($form)) ?></dd>
<dt>Alcance</dt><dd><?= e($scopeLabels[$form['alcance']]) ?></dd>
<dt>Clientes</dt><dd><?php if ($form['publico']==='todos'): ?>Todos los clientes<?php else: foreach ($promotionGroups as $group) if ((string)$group['id_grupo']===(string)$form['id_grupo']) echo e($group['nombre']); endif ?></dd>
<dt>Compra mínima</dt><dd><?= $form['regla_minimo']==='siempre'?'Sin compra mínima':'₡'.e(number_format((float)$form['minimo_compra'],2,',','.')) ?></dd>
<dt>Inicio · Costa Rica</dt><dd><?= e(str_replace('T',' ',$form['fecha_inicio'])) ?></dd>
<dt>Vencimiento · Costa Rica</dt><dd><?= e(str_replace('T',' ',$form['fecha_fin'])) ?></dd>
<?php foreach (['productos'=>[$promotionProducts,'id_producto','Artículos'],'categorias'=>[$promotionCategories,'id_categoria_producto','Categorías']] as $target=>[$items,$key,$caption]): if ($form['alcance']!==$target) continue; ?>
<dt><?= e($caption) ?></dt><dd><ul><?php foreach ($items as $item): if (!in_array((string)$item[$key],array_map('strval',$form[$target]),true)) continue; ?><li><?= e($item['nombre']) ?></li><?php endforeach ?></ul></dd>
<?php endforeach ?>
</dl><div class="actions"><a href="administrador.php?module=promociones_tienda">Volver</a><a href="administrador.php?module=promociones_tienda&amp;mode=edit&amp;id_promocion=<?= e($promotionId) ?>">Editar</a></div></section>
<?php return; endif ?>
<?php if (in_array($mode,['create','edit'],true)): ?>
<section class="card">
    <h2><?= $mode==='create' ? 'Crear promoción' : 'Configurar promoción' ?></h2>
    <p>Fechas y horas de Costa Rica. El vencimiento se valida al confirmar cada compra.</p>
    <form method="post" action="administrador.php?module=promociones_tienda" class="record-form" data-promotion-form>
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="action" value="<?= $promotionId ? 'update' : 'create' ?>">
        <?php if ($promotionId): ?><input type="hidden" name="id_promocion" value="<?= e($promotionId) ?>"><?php endif ?>
        <div class="form-grid">
            <label>Nombre de la promoción *<input name="titulo" maxlength="180" value="<?= e($form['titulo']) ?>" required placeholder="Ejemplo: 15% en productos de cuidado"></label>
            <label>Estado<select name="activo"><option value="1" <?= (string)$form['activo']==='1'?'selected':'' ?>>Activa</option><option value="0" <?= (string)$form['activo']==='0'?'selected':'' ?>>Inactiva</option></select></label>
            <label>Tipo de descuento *<select name="tipo_descuento"><option value="porcentaje" <?= $form['tipo_descuento']==='porcentaje'?'selected':'' ?>>Porcentaje (%)</option><option value="monto" <?= $form['tipo_descuento']==='monto'?'selected':'' ?>>Monto fijo por compra (₡)</option></select></label>
            <label>Valor del descuento *<input type="number" name="valor_descuento" min="0.01" step="0.01" value="<?= e($form['valor_descuento']) ?>" required><small>En porcentaje, entre 0.01 y 100. En monto fijo, el importe en colones.</small></label>
            <label>Aplica a *<select name="alcance"><?php foreach ($scopeLabels as $value=>$text): ?><option value="<?= e($value) ?>" <?= $form['alcance']===$value?'selected':'' ?>><?= e($text) ?></option><?php endforeach ?></select></label>
            <label>Clientes que pueden usarla *<select name="publico"><option value="todos" <?= $form['publico']==='todos'?'selected':'' ?>>Todos los clientes</option><option value="grupo" <?= $form['publico']==='grupo'?'selected':'' ?>>Un grupo de clientes</option></select></label>
            <label data-promotion-audience>Grupo de clientes *<select name="id_grupo"><option value="">Selecciona un grupo</option><?php foreach ($promotionGroups as $group): ?><option value="<?= e($group['id_grupo']) ?>" <?= (string)$form['id_grupo']===(string)$group['id_grupo']?'selected':'' ?>><?= e($group['nombre']) ?><?= !$group['activo']?' (inactivo)':'' ?></option><?php endforeach ?></select><small>Crea el grupo y asigna sus clientes desde los subapartados de Tienda.</small></label>
            <label>Regla de compra *<select name="regla_minimo"><option value="siempre" <?= $form['regla_minimo']==='siempre'?'selected':'' ?>>Sin compra mínima</option><option value="minimo" <?= $form['regla_minimo']==='minimo'?'selected':'' ?>>Desde una compra mínima</option></select></label>
            <label data-promotion-minimum>Compra mínima (₡) *<input type="number" name="minimo_compra" min="0.01" step="0.01" value="<?= e($form['minimo_compra']) ?>"></label>
            <label>Inicio *<input type="datetime-local" name="fecha_inicio" value="<?= e($form['fecha_inicio']) ?>" required></label>
            <label>Vencimiento *<input type="datetime-local" name="fecha_fin" value="<?= e($form['fecha_fin']) ?>" required></label>
            <label>Descripción para el cliente<textarea name="descripcion" rows="3" maxlength="5000"><?= e($form['descripcion']) ?></textarea></label>
        </div>
        <fieldset class="promotion-picker" data-promotion-scope="productos">
            <legend>Selecciona uno o varios artículos</legend>
            <label>Buscar artículos<input type="search" data-promotion-search placeholder="Nombre o código del artículo"></label>
            <div class="promotion-options">
                <?php foreach ($promotionProducts as $product): ?><label class="promotion-choice"><input type="checkbox" name="productos[]" value="<?= e($product['id_producto']) ?>" <?= in_array((string)$product['id_producto'],array_map('strval',$form['productos']),true)?'checked':'' ?>><span><?= e($product['nombre']) ?> · <?= e($product['sku']) ?><?= !$product['activo']?' (inactivo)':'' ?></span></label><?php endforeach ?>
                <?php if (!$promotionProducts): ?><p>Agrega artículos antes de configurar este alcance.</p><?php endif ?>
            </div>
        </fieldset>
        <fieldset class="promotion-picker" data-promotion-scope="categorias">
            <legend>Selecciona una o varias categorías</legend>
            <label>Buscar categorías<input type="search" data-promotion-search placeholder="Nombre de la categoría"></label>
            <div class="promotion-options">
                <?php foreach ($promotionCategories as $category): ?><label class="promotion-choice"><input type="checkbox" name="categorias[]" value="<?= e($category['id_categoria_producto']) ?>" <?= in_array((string)$category['id_categoria_producto'],array_map('strval',$form['categorias']),true)?'checked':'' ?>><span><?= e($category['nombre']) ?><?= !$category['activo']?' (inactiva)':'' ?></span></label><?php endforeach ?>
                <?php if (!$promotionCategories): ?><p>Crea una categoría antes de configurar este alcance.</p><?php endif ?>
            </div>
        </fieldset>
        <div class="actions"><button type="submit">Guardar promoción</button><a href="administrador.php?module=promociones_tienda">Cancelar</a></div>
    </form>
</section>
<?php endif ?>
<section class="card">
    <form method="get" action="administrador.php" class="filters">
        <input type="hidden" name="module" value="promociones_tienda">
        <label>Buscar<input name="q" value="<?= e($search) ?>" placeholder="Nombre de la promoción"></label>
        <label>Estado<select name="activo"><option value="">Todos</option><option value="1" <?= $promotionStatus==='1'?'selected':'' ?>>Activas</option><option value="0" <?= $promotionStatus==='0'?'selected':'' ?>>Inactivas</option></select></label>
        <button>Filtrar</button><a href="administrador.php?module=promociones_tienda">Limpiar</a>
    </form>
    <p><?= e($list['total']) ?> promociones · Página <?= e($list['page']) ?></p>
    <div class="table-scroll"><table data-server-paginated>
        <thead><tr><th>Promoción</th><th>Descuento</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($list['rows'] as $promo):
            $now=gmdate('Y-m-d H:i:s');
            $status=!$promo['activo']?'Inactiva':($promo['fecha_fin']<=$now?'Vencida':($promo['fecha_inicio']>$now?'Programada':'Vigente'));
        ?>
            <tr><td class="list-value"><?= e($promo['titulo']) ?></td><td><?= e(StorePromotions::benefit($promo)) ?></td><td><?= e($status) ?></td><td><div class="actions"><a href="administrador.php?module=promociones_tienda&amp;mode=view&amp;id_promocion=<?= e($promo['id_promocion']) ?>">Ver</a><a href="administrador.php?module=promociones_tienda&amp;mode=edit&amp;id_promocion=<?= e($promo['id_promocion']) ?>">Editar</a><form method="post" action="administrador.php?module=promociones_tienda"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="id_promocion" value="<?= e($promo['id_promocion']) ?>"><input type="hidden" name="action" value="<?= $promo['activo']?'deactivate':'activate' ?>"><button class="secondary"><?= $promo['activo']?'Desactivar':'Activar' ?></button></form></div></td></tr>
        <?php endforeach ?>
        <?php if (!$list['rows']): ?><tr><td colspan="4" class="empty">No hay promociones. Usa Crear promoción para agregar la primera.</td></tr><?php endif ?>
        </tbody>
    </table></div>
    <?php renderPagination((int)$list['total'], (int)$list['page'], array_replace($_GET, ['module'=>$moduleId])); ?>
</section>
