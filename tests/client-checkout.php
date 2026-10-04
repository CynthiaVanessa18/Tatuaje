<?php
declare(strict_types=1);
require __DIR__.'/../app/Core/bootstrap.php';
require __DIR__.'/../app/Services/ClientCheckout.php';

// Tablas temporales: nunca se insertan ni modifican datos operativos.
$db=conectarBaseDatos();
$db->exec('CREATE TEMPORARY TABLE categorias_productos (id_categoria_producto INT PRIMARY KEY,activo INT) ENGINE=InnoDB');
$db->exec('CREATE TEMPORARY TABLE productos (id_producto INT PRIMARY KEY,id_categoria_producto INT,nombre VARCHAR(180),precio DECIMAL(12,2),stock_actual INT,activo INT) ENGINE=InnoDB');
$db->exec('CREATE TEMPORARY TABLE ventas (id_venta INT AUTO_INCREMENT PRIMARY KEY,id_cliente INT,estado VARCHAR(20),moneda CHAR(3),subtotal DECIMAL(12,2),total DECIMAL(12,2) AS (subtotal) STORED) ENGINE=InnoDB');
$db->exec('CREATE TEMPORARY TABLE detalle_ventas (id_detalle_venta INT AUTO_INCREMENT PRIMARY KEY,id_venta INT,tipo_item VARCHAR(30),id_producto INT,descripcion VARCHAR(255),cantidad INT,precio_unitario DECIMAL(12,2)) ENGINE=InnoDB');
$db->exec('CREATE TEMPORARY TABLE pagos (id_pago INT AUTO_INCREMENT PRIMARY KEY,id_venta INT,metodo VARCHAR(30),monto DECIMAL(12,2),moneda CHAR(3),estado VARCHAR(30),clave_idempotencia VARCHAR(128) UNIQUE,proveedor VARCHAR(80),referencia_externa VARCHAR(190),UNIQUE(proveedor,referencia_externa)) ENGINE=InnoDB');
$db->exec('INSERT INTO categorias_productos VALUES (1,1)');
$db->exec("INSERT INTO productos VALUES (1,1,'Artículo de prueba',1200.50,20,1)");
function checkCheckout(bool $condition,string $message): void { if (!$condition) throw new RuntimeException($message); }
$token=bin2hex(random_bytes(32));
$sale=ClientCheckout::order($db,1,[1=>2],'pasarela',$token,'aprobado');
checkCheckout($db->query('SELECT estado FROM ventas')->fetchColumn()==='completada','Venta aprobada incorrecta');
checkCheckout($db->query('SELECT total FROM ventas')->fetchColumn()==='2401.00','Total incorrecto');
checkCheckout($db->query('SELECT estado FROM pagos')->fetchColumn()==='aprobado','Pago aprobado incorrecto');
checkCheckout($db->query('SELECT proveedor FROM pagos')->fetchColumn()==='demo_tienda','Falta identificar demostración');
checkCheckout((int)$db->query('SELECT stock_actual FROM productos')->fetchColumn()===18,'Stock incorrecto');
checkCheckout(ClientCheckout::order($db,1,[],'pasarela',$token,'aprobado')===$sale,'Reintento no idempotente');
checkCheckout((int)$db->query('SELECT COUNT(*) FROM ventas')->fetchColumn()===1,'Venta duplicada');
$rejected=ClientCheckout::order($db,1,[1=>2],'pasarela',bin2hex(random_bytes(32)),'rechazado');
checkCheckout($db->query('SELECT estado FROM ventas WHERE id_venta='.$rejected)->fetchColumn()==='cancelada','Rechazo no cancela venta');
checkCheckout((int)$db->query('SELECT stock_actual FROM productos')->fetchColumn()===18,'Rechazo descontó stock');
foreach (['efectivo','transferencia'] as $method) {
    $id=ClientCheckout::order($db,1,[1=>1],$method,bin2hex(random_bytes(32)));
    checkCheckout($db->query('SELECT estado FROM pagos WHERE id_venta='.$id)->fetchColumn()==='pendiente','Pago presencial aprobado indebidamente');
}
$before=(int)$db->query('SELECT COUNT(*) FROM ventas')->fetchColumn();
foreach ([['pasarela','otro',[1=>1]],['otro','aprobado',[1=>1]],['pasarela','aprobado',[1=>99]]] as [$method,$result,$cart]) {
    try { ClientCheckout::order($db,1,$cart,$method,bin2hex(random_bytes(32)),$result); throw new RuntimeException('Aceptó una compra inválida'); }
    catch (DomainException $e) {}
}
checkCheckout((int)$db->query('SELECT COUNT(*) FROM ventas')->fetchColumn()===$before,'Compra inválida dejó datos');
checkCheckout((int)$db->query('SELECT stock_actual FROM productos')->fetchColumn()===16,'Stock final incorrecto');
echo "OK: tarjeta aprobada/rechazada, efectivo, transferencia, totales, inventario e idempotencia. Solo tablas temporales.\n";
