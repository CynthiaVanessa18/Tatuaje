<?php
$navEndpoint=$clientEndpoint??'index.php';$navPrefix=$publicPrefix??'';$navSection=$_GET['section']??'';
$navStore=in_array($navSection,['tienda','carrito','tarjetas'],true);
?>
<div class="client-navigation">
<a class="client-navigation-brand" href="<?= e($navEndpoint) ?>">TINTA <strong>VIVA</strong></a>
<nav aria-label="Navegación principal">
<a href="<?= e($navEndpoint) ?>" <?= $navSection===''?'aria-current="page"':'' ?>>Inicio</a>
<a href="<?= e($navPrefix) ?>artistas/">Artistas</a>
<a href="<?= e($navPrefix) ?>galeria/">Galería</a>
<a href="<?= e($navPrefix) ?>cotizaciones/">Cotizar</a>
<a href="<?= e($navEndpoint) ?>#cuidados">Cuidados</a>
<a href="<?= e($navEndpoint) ?>?section=membresias" <?= $navSection==='membresias'?'aria-current="page"':'' ?>>Membresías</a>
<a href="<?= e($navEndpoint) ?>?section=tienda" <?= $navStore?'aria-current="page"':'' ?>>Tienda</a>
<a href="<?= e($navEndpoint) ?>?section=citas" <?= $navSection==='citas'?'aria-current="page"':'' ?>>Mis citas</a>
<a href="<?= e($navPrefix) ?>panel/mis-cotizaciones.php">Mis cotizaciones</a>
<a href="<?= e($navPrefix) ?>panel/cliente.php">Mi espacio</a>
</nav>
<a class="client-navigation-account" href="<?= e($navEndpoint) ?>?section=cuenta" aria-label="Mi cuenta" title="Mi cuenta" <?= $navSection==='cuenta'?'aria-current="page"':'' ?>><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg></a>
<form method="post" action="<?= e($navPrefix) ?>auth/logout.php"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button type="submit">Cerrar sesión</button></form>
</div>
