<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
$db=conectarBaseDatos();
$db->exec(file_get_contents(__DIR__.'/../database/membresias_configuracion.sql'));
$db->beginTransaction();
try {
    $defaults=[
        'esencial'=>['Esencial',['prioridad'=>1,'mercancia_porcentaje'=>5]],
        'plus'=>['Plus',['prioridad'=>1,'retoques'=>1,'mercancia_porcentaje'=>10,'hidratacion'=>1]],
        'premium'=>['Premium',['prioridad'=>1,'retoques'=>2,'mercancia_porcentaje'=>15,'hidratacion'=>2,'color'=>1,'brillo'=>1,'kit_regalo'=>1]],
    ];
    foreach ($defaults as $level=>[$name,$rules]) {
        $q=$db->prepare('SELECT id_plan FROM membresias_configuracion WHERE nivel=?'); $q->execute([$level]);
        if ($q->fetchColumn()) continue;
        $name='Tinta Viva · '.$name;
        $q=$db->prepare('SELECT id_plan FROM planes_membresia WHERE nombre=?'); $q->execute([$name]);
        if ($q->fetchColumn()) throw new RuntimeException('Ya existe un plan con ese nombre; revisa antes de instalar.');
        $db->prepare('INSERT INTO planes_membresia (nombre,descripcion,precio,duracion_dias,activo) VALUES (?, ?,0,30,0)')->execute([$name,'Configura las cuotas y los beneficios antes de activar.']);
        $id=(int)$db->lastInsertId();
        $db->prepare('INSERT INTO membresias_configuracion (id_plan,nivel) VALUES (?,?)')->execute([$id,$level]);
        $q=$db->prepare('INSERT INTO membresias_reglas (id_plan,codigo,valor) VALUES (?,?,?)');
        foreach ($rules as $code=>$value) $q->execute([$id,$code,$value]);
    }
    $db->commit();
} catch (Throwable $ex) { $db->rollBack(); throw $ex; }
echo "Tres tipos de membresía instalados. Configura cuotas antes de activarlos. Datos existentes conservados.\n";
