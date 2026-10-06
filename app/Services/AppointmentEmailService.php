<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../../vendor/autoload.php';

final class AppointmentEmailService
{
    private array $config;

    public function __construct(private AppointmentRepository $repository)
    {
        $this->config = require __DIR__ . '/../../config/smtp.php';
    }

    public function compose(array $appointment): array
    {
        $start = new DateTimeImmutable(
            (string) $appointment['fecha_hora_inicio'],
            new DateTimeZone('America/Costa_Rica')
        );
        $end = new DateTimeImmutable(
            (string) $appointment['fecha_hora_fin'],
            new DateTimeZone('America/Costa_Rica')
        );
        $escape = static fn (mixed $value): string => htmlspecialchars(
            is_scalar($value) ? (string) $value : '',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $portalUrl = $this->config['app_url'] . '/panel/cliente.php';
        $address = trim((string) ($this->config['direccion_estudio'] ?? ''));
        $phone = trim((string) ($this->config['telefono_estudio'] ?? ''));
        $notes = trim((string) ($appointment['observaciones'] ?? ''));
        $subject = 'Cita confirmada · Tinta Viva · ' . $start->format('d/m/Y H:i');
        $days = [
            1 => 'LUNES',
            2 => 'MARTES',
            3 => 'MIÉRCOLES',
            4 => 'JUEVES',
            5 => 'VIERNES',
            6 => 'SÁBADO',
            7 => 'DOMINGO',
        ];
        $months = [
            1 => 'ENE',
            2 => 'FEB',
            3 => 'MAR',
            4 => 'ABR',
            5 => 'MAY',
            6 => 'JUN',
            7 => 'JUL',
            8 => 'AGO',
            9 => 'SEP',
            10 => 'OCT',
            11 => 'NOV',
            12 => 'DIC',
        ];
        $clientName = $escape(strtoupper((string) $appointment['cliente']));
        $artistName = $escape($appointment['artista']);
        $projectName = $escape($appointment['categoria']) . ' · ' . $escape($appointment['zona_cuerpo']);
        $sessionNumber = $escape($appointment['numero_sesion']);
        $dateDisplay = $escape(
            $days[(int) $start->format('N')]
            . ' · ' . $start->format('d')
            . ' ' . $months[(int) $start->format('n')]
            . ' ' . $start->format('Y')
        );
        $startTime = $escape($start->format('H:i'));
        $endTime = $escape($end->format('H:i'));
        $portalUrlEscaped = $escape($portalUrl);
        $subjectEscaped = $escape($subject);
        $addressRow = $address === '' ? '' : '<tr><td style="padding:13px 0;border-bottom:1px solid #35252a;color:#8f8185;font-size:11px;letter-spacing:1.5px;text-transform:uppercase">Lugar</td><td style="padding:13px 0;border-bottom:1px solid #35252a;color:#f2e8dc;font-family:Georgia,serif;font-size:16px;text-align:right">' . $escape($address) . '</td></tr>';
        $phoneRow = $phone === '' ? '' : '<tr><td style="padding:13px 0;border-bottom:1px solid #35252a;color:#8f8185;font-size:11px;letter-spacing:1.5px;text-transform:uppercase">Teléfono</td><td style="padding:13px 0;border-bottom:1px solid #35252a;color:#f2e8dc;font-family:Georgia,serif;font-size:16px;text-align:right">' . $escape($phone) . '</td></tr>';
        $notesBlock = $notes === '' ? '' : '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;border:1px solid #5c2634;background:#16090d"><tr><td width="64" align="center" style="padding:20px 8px;border-right:1px solid #5c2634;color:#b01d42;font-family:Georgia,serif;font-size:31px;letter-spacing:-8px">╱╱╱</td><td style="padding:20px"><p style="margin:0;color:#d3a552;font-size:10px;font-weight:bold;letter-spacing:2.5px;text-transform:uppercase">Indicaciones antes del ritual</p><p style="margin:9px 0 0;color:#d8cbc4;font-size:14px;line-height:1.7">' . nl2br($escape($notes)) . '</p></td></tr></table>';

        $html = <<<HTML
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{$subjectEscaped}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell { padding: 14px 6px !important; }
            .email-panel { width: 100% !important; max-width: 100% !important; table-layout: fixed !important; }
            .email-panel img { width: 100% !important; max-width: 100% !important; height: auto !important; }
            .email-pad { padding-left: 20px !important; padding-right: 20px !important; }
            .date-column, .time-column { display: block !important; width: 100% !important; text-align: center !important; }
            .date-column { border-right: 0 !important; border-bottom: 1px solid #5c3a2b !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#020203;color:#f2e8dc;font-family:Arial,Helvetica,sans-serif">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0">La noche ha sido marcada: tu cita en Tinta Viva está confirmada.</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#020203">
        <tr>
            <td class="email-shell" align="center" style="padding:38px 12px">
                <table class="email-panel" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:680px;table-layout:fixed;overflow-wrap:anywhere;border:1px solid #5c3a2b;background:#0b0709;box-shadow:0 24px 80px #000000">
                    <tr><td style="height:6px;background:#8f1230;font-size:0;line-height:0">&nbsp;</td></tr>
                    <tr>
                        <td class="email-pad" align="center" style="padding:34px 36px 10px;background:#080507">
                            <p style="margin:0;color:#d3a552;font-size:10px;font-weight:bold;letter-spacing:5px;text-transform:uppercase">T I N T A&nbsp;&nbsp; V I V A</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin:25px auto 20px;border:1px solid #6f2f26;background:#050304">
                                <tr>
                                    <td align="center" style="padding:0;background:#050304;font-size:0;line-height:0">
                                        <img src="cid:tinta-viva-werewolf" width="100%" alt="Hombre lobo monstruoso bajo una luna roja" style="display:block;width:100%;max-width:606px;height:auto;border:0;outline:none;text-decoration:none">
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;color:#a9183b;font-family:Georgia,serif;font-size:28px;letter-spacing:6px;line-height:1">╱ ╱ ╱</p>
                            <p style="margin:18px 0 0;color:#c9954d;font-size:10px;font-weight:bold;letter-spacing:3px;text-transform:uppercase">Ritual de la luna roja</p>
                            <h1 style="margin:10px 0 0;color:#f5e9dc;font-family:Georgia,'Times New Roman',serif;font-size:38px;font-weight:normal;line-height:1.15">La noche ha sido marcada</h1>
                            <p style="margin:11px 0 0;color:#b8a9a3;font-family:Georgia,'Times New Roman',serif;font-size:18px;font-style:italic">Tu cita está confirmada</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:22px 36px 30px;background:#080507">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td style="height:1px;background:#4b2d24"></td><td width="38" align="center" style="color:#b4183c;font-size:13px">◆</td><td style="height:1px;background:#4b2d24"></td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-pad" style="padding:0 36px 34px">
                            <p style="margin:0;color:#d3a552;font-size:10px;font-weight:bold;letter-spacing:2.5px;text-transform:uppercase">A la atención de {$clientName}</p>
                            <p style="margin:10px 0 24px;color:#cbbdb6;font-family:Georgia,'Times New Roman',serif;font-size:17px;line-height:1.7">El estudio selló definitivamente tu próxima sesión. La fecha ya forma parte de nuestro archivo nocturno.</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #5c3a2b;background:#120b0e">
                                <tr>
                                    <td class="date-column" width="58%" valign="middle" style="padding:24px;border-right:1px solid #5c3a2b">
                                        <p style="margin:0;color:#9f8586;font-size:9px;font-weight:bold;letter-spacing:2.4px;text-transform:uppercase">Bajo la luna de</p>
                                        <p style="margin:8px 0 0;color:#f2e8dc;font-family:Georgia,'Times New Roman',serif;font-size:22px;line-height:1.35">{$dateDisplay}</p>
                                    </td>
                                    <td class="time-column" width="42%" align="center" valign="middle" style="padding:24px">
                                        <p style="margin:0;color:#9f8586;font-size:9px;font-weight:bold;letter-spacing:2.4px;text-transform:uppercase">La hora señalada</p>
                                        <p style="margin:8px 0 0;color:#d3a552;font-family:Georgia,'Times New Roman',serif;font-size:27px;line-height:1">{$startTime}–{$endTime}</p>
                                        <p style="margin:7px 0 0;color:#7f7173;font-size:9px;letter-spacing:1.5px;text-transform:uppercase">Costa Rica</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:24px">
                                <tr><td style="padding:13px 0;border-top:1px solid #35252a;border-bottom:1px solid #35252a;color:#8f8185;font-size:11px;letter-spacing:1.5px;text-transform:uppercase">Artista</td><td style="padding:13px 0;border-top:1px solid #35252a;border-bottom:1px solid #35252a;color:#f2e8dc;font-family:Georgia,serif;font-size:16px;text-align:right">{$artistName}</td></tr>
                                <tr><td style="padding:13px 0;border-bottom:1px solid #35252a;color:#8f8185;font-size:11px;letter-spacing:1.5px;text-transform:uppercase">Marca elegida</td><td style="padding:13px 0;border-bottom:1px solid #35252a;color:#f2e8dc;font-family:Georgia,serif;font-size:16px;text-align:right">{$projectName}</td></tr>
                                <tr><td style="padding:13px 0;border-bottom:1px solid #35252a;color:#8f8185;font-size:11px;letter-spacing:1.5px;text-transform:uppercase">Sesión</td><td style="padding:13px 0;border-bottom:1px solid #35252a;color:#f2e8dc;font-family:Georgia,serif;font-size:16px;text-align:right">#{$sessionNumber}</td></tr>
                                {$addressRow}
                                {$phoneRow}
                            </table>

                            {$notesBlock}

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:30px">
                                <tr><td align="center"><a href="{$portalUrlEscaped}" style="display:inline-block;padding:15px 28px;border:1px solid #c3924d;background:#7d102b;color:#fff4e8;text-decoration:none;font-size:10px;font-weight:bold;letter-spacing:2.8px;text-transform:uppercase">Entrar a mi archivo&nbsp; →</a></td></tr>
                            </table>
                            <p style="margin:24px auto 0;max-width:440px;color:#75696b;font-size:10px;line-height:1.7;text-align:center">Si no reconoces esta cita, comunícate directamente con el estudio. Este mensaje fue generado cuando el administrador confirmó la sesión.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:24px;border-top:1px solid #3b2425;background:#060405">
                            <p style="margin:0;color:#a9183b;font-family:Georgia,serif;font-size:19px;letter-spacing:3px">╱ ╱ ╱</p>
                            <p style="margin:12px 0 0;color:#d3a552;font-size:10px;font-weight:bold;letter-spacing:4px;text-transform:uppercase">Tinta Viva</p>
                            <p style="margin:7px 0 0;color:#6f6264;font-family:Georgia,serif;font-size:12px;font-style:italic">Arte nacido en la sombra. Marcas hechas para permanecer.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        return [
            'subject' => $subject,
            'html' => $html,
            'data' => [
                'tipo' => 'confirmacion_cita',
                'id_cita' => (int) $appointment['id_cita'],
                'cliente' => (string) $appointment['cliente'],
                'artista' => (string) $appointment['artista'],
                'fecha' => $start->format('d/m/Y'),
                'hora_inicio' => $start->format('H:i'),
                'hora_fin' => $end->format('H:i'),
                'portal_url' => $portalUrl,
            ],
        ];
    }

    public function sendQueued(int $mailId): bool
    {
        $queued = $this->repository->claimEmail($mailId);

        if ($queued === null) {
            return false;
        }

        try {
            $username = trim((string) ($this->config['usuario'] ?? ''));
            $password = preg_replace('/\s+/', '', (string) ($this->config['password'] ?? ''));
            $from = trim((string) ($this->config['remitente'] ?? $username));

            if ($username === '' || $password === '' || $from === '') {
                throw new RuntimeException('SMTP no está configurado. Define SMTP_USER y SMTP_PASSWORD antes de confirmar citas.');
            }

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = (string) $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->Port = (int) $this->config['port'];
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 15;
            $mail->getSMTPInstance()->Timelimit = 15;

            $encryption = strtolower((string) ($this->config['encryption'] ?? 'tls'));
            $mail->SMTPSecure = $encryption === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom($from, (string) $this->config['nombre_remitente']);
            $mail->addAddress((string) $queued['destinatario']);
            $mail->isHTML(true);
            $mail->Subject = (string) $queued['asunto'];
            $mail->Body = (string) $queued['contenido'];

            if (str_contains($mail->Body, 'cid:tinta-viva-werewolf')) {
                $werewolfImage = __DIR__ . '/../../public/assets/images/email/hombre-lobo-luna-roja.png';

                if (!is_file($werewolfImage)) {
                    throw new RuntimeException('No se encontró la imagen del hombre lobo usada por el correo de confirmación.');
                }

                $mail->addEmbeddedImage(
                    $werewolfImage,
                    'tinta-viva-werewolf',
                    'hombre-lobo-luna-roja.png',
                    PHPMailer::ENCODING_BASE64,
                    'image/png'
                );
            }

            $data = json_decode((string) $queued['datos_plantilla'], true);
            $mail->AltBody = is_array($data)
                ? "TINTA VIVA\n\nTu cita está confirmada.\nFecha: {$data['fecha']}\nHora: {$data['hora_inicio']}–{$data['hora_fin']} (Costa Rica)\nArtista: {$data['artista']}\n\nConsulta tu cuenta: {$data['portal_url']}"
                : 'Tu cita en Tinta Viva está confirmada. Ingresa a tu cuenta para consultar los detalles.';

            $mail->send();
            $this->repository->markEmailSent($mailId);

            return true;
        } catch (Throwable $exception) {
            $this->repository->markEmailFailed($mailId, $exception->getMessage());
            error_log('Correo de cita #' . $mailId . ': ' . $exception->getMessage());

            throw new RuntimeException('La cita quedó confirmada, pero el correo no pudo enviarse: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
