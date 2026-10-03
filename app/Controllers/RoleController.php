<?php
declare(strict_types=1);

$account = requireRole($requiredRole);
$descriptions = [
    'secretaria' => ['Recepción', 'Consulta las próximas citas del estudio.'],
    'artista' => ['Mi agenda', 'Consulta las citas asignadas a tu cuenta de artista.'],
    'cliente' => ['Mis citas', 'Consulta las citas vinculadas a tu cuenta de cliente.'],
];
[$title, $description] = $descriptions[$requiredRole];
$parameters = [];
$where = '';
$profileMissing = false;

if ($requiredRole === 'artista' || $requiredRole === 'cliente') {
    $profileTable = $requiredRole === 'artista' ? 'artistas' : 'clientes';
    $profileKey = $requiredRole === 'artista' ? 'id_artista' : 'id_cliente';
    $query = conectarBaseDatos()->prepare("SELECT $profileKey FROM $profileTable WHERE id_cuenta = ?");
    $query->execute([$account['id_cuenta']]);
    $profileId = $query->fetchColumn();
    $profileMissing = $profileId === false;
    $where = " AND c.$profileKey = ?";
    $parameters[] = $profileId === false ? 0 : $profileId;
}

$query = conectarBaseDatos()->prepare(
    "SELECT c.fecha_hora_inicio, c.fecha_hora_fin, c.estado,
            CONCAT(cl.nombre, ' ', cl.apellidos) AS cliente,
            COALESCE(NULLIF(a.nombre_artistico, ''), CONCAT(a.nombre, ' ', a.apellidos)) AS artista
     FROM citas c
     JOIN clientes cl ON cl.id_cliente = c.id_cliente
     JOIN artistas a ON a.id_artista = c.id_artista
     WHERE c.fecha_hora_fin >= UTC_TIMESTAMP()
       AND c.estado NOT IN ('cancelada', 'no_asistio') $where
     ORDER BY c.fecha_hora_inicio LIMIT 50"
);
$query->execute($parameters);
$appointments = $query->fetchAll();
