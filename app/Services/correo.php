<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../../vendor/autoload.php';

function enviarCorreoRecuperacion(
    string $email,
    string $codigo
): bool {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException(
            'El correo electrónico no es válido.'
        );
    }

    if (!preg_match('/^[0-9]{6}$/D', $codigo)) {
        throw new InvalidArgumentException(
            'El código de recuperación debe tener 6 dígitos.'
        );
    }

    $config = require __DIR__ . '/../../config/smtp.php';

    $password = preg_replace(
        '/\s+/',
        '',
        (string) ($config['password'] ?? '')
    );
    $username = trim((string) ($config['usuario'] ?? ''));
    $from = trim((string) ($config['remitente'] ?? $username));

    if (
        $username === '' ||
        $from === '' ||
        $password === '' ||
        $password === 'PEGA_AQUI_TU_NUEVA_CLAVE_DE_APLICACION'
    ) {
        throw new RuntimeException(
            'Configura SMTP_USER y SMTP_PASSWORD en el entorno del servidor.'
        );
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = (string) $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = strtolower((string) $config['encryption']) === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) $config['port'];
        $mail->CharSet = 'UTF-8';

        $mail->Timeout = 10;
        $mail->getSMTPInstance()->Timelimit = 10;

        $mail->setFrom(
            $from,
            (string) $config['nombre_remitente']
        );

        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject =
            'Tu código de recuperación · Tinta Viva';

        $mail->Body = <<<HTML
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña</title>
</head>
<body style="margin:0; padding:0; background-color:#090909;
             font-family:Arial,Helvetica,sans-serif; color:#eeeeee;">

    <table role="presentation" width="100%" cellspacing="0"
           cellpadding="0" border="0"
           style="background-color:#090909;">
        <tr>
            <td align="center" style="padding:32px 12px;">

                <table role="presentation" width="100%" cellspacing="0"
                       cellpadding="0" border="0"
                       style="max-width:560px; background-color:#161616;
                              border:1px solid #393025;">

                    <tr>
                        <td style="height:5px; background-color:#990f32;
                                   font-size:0; line-height:0;">
                            &nbsp;
                        </td>
                    </tr>

                    <tr>
                        <td align="center"
                            style="padding:32px 24px 26px;
                                   background-color:#101010;
                                   border-bottom:1px solid #393025;">

                            <p style="margin:0; color:#d4af37;
                                      font-size:28px; font-weight:bold;
                                      letter-spacing:3px;">
                                TINTA VIVA
                            </p>

                            <p style="margin:10px 0 0; color:#aaaaaa;
                                      font-size:11px; letter-spacing:2px;">
                                ARTE QUE DEJA HUELLA
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 28px;">
                            <p style="margin:0 0 12px; color:#d4af37;
                                      font-size:11px; font-weight:bold;
                                      letter-spacing:2px;">
                                RECUPERACIÓN DE CUENTA
                            </p>

                            <h1 style="margin:0 0 20px; color:#ffffff;
                                       font-size:25px; line-height:1.3;">
                                Recupera tu acceso
                            </h1>

                            <p style="margin:0 0 16px; color:#cccccc;
                                      font-size:15px; line-height:1.7;">
                                Hola,
                            </p>

                            <p style="margin:0 0 24px; color:#cccccc;
                                      font-size:15px; line-height:1.7;">
                                Recibimos una solicitud para cambiar la
                                contraseña de tu cuenta en
                                <strong style="color:#d4af37;">
                                    Tinta Viva
                                </strong>.
                                Ingresa este código en la página de recuperación
                                para continuar.
                            </p>

                            <table role="presentation" width="100%"
                                   cellspacing="0" cellpadding="0" border="0"
                                   style="background-color:#0b0b0b;
                                          border:1px solid #d4af37;">
                                <tr>
                                    <td align="center"
                                        style="padding:22px 12px;">
                                        <p style="margin:0 0 12px;
                                                  color:#aaaaaa;
                                                  font-size:11px;
                                                  letter-spacing:2px;">
                                            TU CÓDIGO DE SEGURIDAD
                                        </p>

                                        <p style="margin:0; color:#d4af37;
                                                  font-family:Consolas,monospace;
                                                  font-size:36px;
                                                  font-weight:bold;
                                                  letter-spacing:6px;">
                                            {$codigo}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:18px 0 24px; color:#bbbbbb;
                                      font-size:13px; line-height:1.6;
                                      text-align:center;">
                                Válido durante <strong>30 minutos</strong>.
                                Solo puede utilizarse una vez.
                            </p>

                            <table role="presentation" width="100%"
                                   cellspacing="0" cellpadding="0" border="0"
                                   style="background-color:#251017;
                                          border-left:4px solid #990f32;">
                                <tr>
                                    <td style="padding:15px 16px;">
                                        <p style="margin:0; color:#f0d4dc;
                                                  font-size:13px;
                                                  line-height:1.7;">
                                            <strong>No compartas este código.</strong>
                                            Si no solicitaste el cambio,
                                            puedes ignorar este correo.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%"
                                   cellspacing="0" cellpadding="0" border="0"
                                   style="margin-top:30px;
                                          border-top:1px solid #393025;">
                                <tr>
                                    <td style="padding-top:22px;">
                                        <p style="margin:0 0 8px; color:#cccccc;
                                                  font-size:14px;">
                                            Con dedicación,
                                        </p>

                                        <p style="margin:0; color:#d4af37;
                                                  font-size:18px;
                                                  font-weight:bold;
                                                  letter-spacing:1px;">
                                            Equipo Tinta Viva
                                        </p>

                                        <p style="margin:7px 0 0; color:#aaaaaa;
                                                  font-size:12px;
                                                  line-height:1.6;">
                                            Arte, identidad y pasión en cada trazo.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center"
                            style="padding:20px 24px;
                                   background-color:#101010;
                                   border-top:1px solid #393025;">
                            <p style="margin:0; color:#888888;
                                      font-size:11px; line-height:1.7;">
                                Este correo se generó automáticamente
                                para recuperar el acceso a tu cuenta.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $mail->AltBody =
            "TINTA VIVA\n"
            . "Arte que deja huella\n\n"
            . "Recupera tu acceso\n\n"
            . "Recibimos una solicitud para cambiar "
            . "la contraseña de tu cuenta.\n\n"
            . "Tu código de seguridad: {$codigo}\n\n"
            . "Ingresa el código en la página de recuperación.\n"
            . "Válido durante 30 minutos y para un solo uso.\n\n"
            . "No compartas este código. "
            . "Si no solicitaste el cambio, ignora este correo.\n\n"
            . "Con dedicación,\n"
            . "Equipo Tinta Viva\n"
            . "Arte, identidad y pasión en cada trazo.";

        return $mail->send();
    } catch (Throwable $ex) {
        error_log(
            'SMTP recuperación: ' . $ex->getMessage()
        );

        throw new RuntimeException(
            'Error al enviar correo: ' . $ex->getMessage(),
            0,
            $ex
        );
    }
}
