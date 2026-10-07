<?php
$benefitSections=[
    'productos'=>['Productos','productos','promociones_tienda','Configurar promociones'],
    'membresias'=>['Membresías','planes','planes','Configurar membresías'],
    'tarjetas'=>['Tarjetas de regalo','tarjetas','tarjetas','Gestionar tarjetas'],
];
$selectedBenefit=$benefitsOverview['section'];
[$benefitTitle,$benefitIcon,$benefitDestination,$benefitAction]=$benefitSections[$selectedBenefit];
$benefitDate=static fn(?string $date): string=>$date
    ? (new DateTimeImmutable($date,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Costa_Rica'))->format('d/m/Y H:i')
    : 'Sin vencimiento';
?>
<nav class="benefit-tabs" aria-label="Tipos de beneficios">
<?php foreach ($benefitSections as $key=>[$title,$icon]): ?>
<a href="administrador.php?module=beneficios&amp;benefit_section=<?= e($key) ?>" <?= $selectedBenefit===$key?'aria-current="page"':'' ?>><?= adminIcon($icon) ?><span><?= e($title) ?></span><b><?= e($benefitsOverview['counts'][$key]) ?></b></a>
<?php endforeach ?>
</nav>
<section class="card">
    <div class="benefit-heading"><div><p class="eyebrow">BENEFICIOS VIGENTES</p><h2><?= e($benefitTitle) ?></h2><p><?= e([
        'productos'=>'Promociones automáticas vigentes. Su aplicación depende de los artículos, el público y la compra mínima; el carrito elige la de mayor ahorro.',
        'membresias'=>'Beneficios incluidos en los planes activos. Las cantidades corresponden al período contratado y los servicios conservan las condiciones de cada plan.',
        'tarjetas'=>'Tarjetas activas en colones con saldo disponible y sin vencer. El saldo sirve para pagar compras en la tienda.',
    ][$selectedBenefit]) ?></p></div><a class="button" href="administrador.php?module=<?= e($benefitDestination) ?>"><?= adminIcon('editar') ?> <?= e($benefitAction) ?> ↗</a></div>
    <p class="hint"><?= e($benefitsOverview['total']) ?> <?= $selectedBenefit==='membresias'?'beneficios incluidos':($selectedBenefit==='productos'?'promociones disponibles':'tarjetas disponibles') ?> · Página <?= e($benefitsOverview['page']) ?></p>
    <div class="benefit-grid">
    <?php foreach ($benefitsOverview['rows'] as $benefit): ?>
    <article class="benefit-card">
        <span class="dashboard-stat-icon"><?= adminIcon($benefitIcon) ?></span>
        <?php if ($selectedBenefit==='productos'): ?>
        <p class="eyebrow">PROMOCIÓN DE TIENDA</p><h3><?= e($benefit['titulo']) ?></h3>
        <strong class="benefit-value"><?= $benefit['tipo_descuento']==='porcentaje'?e(rtrim(rtrim((string)$benefit['valor_descuento'],'0'),'.')).' %':'₡ '.e(number_format((float)$benefit['valor_descuento'],2,',','.')) ?> de descuento</strong>
        <?php if ($benefit['descripcion']): ?><p><?= e($benefit['descripcion']) ?></p><?php endif ?>
        <dl><dt>Aplica a</dt><dd><?= e(match($benefit['alcance']) {'tienda'=>'Toda la tienda','productos'=>$benefit['productos']?:'Productos seleccionados','categorias'=>$benefit['categorias']?:'Categorías seleccionadas',default=>'Consultar promoción'}) ?></dd><dt>Público</dt><dd><?= e($benefit['publico']==='todos'?'Todos los clientes':($benefit['grupo']??'Grupo de clientes')) ?></dd><dt>Compra mínima</dt><dd>₡ <?= e(number_format((float)$benefit['minimo_compra'],2,',','.')) ?></dd><dt>Vence</dt><dd><?= e($benefitDate($benefit['fecha_fin'])) ?> · Costa Rica</dd></dl>
        <a href="administrador.php?module=promociones_tienda&amp;mode=edit&amp;id_promocion=<?= e($benefit['id_promocion']) ?>"><?= adminIcon('editar') ?> Configurar promoción ↗</a>
        <?php elseif ($selectedBenefit==='membresias'): ?>
        <p class="eyebrow"><?= e($benefit['nombre']) ?></p><h3><?= e($benefit['beneficio']) ?></h3><p><?= e($benefit['descripcion']) ?></p>
        <a href="administrador.php?module=planes&amp;plan=<?= e($benefit['id_plan']) ?>"><?= adminIcon('editar') ?> Configurar este plan ↗</a>
        <?php else: ?>
        <p class="eyebrow">TARJETA #<?= e($benefit['id_tarjeta']) ?></p><h3>Para <?= e($benefit['nombre_destinatario']) ?></h3><strong class="benefit-value">₡ <?= e(number_format((float)$benefit['saldo'],2,',','.')) ?></strong><p>Saldo disponible para compras en la tienda.</p><dl><dt>Vencimiento</dt><dd><?= e($benefitDate($benefit['fecha_vencimiento'])) ?><?= $benefit['fecha_vencimiento']?' · Costa Rica':'' ?></dd></dl>
        <a href="administrador.php?module=tarjetas&amp;mode=view&amp;id_tarjeta=<?= e($benefit['id_tarjeta']) ?>"><?= adminIcon('registros') ?> Abrir tarjeta ↗</a>
        <?php endif ?>
    </article>
    <?php endforeach ?>
    </div>
    <?php if (!$benefitsOverview['rows']): ?><div class="benefit-empty"><span class="dashboard-stat-icon"><?= adminIcon($benefitIcon) ?></span><h3>No hay <?= e(strtolower($benefitTitle)) ?> con beneficios disponibles</h3><p>Revisa la configuración, el estado y las fechas en el apartado original.</p><a href="administrador.php?module=<?= e($benefitDestination) ?>"><?= e($benefitAction) ?> →</a></div><?php endif ?>
    <nav class="actions pagination" aria-label="Páginas de beneficios">
    <?php foreach ([$benefitsOverview['page']-1=>'← Anterior',$benefitsOverview['page']+1=>'Siguiente →'] as $page=>$caption): if ($page<1 || $page>max(1,(int)ceil($benefitsOverview['total']/20))) continue; ?>
    <a href="administrador.php?<?= e(http_build_query(['module'=>'beneficios','benefit_section'=>$selectedBenefit,'page'=>$page])) ?>"><?= e($caption) ?></a>
    <?php endforeach ?>
    </nav>
</section>
