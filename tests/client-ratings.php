<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../app/Core/bootstrap.php';
require __DIR__.'/../app/Services/ClientRatings.php';
$db=conectarBaseDatos();
// These connection-local tables shadow the real tables without changing customer data.
$db->exec('CREATE TEMPORARY TABLE clientes (id_cliente INT PRIMARY KEY, id_cuenta INT)');
$db->exec('CREATE TEMPORARY TABLE artistas (id_artista INT PRIMARY KEY, nombre_artistico VARCHAR(100), nombre VARCHAR(100), apellidos VARCHAR(100))');
$db->exec('CREATE TEMPORARY TABLE citas (id_cita INT PRIMARY KEY, id_cliente INT, id_artista INT, estado VARCHAR(30), fecha_hora_inicio DATETIME)');
$db->exec('CREATE TEMPORARY TABLE calificaciones_artistas (id_calificacion INT AUTO_INCREMENT PRIMARY KEY, id_cita INT UNIQUE, puntuacion INT, comentario TEXT, estado_publicacion VARCHAR(30)) ENGINE=InnoDB');
$db->exec("INSERT INTO clientes VALUES (1,10),(2,20)");
$db->exec("INSERT INTO artistas VALUES (1,'Artista','Nombre','Apellido')");
$db->exec("INSERT INTO citas VALUES (1,1,1,'finalizada','2026-01-01 12:00:00'),(2,1,1,'confirmada','2026-01-02 12:00:00'),(3,2,1,'finalizada','2026-01-03 12:00:00'),(4,1,1,'cancelada','2026-01-04 12:00:00')");
$service=new ClientRatings($db,10);
$checks=0;
function ratingCheck(bool $ok): void { global $checks; if (!$ok) throw new RuntimeException('Fallo de verificación'); ++$checks; }
function ratingReject(callable $action): void { try { $action(); } catch (DomainException $error) { ratingCheck(true); return; } throw new RuntimeException('Se aceptó una calificación inválida'); }
ratingCheck(count($service->appointments())===1);
ratingReject(fn()=>$service->submit(['id_cita'=>3,'puntuacion'=>5]));
ratingReject(fn()=>$service->submit(['id_cita'=>2,'puntuacion'=>5]));
ratingReject(fn()=>$service->submit(['id_cita'=>4,'puntuacion'=>5]));
foreach ([0,6,'3.5',[], 'cinco'] as $score) ratingReject(fn()=>$service->submit(['id_cita'=>1,'puntuacion'=>$score]));
ratingReject(fn()=>$service->submit(['id_cita'=>1,'puntuacion'=>5,'comentario'=>str_repeat('a',1501)]));
$service->submit(['id_cita'=>1,'puntuacion'=>5,'comentario'=>'Muy buena experiencia','id_artista'=>999]);
$items=$service->appointments();
ratingCheck((int)$items[0]['puntuacion']===5 && $items[0]['estado_publicacion']==='pendiente');
ratingReject(fn()=>$service->submit(['id_cita'=>1,'puntuacion'=>4]));
ratingCheck(!$db->inTransaction());
ratingCheck((new ClientRatings($db,20))->appointments()[0]['id_calificacion']===null);
echo "OK: $checks comprobaciones; solo tablas temporales.\n";
