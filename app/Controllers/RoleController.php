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
$clientStore=$requiredRole==='cliente' && in_array($_GET['section']??'',['tienda','carrito'],true);
$clientCart=$clientStore && ($_GET['section']??'')==='carrito';
$clientMemberships=$requiredRole==='cliente' && ($_GET['section']??'')==='membresias';
$clientAccount=$requiredRole==='cliente' && ($_GET['section']??'')==='cuenta';
$clientGiftCards=$requiredRole==='cliente' && ($_GET['section']??'')==='tarjetas';
if ($clientGiftCards) {
    $title='Mis tarjetas de regalo';$description='Consulta tus regalos y usa su saldo en la tienda.';
    require __DIR__.'/ClientGiftCardsController.php';
}
if ($clientAccount) {
    $title='Mi cuenta';$description='Actualiza tus datos y tu contraseña.';
    require __DIR__.'/ClientAccountController.php';
}
if ($clientMemberships) {
    require_once __DIR__.'/../Services/MembershipPlans.php';
    $membershipPlans=(new MembershipPlans(conectarBaseDatos()))->plans(true);
    $title='Membresías';
    $description='Compara las cuotas y los beneficios de nuestros planes.';
    require __DIR__.'/ClientMembershipController.php';
}
if ($clientStore) {
    require __DIR__.'/ClientCartController.php';
    $title='Tienda del estudio';
    $description='Descubre nuestros artículos y productos para cuidar tus tatuajes.';
    require __DIR__.'/ClientStoreController.php';
    if ($clientCart) { $title='Mi carrito';$description='Revisa tus artículos y elige cómo pagar. Retiro en el estudio.'; }
    if ($clientCart && ($_GET['paso']??'')==='pago') { $title='Finalizar compra';$description='Selecciona tu método de pago y confirma el pedido.'; }
    if ($clientCart && ($_GET['paso']??'')==='confirmacion') { $title='Confirmación de tu pedido';$description='Revisa el resultado de tu compra y las indicaciones para el retiro.'; }
}
