<ol class="pasos-compra" aria-label="Proceso de compra"><li><span>1</span> Carrito</li><li><span>2</span> Pago</li><li aria-current="step"><span>3</span> Confirmación</li></ol>
<section class="card confirmacion-compra shadow-sm">
    <div class="confirmacion-icono" aria-hidden="true">✓</div>
    <p class="eyebrow">PEDIDO REGISTRADO</p>
    <h2><?= $cartReceipt['estado']==='aprobado'?'Compra de demostración completada':'Tu pedido está reservado' ?></h2>
    <p><?= $cartReceipt['estado']==='aprobado'?'La tarjeta de prueba fue aprobada. No se realizó ningún cobro real.':'El pago está pendiente de verificación. Coordina el retiro y pago con el estudio.' ?></p>
    <div class="pago-resumen-compacto"><div><span>Método de pago</span><strong><?= e(['efectivo'=>'Efectivo al retirar','transferencia'=>'Transferencia','pasarela'=>'Tarjeta de demostración'][$cartReceipt['metodo']]??label($cartReceipt['metodo'])) ?></strong></div><div><span>Total del pedido</span><strong>₡<?= e(number_format((float)$cartReceipt['total'],2,',','.')) ?></strong></div></div>
    <div class="row g-3 confirmacion-detalles"><div class="col-12 col-sm-6"><span>Estado del pago</span><strong><?= e(label($cartReceipt['estado'])) ?></strong></div><div class="col-12 col-sm-6"><span>Entrega</span><strong>Retiro en el estudio</strong></div></div>
</section>
<?php $checkoutBarStep='confirmacion';$checkoutBarTotal=(float)$cartReceipt['total'];$checkoutBarInfo='Pedido registrado';require __DIR__.'/cliente-compra-barra.php'; ?>
