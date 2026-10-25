<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../app/Core/bootstrap.php';
require __DIR__.'/../app/Services/ClientRatings.php';
$db=conectarBaseDatos();
// These connection-local tables shadow the real tables without changing customer data.
$db->exec('CREATE TEMPORARY TABLE clientes (id_cliente INT PRIMARY KEY, id_cuenta INT)');
$db->exec('CREATE TEMPORARY TABLE artistas (id_artista INT PRIMARY KEY, nombre_artistico VARCHAR(100), nombre VARCHAR(100), apellidos VARCHAR(100))');
$db->exec('CREATE TEMPORARY TABLE citas (id_cita INT PRIMARY KEY, id_cliente INT, id_artista INT, estado VARCHAR(30), fecha_hora_inicio DATETIME, id_cotizacion INT NULL)');
$db->exec('CREATE TEMPORARY TABLE cotizaciones (id_cotizacion INT PRIMARY KEY, id_cliente INT, estado VARCHAR(30))');
$db->exec('CREATE TEMPORARY TABLE calificaciones_artistas (id_calificacion INT AUTO_INCREMENT PRIMARY KEY, id_cita INT UNIQUE, puntuacion INT, comentario TEXT, estado_publicacion VARCHAR(30)) ENGINE=InnoDB');
$db->exec("INSERT INTO clientes VALUES (1,10),(2,20)");
$db->exec("INSERT INTO artistas VALUES (1,'Artista','Nombre','Apellido')");
$db->exec("INSERT INTO citas (id_cita,id_cliente,id_artista,estado,fecha_hora_inicio) VALUES (1,1,1,'finalizada','2026-01-01 12:00:00'),(2,1,1,'confirmada','2026-01-02 12:00:00'),(3,2,1,'finalizada','2026-01-03 12:00:00'),(4,1,1,'cancelada','2026-01-04 12:00:00')");
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
$db->exec("INSERT INTO cotizaciones VALUES (10,1,'aceptada'),(11,1,'enviada'),(12,2,'aceptada'),(13,1,'aceptada')");
$db->exec("INSERT INTO citas VALUES (5,1,1,'pendiente','2026-01-05 12:00:00',10),(6,1,1,'confirmada','2026-01-06 12:00:00',10),(7,1,1,'cancelada','2026-01-07 12:00:00',10),(8,1,1,'no_asistio','2026-01-08 12:00:00',10),(9,1,1,'confirmada','2026-01-09 12:00:00',11),(10,1,1,'confirmada','2026-01-10 12:00:00',12)");
ratingCheck(count($service->appointments())===3);
ratingCheck($service->appointments()[0]['id_calificacion']===null);
$service->submit(['id_cita'=>5,'puntuacion'=>4,'comentario'=>'Buena atención']);
$service->submit(['id_cita'=>6,'puntuacion'=>5]);
ratingReject(fn()=>$service->submit(['id_cita'=>5,'puntuacion'=>3]));
foreach ([7,8,9,10] as $invalidId) ratingReject(fn()=>$service->submit(['id_cita'=>$invalidId,'puntuacion'=>5]));
ratingCheck((int)$db->query('SELECT COUNT(*) FROM citas WHERE id_cotizacion=13')->fetchColumn()===0);
$db->exec("INSERT INTO citas VALUES (11,1,1,'pendiente','2026-01-11 12:00:00',13)");
ratingCheck(count($service->appointments())===4);
$db->exec("UPDATE cotizaciones SET estado='rechazada' WHERE id_cotizacion=13");
ratingReject(fn()=>$service->submit(['id_cita'=>11,'puntuacion'=>5]));
ratingCheck(count($service->appointments())===3);
echo "OK: $checks comprobaciones; solo tablas temporales.\n";
