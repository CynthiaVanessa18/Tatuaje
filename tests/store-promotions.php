<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../app/Core/bootstrap.php';
require __DIR__.'/../app/Services/ClientCheckout.php';
require __DIR__.'/../app/Services/StorePromotionAdmin.php';
require __DIR__.'/temporary-store-promotions.php';
$db=conectarBaseDatos();
temporaryStorePromotions($db);
foreach (['categorias_productos','productos','clientes','ventas','detalle_ventas','pagos'] as $table) temporaryStoreTable($db,$table);
$db->exec("INSERT INTO categorias_productos (id_categoria_producto,nombre) VALUES (1,'Cuidados'),(2,'Accesorios')");
$db->exec("INSERT INTO productos (id_producto,id_categoria_producto,sku,nombre,precio,stock_actual) VALUES (1,1,'TEST-1','Crema','1000.00',100),(2,2,'TEST-2','Accesorio','2000.00',100),(3,1,'TEST-3','Jabón','333.33',100)");
$db->exec("INSERT INTO clientes (id_cliente,id_cuenta,nombre,apellidos) VALUES (1,1,'Cliente','Grupo'),(2,2,'Cliente','General')");
$db->exec("INSERT INTO grupos_clientes (id_grupo,nombre) VALUES (1,'Preferentes')");
$db->exec('INSERT INTO clientes_grupos (id_grupo,id_cliente) VALUES (1,1)');
$admin=new StorePromotionAdmin($db); $checks=0;
function promoCheck(bool $condition,string $message): void { global $checks; if (!$condition) throw new RuntimeException($message); ++$checks; }
function promoReject(callable $action,string $message): void { try { $action(); } catch (DomainException $ex) { promoCheck(true,$message); return; } throw new RuntimeException($message); }
function promotionData(array $changes=[]): array {
    $now=new DateTimeImmutable('now',new DateTimeZone('America/Costa_Rica'));
    return array_replace(['titulo'=>'Oferta de prueba','descripcion'=>'Descripción','tipo_descuento'=>'porcentaje','valor_descuento'=>'10.00','alcance'=>'tienda','publico'=>'todos','id_grupo'=>'','productos'=>[],'categorias'=>[],'regla_minimo'=>'siempre','minimo_compra'=>'0.00','activo'=>'1','fecha_inicio'=>$now->modify('-1 day')->format('Y-m-d\TH:i'),'fecha_fin'=>$now->modify('+1 day')->format('Y-m-d\TH:i')],$changes);
}
function onlyPromotion(array $data): int { global $db,$admin; $db->exec('UPDATE promociones SET activo=0'); return $admin->save($data); }
function quoteCart(array $cart=[1=>2,2=>1],int $client=1): array { global $db; return ClientCheckout::quote($db,$cart,$client); }

$id=onlyPromotion(promotionData());
$quote=quoteCart(); promoCheck($quote['subtotal']===400000 && $quote['discount']===40000 && $quote['total']===360000,'Porcentaje en toda la tienda');
promoCheck($admin->find($id)['fecha_inicio']===promotionData()['fecha_inicio'],'Fechas Costa Rica se guardan y recuperan');
$admin->save(promotionData(['titulo'=>'Editada','valor_descuento'=>'15.00']),$id);
promoCheck(quoteCart()['discount']===60000 && $admin->find($id)['titulo']==='Editada','Edición de promoción');
$admin->setActive($id,false); promoCheck(quoteCart()['discount']===0,'Desactivación');
$admin->setActive($id,true); promoCheck(quoteCart()['discount']===60000,'Activación');

$id=onlyPromotion(promotionData(['alcance'=>'productos','productos'=>['1','3'],'tipo_descuento'=>'monto','valor_descuento'=>'500.00']));
$quote=quoteCart(); promoCheck($quote['discount']===50000 && $quote['items'][1]['descuento_centavos']===0,'Monto fijo una vez y solo artículos seleccionados');
promoCheck($admin->find($id)['productos']===[1,3],'Selección múltiple persistida');
$admin->save(promotionData(['alcance'=>'categorias','categorias'=>['2'],'valor_descuento'=>'25.00']),$id);
$quote=quoteCart(); promoCheck($quote['discount']===50000 && $quote['items'][0]['descuento_centavos']===0,'Categoría seleccionada y limpieza del alcance anterior');
promoCheck($admin->find($id)['productos']===[] && $admin->find($id)['categorias']===[2],'Sin objetivos obsoletos al cambiar de alcance');

onlyPromotion(promotionData(['publico'=>'grupo','id_grupo'=>'1']));
promoCheck(quoteCart(client:1)['discount']===40000 && quoteCart(client:2)['discount']===0,'Grupo restringe descuento');
promoCheck(StorePromotions::available($db,null)===[],'No muestra oferta de grupo sin cliente');
$db->exec('UPDATE grupos_clientes SET activo=0'); promoCheck(quoteCart()['discount']===0,'Grupo inactivo no participa'); $db->exec('UPDATE grupos_clientes SET activo=1');

