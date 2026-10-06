<?php
declare(strict_types=1);
use PHPMailer\PHPMailer\PHPMailer;
require_once __DIR__.'/../../vendor/autoload.php';

final class GiftCardEmail
{
    public static function message(string $email,string $code,array $config): PHPMailer
    {
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}$/D',$code)) throw new InvalidArgumentException('Destinatario o código de regalo inválido.');
        $mail=new PHPMailer(true);$mail->isSMTP();$mail->Host='smtp.gmail.com';$mail->Port=587;
        $mail->SMTPAuth=true;$mail->Username=$config['usuario'];$mail->Password=$config['password'];
        $mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS;$mail->CharSet='UTF-8';$mail->Timeout=10;$mail->getSMTPInstance()->Timelimit=10;
        $mail->setFrom($config['usuario'],'Tinta Viva');$mail->addAddress($email);$mail->isHTML(false);
        $mail->Subject='Recibiste una tarjeta de regalo · Tinta Viva';
        $mail->Body="¡Recibiste una tarjeta de regalo de Tinta Viva!\n\nTu código es: $code\n\nPuedes añadirla en Tienda → Mis tarjetas de regalo.\n\nTinta Viva";
        return $mail;
    }
    public static function send(string $email,string $code): bool
    {
        $config=require __DIR__.'/../../config/smtp.php';
        if (empty($config['usuario']) || empty($config['password'])) return false;
        try { return self::message($email,$code,$config)->send(); }
        catch (Throwable $ex) { error_log('No se pudo enviar el aviso de tarjeta de regalo.');return false; }
    }
}
