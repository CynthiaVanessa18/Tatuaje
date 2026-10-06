<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') exit;
require __DIR__.'/../app/Core/bootstrap.php';
require __DIR__.'/../app/Models/CrudRepository.php';
require __DIR__.'/../app/Services/CrudService.php';
require __DIR__.'/../app/Services/GiftCardEmail.php';
require __DIR__.'/temporary-store-promotions.php';
$db=conectarBaseDatos();foreach (['tarjetas_regalo','movimientos_tarjetas_regalo','tarjetas_regalo_clientes'] as $table) temporaryStoreTable($db,$table);
$modules=require __DIR__.'/../config/modules.php';$repo=new CrudRepository($db,$modules['tarjetas']);
$input=['id_cliente_comprador'=>'1','nombre_destinatario'=>'Prueba','correo_destinatario'=>'test@example.com','mensaje'=>'Mensaje privado','monto_inicial'=>'5000','moneda'=>'CRC','fecha_vencimiento'=>'','estado'=>'pendiente'];
$sent=[];$sender=static function(string $email,string $code) use (&$sent,$db):bool {
    if ($db->inTransaction()) throw new RuntimeException('Envía antes de confirmar la creación');
    $sent[]=[$email,$code];return true;
};
$service=new CrudService($repo,$sender);$notice=$service->execute('create',$input,null);
$card=$db->query('SELECT * FROM tarjetas_regalo')->fetch();
if(count($sent)!==1 || $sent[0][0]!==$input['correo_destinatario'] || hash('sha256',str_replace('-','',$sent[0][1]),true)!==$card['codigo_hash'] || !str_contains($notice,'enviados')) throw new RuntimeException('Aviso o código incorrectos');
$mail=GiftCardEmail::message($sent[0][0],$sent[0][1],['usuario'=>'studio@example.com','password'=>'test']);
if (!str_contains($mail->Body,$sent[0][1]) || !str_contains($mail->Subject,'tarjeta de regalo') || str_contains($mail->Body,'5000') || str_contains($mail->Body,'Mensaje privado')) throw new RuntimeException('El correo debe contener solo aviso y código');
$service->execute('update',$input,['id_tarjeta'=>$card['id_tarjeta']]);if(count($sent)!==1) throw new RuntimeException('Editar no debe reenviar');
$notice=(new CrudService($repo,static fn()=>false))->execute('create',$input,null);
if(!str_contains($notice,'No se pudo enviar') || (int)$db->query('SELECT COUNT(*) FROM tarjetas_regalo')->fetchColumn()!==2) throw new RuntimeException('Fallo de correo debe conservar tarjeta y código');
echo "OK: aviso y código correctos, envío después de guardar, sin reenvío al editar y conservación si falla. Sin correos reales; tablas temporales.\n";