onlyPromotion(promotionData(['alcance'=>'categorias','categorias'=>['2'],'regla_minimo'=>'minimo','minimo_compra'=>'5000.00']));
promoCheck(quoteCart()['discount']===0,'Compra por debajo del mínimo');
promoCheck(quoteCart()['pending_promotions'][0]['remaining']===100000,'Indica monto pendiente para alcanzar el mínimo');
promoCheck(quoteCart([1=>1])['pending_promotions']===[],'No ofrece descuento pendiente sin artículos elegibles');
promoCheck(quoteCart([1=>3,2=>1])['pending_promotions']===[],'Elimina aviso al cumplir el mínimo');
promoCheck(quoteCart([1=>3,2=>1])['discount']===20000,'Mínimo sobre subtotal completo y descuento solo categoría elegible');

$now=new DateTimeImmutable('now',new DateTimeZone('America/Costa_Rica'));
onlyPromotion(promotionData(['fecha_inicio'=>$now->modify('-3 days')->format('Y-m-d\TH:i'),'fecha_fin'=>$now->modify('-2 days')->format('Y-m-d\TH:i')]));
promoCheck(quoteCart()['discount']===0,'Oferta vencida');
onlyPromotion(promotionData(['fecha_inicio'=>$now->modify('+2 days')->format('Y-m-d\TH:i'),'fecha_fin'=>$now->modify('+3 days')->format('Y-m-d\TH:i')]));
promoCheck(quoteCart()['discount']===0,'Oferta programada no se aplica antes de inicio');

onlyPromotion(promotionData());
$best=$admin->save(promotionData(['tipo_descuento'=>'monto','valor_descuento'=>'700.00']));
$quote=quoteCart(); promoCheck($quote['discount']===110000 && count($quote['promotions'])===2,'Acumula porcentaje y monto fijo');
promoCheck(array_sum(array_column($quote['items'],'descuento_centavos'))===$quote['discount'],'Reparto exacto del monto fijo');
$sale=ClientCheckout::order($db,1,[1=>2,2=>1],'pasarela','test-promociones-aprobada','aprobado',$quote['total']);
$row=$db->query('SELECT * FROM ventas WHERE id_venta='.$sale)->fetch();
promoCheck($row['subtotal']==='4000.00' && $row['descuento_total']==='1100.00' && $row['total']==='2900.00','Venta conserva base, descuento y total acumulado');
promoCheck($db->query('SELECT monto FROM pagos WHERE id_venta='.$sale)->fetchColumn()==='2900.00','Pago usa total descontado');
promoCheck($db->query('SELECT SUM(descuento) FROM detalle_ventas WHERE id_venta='.$sale)->fetchColumn()==='1100.00','Detalles conservan descuento exacto');
promoCheck((int)$db->query('SELECT COUNT(*) FROM detalle_ventas WHERE id_venta='.$sale.' AND id_promocion IS NULL')->fetchColumn()===2,'Descuento acumulado no se atribuye a una sola promoción');
promoCheck(ClientCheckout::order($db,1,[],'pasarela','test-promociones-aprobada','aprobado',1)===$sale,'Reintento idempotente incluso si cambia oferta');
$stockBeforeReject=(int)$db->query('SELECT stock_actual FROM productos WHERE id_producto=1')->fetchColumn();
$rejected=ClientCheckout::order($db,1,[1=>2,2=>1],'pasarela','test-promocion-rechazada','rechazado',$quote['total']);
promoCheck($db->query('SELECT estado FROM ventas WHERE id_venta='.$rejected)->fetchColumn()==='cancelada','Oferta con pago rechazado cancela venta');
promoCheck((int)$db->query('SELECT stock_actual FROM productos WHERE id_producto=1')->fetchColumn()===$stockBeforeReject,'Pago rechazado con oferta no descuenta inventario');

$saleCount=(int)$db->query('SELECT COUNT(*) FROM ventas')->fetchColumn();
$stock=(int)$db->query('SELECT stock_actual FROM productos WHERE id_producto=1')->fetchColumn();
$db->exec('UPDATE promociones SET fecha_fin=UTC_TIMESTAMP() WHERE activo=1');
promoReject(fn()=>ClientCheckout::order($db,1,[1=>2,2=>1],'efectivo','test-expirada','aprobado',$quote['total']),'Revalidación de vencimiento al confirmar');
promoCheck((int)$db->query('SELECT COUNT(*) FROM ventas')->fetchColumn()===$saleCount && (int)$db->query('SELECT stock_actual FROM productos WHERE id_producto=1')->fetchColumn()===$stock,'Cambio de total no crea pedido ni descuenta inventario');

