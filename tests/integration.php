<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || getenv('DB_NAME') !== 'tattoo_crud_test')
    exit("Solo se ejecuta con DB_NAME=tattoo_crud_test por consola.\n");
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/Models/CrudRepository.php';
require __DIR__ . '/../app/Services/CrudService.php';
$db = conectarBaseDatos();
$modules = require __DIR__ . '/../config/modules.php';
$checks = 0;
if ((int) $db->query('SELECT COUNT(*) FROM cuentas')->fetchColumn())
    exit("Usa una base de prueba recién importada, sin cuentas.\n");
function checkTest(bool $ok, string $name): void
{
    global $checks;
    if (!$ok)
        throw new RuntimeException($name);
    ++$checks;
    echo "OK $name\n";
}
function repository(string $m): CrudRepository
{
    global $db, $modules;
    return new CrudRepository($db, $modules[$m]);
}
function dataFor(string $m, array $values = []): array
{
    $r = repository($m);
    $data = [];
    foreach ($r->columns() as $k => $c)
        if ($r->editable($c))
            $data[$k] = $c['Default'] ?? '';
    return array_replace($data, $values);
}
function createTest(string $m, array $values): string
{
    global $db;
    (new CrudService(repository($m)))->execute('create', dataFor($m, $values), null);
    return (string) $db->query('SELECT LAST_INSERT_ID()')->fetchColumn();
}
function rejectTest(callable $fn, string $label): void
{
    try {
        $fn();
    } catch (DomainException | PDOException $e) {
        checkTest(true, $label);
        return;
    }
    throw new RuntimeException('No rechazó: ' . $label);
}
function updateTest(string $m, array $key, array $changes): void
{
    $r = repository($m);
    (new CrudService($r))->execute('update', array_replace($r->find($key), $changes), $key);
}
foreach ($modules as $m => $cfg) {
    $r = repository($m);
    checkTest(count($r->columns()) > 0, 'Columnas ' . $m);
    $r->references();
    $r->listing('', [], 1);
}
$category = createTest('categorias', ['nombre' => 'Categoría de prueba']);
updateTest('categorias', ['id_categoria' => $category], ['nombre' => 'Categoría editada']);
checkTest(repository('categorias')->listing('editada', [], 1)['total'] === 1, 'Buscar categoría editada');
rejectTest(fn() => createTest('categorias', ['nombre' => 'Categoría editada']), 'Nombre duplicado');
(new CrudService(repository('categorias')))->execute('delete', [], ['id_categoria' => $category]);
checkTest(repository('categorias')->listing('editada', [], 1)['total'] === 0, 'Eliminar categoría');
$db->exec("INSERT INTO cuentas (id_rol,usuario,correo,contrasena_hash,estado) SELECT id_rol,'cliente_test','cliente@test.invalid','unused','activo' FROM roles WHERE nombre_rol='cliente'");
$account = $db->lastInsertId();
$db->prepare("INSERT INTO clientes(id_cuenta,nombre,apellidos) VALUES (?,'Cliente','Prueba')")->execute([$account]);
$client = $db->lastInsertId();
$db->exec("INSERT INTO cuentas (id_rol,usuario,correo,contrasena_hash,estado) SELECT id_rol,'artista_test','artista@test.invalid','unused','activo' FROM roles WHERE nombre_rol='artista'");
$account = $db->lastInsertId();
$db->prepare("INSERT INTO artistas(id_cuenta,nombre,apellidos) VALUES (?,'Artista','Prueba')")->execute([$account]);
$artist = $db->lastInsertId();
createTest('especialidades', ['id_artista' => $artist, 'id_categoria' => '1']);
updateTest('especialidades', ['id_artista' => $artist, 'id_categoria' => '1'], ['id_categoria' => '2']);
(new CrudService(repository('especialidades')))->execute('delete', [], ['id_artista' => $artist, 'id_categoria' => '2']);
checkTest(true, 'CRUD de clave compuesta');
$db->prepare("INSERT INTO horarios_artistas(id_artista,dia_semana,hora_inicio,hora_fin) VALUES (?,4,'08:00:00','17:00:00')")->execute([$artist]);
$db->prepare("INSERT INTO citas(id_cliente,id_artista,fecha_hora_inicio,fecha_hora_fin,estado) VALUES (?,?,'2026-01-01 10:00:00','2026-01-01 11:00:00','pendiente')")->execute([$client, $artist]);
$appointment = $db->lastInsertId();
rejectTest(fn() => createTest('calificaciones', ['id_cita' => $appointment, 'puntuacion' => '5']), 'Cita no finalizada');
$db->exec("UPDATE citas SET estado='finalizada' WHERE id_cita=$appointment");
rejectTest(fn() => createTest('calificaciones', ['id_cita' => $appointment, 'puntuacion' => '6']), 'Calificación fuera de rango');
$rating = createTest('calificaciones', ['id_cita' => $appointment, 'puntuacion' => '5']);
updateTest('calificaciones', ['id_calificacion' => $rating], ['estado_publicacion' => 'publicado']);
checkTest((float) $db->query('SELECT promedio FROM vista_calificaciones_artistas LIMIT 1')->fetchColumn() === 5.0, 'Promedio de calificaciones publicadas');
rejectTest(fn() => createTest('calificaciones', ['id_cita' => $appointment, 'puntuacion' => '4']), 'Calificación duplicada por cita');
$promo = ['titulo' => 'Oferta test', 'tipo_descuento' => 'porcentaje', 'valor_descuento' => '10', 'fecha_inicio' => '2020-01-01 00:00:00', 'fecha_fin' => '2099-01-01 00:00:00'];
rejectTest(fn() => createTest('promociones', array_replace($promo, ['valor_descuento' => '101'])), 'Porcentaje mayor a cien');
rejectTest(fn() => createTest('promociones', array_replace($promo, ['fecha_fin' => '2019-01-01 00:00:00'])), 'Fechas invertidas');
$promotion = createTest('promociones', $promo);
$plan = createTest('planes', ['nombre' => 'Plan test', 'precio' => '1000', 'duracion_dias' => '30']);
$benefit = createTest('beneficios', ['nombre' => 'Prioridad', 'tipo' => 'prioridad']);
createTest('planes_beneficios', ['id_plan' => $plan, 'id_beneficio' => $benefit]);
rejectTest(fn() => (new CrudService(repository('beneficios')))->execute('delete', [], ['id_beneficio' => $benefit]), 'Eliminar beneficio con relaciones');
$cat = createTest('categorias_productos', ['nombre' => 'Cuidados']);
$product = createTest('productos', ['id_categoria_producto' => $cat, 'sku' => 'TEST-01', 'nombre' => 'Crema', 'precio' => '100', 'stock_actual' => '5']);
$image = createTest('imagenes', ['id_producto' => $product, 'imagen_url' => 'https://example.com/image.png']);
rejectTest(fn() => createTest('imagenes', ['id_producto' => $product, 'imagen_url' => 'javascript:alert(1)']), 'URL de imagen insegura');
$sale = createTest('ventas', ['id_cliente' => $client]);
$detail = createTest('detalle', ['id_venta' => $sale, 'tipo_item' => 'producto', 'id_producto' => $product, 'descripcion' => 'Crema', 'cantidad' => '2', 'precio_unitario' => '1', 'id_promocion' => $promotion]);
$row = repository('ventas')->find(['id_venta' => $sale]);
checkTest($row['subtotal'] === '200.00' && $row['descuento_total'] === '20.00' && $row['total'] === '180.00', 'Precio de catálogo, descuento y total calculados');
updateTest('ventas', ['id_venta' => $sale], ['estado' => 'confirmada']);
checkTest(repository('productos')->find(['id_producto' => $product])['stock_actual'] === 3, 'Confirmar descuenta inventario');
rejectTest(fn() => updateTest('ventas', ['id_venta' => $sale], ['estado' => 'confirmada']), 'Evitar doble confirmación');
rejectTest(fn() => updateTest('detalle', ['id_detalle_venta' => $detail], ['cantidad' => '1']), 'Detalle confirmado inmutable');
$sale2 = createTest('ventas', ['id_cliente' => $client]);
$detail2 = createTest('detalle', ['id_venta' => $sale2, 'tipo_item' => 'producto', 'id_producto' => $product, 'descripcion' => 'Sin stock', 'cantidad' => '4', 'precio_unitario' => '100']);
rejectTest(fn() => updateTest('ventas', ['id_venta' => $sale2], ['estado' => 'confirmada']), 'Rechazar inventario insuficiente');
checkTest(repository('ventas')->find(['id_venta' => $sale2])['estado'] === 'pendiente', 'Revertir confirmación fallida');
checkTest(repository('productos')->find(['id_producto' => $product])['stock_actual'] === 3, 'Stock intacto tras fallo');
(new CrudService(repository('detalle')))->execute('delete', [], ['id_detalle_venta' => $detail2]);
checkTest(repository('ventas')->find(['id_venta' => $sale2])['total'] === '0.00', 'Eliminar detalle recalcula total');
$memberDetail = createTest('detalle', ['id_venta' => $sale2, 'tipo_item' => 'membresia', 'id_plan' => $plan, 'descripcion' => 'Plan test', 'precio_unitario' => '1000']);
$cardDetail = createTest('detalle', ['id_venta' => $sale2, 'tipo_item' => 'tarjeta_regalo', 'descripcion' => 'Regalo', 'precio_unitario' => '500']);
updateTest('ventas', ['id_venta' => $sale2], ['estado' => 'confirmada']);
$member = createTest('membresias', ['id_cliente' => $client, 'id_plan' => $plan, 'id_detalle_venta' => $memberDetail, 'fecha_inicio' => '2026-01-01 00:00:00', 'fecha_fin' => '2026-01-31 00:00:00']);
updateTest('membresias', ['id_membresia' => $member], ['estado' => 'cancelada']);
checkTest(true, 'Crear y cancelar membresía');
$cardData = dataFor('tarjetas', ['id_cliente_comprador' => $client, 'id_detalle_venta' => $cardDetail, 'nombre_destinatario' => 'Prueba', 'correo_destinatario' => 'regalo@test.invalid', 'monto_inicial' => '500']);
$message = (new CrudService(repository('tarjetas')))->execute('create', $cardData, null);
preg_match('/código: ([A-F0-9]{32})/u', $message, $match);
$card = $db->query('SELECT * FROM tarjetas_regalo')->fetch();
checkTest(isset($match[1]) && hash('sha256', $match[1], true) === $card['codigo_hash'], 'Código de tarjeta almacenado como hash');
checkTest($db->query('SELECT saldo_actual FROM vista_saldo_tarjetas')->fetchColumn() === '500.00', 'Carga inicial y saldo de tarjeta');
rejectTest(fn() => (new CrudService(repository('tarjetas')))->execute('create', $cardData, null), 'Evitar doble emisión por detalle');
checkTest((int) $db->query('SELECT COUNT(*) FROM movimientos_tarjetas_regalo')->fetchColumn() === 1, 'Sin carga duplicada tras error');
updateTest('tarjetas', ['id_tarjeta' => (string) $card['id_tarjeta']], ['estado' => 'cancelada']);
rejectTest(fn() => updateTest('tarjetas', ['id_tarjeta' => (string) $card['id_tarjeta']], ['estado' => 'activa']), 'Tarjeta cancelada no reactiva');
checkTest(e('<script>') === '&lt;script&gt;', 'Escape HTML');
echo "Completado: $checks comprobaciones. Datos conservados solo en tattoo_crud_test para inspección.\n";
