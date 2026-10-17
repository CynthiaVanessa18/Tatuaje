<?php
declare(strict_types=1);
use PHPMailer\PHPMailer\PHPMailer;
require_once __DIR__.'/../../vendor/autoload.php';

final class GiftCardEmail
{
    public static function message(string $email,string $code,array $config): PHPMailer
    {
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}$/D',$code)) throw new InvalidArgumentException('Destinatario o código de regalo inválido.');
        // Mismas credenciales y normalización que enviarCorreoRecuperacion().
        $username=trim((string)($config['usuario']??''));
        $password=preg_replace('/\s+/','',(string)($config['password']??''));
        $from=trim((string)($config['remitente']??$username));
        if ($username==='' || $from==='' || $password==='' || $password==='PEGA_AQUI_TU_NUEVA_CLAVE_DE_APLICACION') {
            throw new RuntimeException('Configura SMTP_USER y SMTP_PASSWORD en el entorno del servidor.');
        }
        $mail=new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host=(string)($config['host']??'smtp.gmail.com');
        $mail->Port=(int)($config['port']??587);
        $mail->SMTPAuth=true;
        $mail->Username=$username;
        $mail->Password=$password;
        $mail->SMTPSecure=strtolower((string)($config['encryption']??'tls'))==='ssl'
            ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet='UTF-8';
        $mail->Timeout=10;
        $mail->getSMTPInstance()->Timelimit=10;
        $mail->setFrom($from,(string)($config['nombre_remitente']??'Tinta Viva'));
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject='Recibiste una tarjeta de regalo · Tinta Viva';
        $mail->Body='<!doctype html><html lang="es"><head><meta charset="utf-8"></head>'
            .'<body style="background:#f5f3f0;font-family:Arial,sans-serif;color:#262626;padding:24px">'
            .'<div style="max-width:560px;margin:auto;background:#fff;padding:32px;border-radius:12px">'
            .'<p style="font-weight:bold;letter-spacing:3px">TINTA VIVA</p>'
            .'<h1>¡Recibiste una tarjeta de regalo!</h1><p>Tu código de regalo es:</p>'
            .'<p style="font-size:26px;font-weight:bold;letter-spacing:3px">'.$code.'</p>'
            .'<p>Inicia sesión y entra a <strong>Tienda → Mis tarjetas de regalo</strong>. '
            .'Añade este código para consultar tu tarjeta y usar su saldo al comprar.</p>'
            .'<p>Conserva este código y compártelo únicamente con la persona que usará el regalo.</p>'
            .'<p>Equipo Tinta Viva</p></div></body></html>';
        $mail->AltBody="¡Recibiste una tarjeta de regalo de Tinta Viva!\n\nTu código es: $code\n\nInicia sesión y añádela en Tienda → Mis tarjetas de regalo para usar su saldo al comprar.\n\nEquipo Tinta Viva";
        return $mail;
    }
    public static function send(string $email,string $code): bool
    {
        $config=require __DIR__.'/../../config/smtp.php';
        try { return self::message($email,$code,$config)->send(); }
        catch (Throwable $ex) { error_log('SMTP tarjeta de regalo: '.$ex->getMessage());return false; }
    }
}