onlyPromotion(promotionData(['tipo_descuento'=>'monto','valor_descuento'=>'0.01']));
$quote=quoteCart([1=>1,3=>3]);
promoCheck($quote['discount']===1 && array_sum(array_column($quote['items'],'descuento_centavos'))===1,'Redondeo de un centavo repartido');
onlyPromotion(promotionData(['tipo_descuento'=>'monto','valor_descuento'=>'9999.00']));
promoCheck(quoteCart()['total']===0,'Descuento nunca supera artículos elegibles');
$freeSale=ClientCheckout::order($db,1,[1=>2,2=>1],'efectivo','test-total-cero','aprobado',0);
promoCheck($db->query('SELECT total FROM ventas WHERE id_venta='.$freeSale)->fetchColumn()==='0.00','Pedido cubierto por promoción');
promoCheck((int)$db->query('SELECT COUNT(*) FROM pagos WHERE id_venta='.$freeSale)->fetchColumn()===0,'Pedido gratuito no crea pago ficticio');
promoCheck(ClientCheckout::order($db,1,[],'efectivo','test-total-cero')===$freeSale,'Pedido gratuito idempotente');
onlyPromotion(promotionData(['valor_descuento'=>'100.00']));
promoCheck(quoteCart()['discount']===400000,'Porcentaje del cien por ciento');
onlyPromotion(promotionData(['valor_descuento'=>'15.00']));
$admin->save(promotionData(['valor_descuento'=>'6.00']));
promoCheck(quoteCart()['discount']===84000,'15 por ciento más 6 por ciento equivale a 21 por ciento del subtotal');
$admin->save(promotionData(['valor_descuento'=>'100.00']));
$stacked=quoteCart();
promoCheck($stacked['total']===0 && array_sum(array_column($stacked['items'],'descuento_centavos'))===400000,'Acumulación nunca excede precio de artículos');
onlyPromotion(promotionData(['valor_descuento'=>'33.33']));
promoCheck(quoteCart([3=>1])['discount']===11110,'Redondeo porcentual exacto de dos decimales');

foreach ([['valor_descuento'=>'101'],['valor_descuento'=>'-1'],['alcance'=>'productos','productos'=>[]],['alcance'=>'productos','productos'=>['999']],['alcance'=>'categorias','categorias'=>[]],['publico'=>'grupo','id_grupo'=>'999'],['regla_minimo'=>'minimo','minimo_compra'=>'0'],['fecha_fin'=>'2020-01-01T00:00'],['fecha_fin'=>'2026-02-30T10:00']] as $invalid) {
    promoReject(fn()=>$admin->save(promotionData($invalid)),'Validación de reglas y selección');
}
promoCheck(!$db->inTransaction(),'No quedan transacciones abiertas tras errores');
promoCheck(StorePromotions::benefit(['tipo_descuento'=>'porcentaje','valor_descuento'=>'10'])==='10% de descuento','Porcentaje entero se muestra sin perder ceros');
$cartTotal=0;$profileMissing=false;$cartError=null;$cartCount=1;$tokenKey='test_checkout';$_SESSION[$tokenKey]='test-token';
$cartSubtotal=400000;$cartDiscount=60000;$checkoutBarStep='pago';$checkoutBarTotal=3400;$checkoutBarInfo='3 artículos';
ob_start();require __DIR__.'/../app/Views/cliente-compra-barra.php';$discountBarHtml=ob_get_clean();
promoCheck(str_contains($discountBarHtml,'Subtotal: ₡4.000,00') && str_contains($discountBarHtml,'Descuento: −₡600,00') && str_contains($discountBarHtml,'Total con descuento') && str_contains($discountBarHtml,'₡3.400,00'),'Pago muestra subtotal, descuento y monto final junto a confirmar');
ob_start();require __DIR__.'/../app/Views/cliente-pago.php';$freePaymentHtml=ob_get_clean();
promoCheck(str_contains($freePaymentHtml,'name="expected_total" value="0"') && !str_contains($freePaymentHtml,'data-payment-choice'),'Pedido gratuito no exige seleccionar método de pago');
$cartReceipt=['metodo'=>'sin_cobro','estado'=>'aprobado','total'=>'0.00','descuento_total'=>'1000.00'];
ob_start();require __DIR__.'/../app/Views/cliente-confirmacion.php';$freeReceiptHtml=ob_get_clean();
promoCheck(str_contains($freeReceiptHtml,'Pedido confirmado sin pago') && str_contains($freeReceiptHtml,'Cubierto por promoción'),'Confirmación de pedido gratuito');
echo "OK: $checks comprobaciones de promociones, grupos, mínimos, vigencia, redondeo, venta y pago. Solo tablas temporales.\n";
