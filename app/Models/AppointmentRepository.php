<?php
declare(strict_types=1);

final class AppointmentRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function forQuote(int $quoteId): array
    {
        $query = $this->database->prepare(
            "SELECT
                appointment.id_cita,
                appointment.fecha_hora_inicio,
                appointment.fecha_hora_fin,
                appointment.numero_sesion,
                appointment.estado,
                appointment.observaciones,
                COALESCE(
                    NULLIF(artist.nombre_artistico, ''),
                    CONCAT_WS(' ', artist.nombre, artist.apellidos)
                ) AS artista,
                mail.id_correo,
                mail.estado AS estado_correo,
                mail.intentos AS intentos_correo,
                mail.ultimo_error AS error_correo,
                mail.fecha_envio
             FROM citas appointment
             INNER JOIN artistas artist
                ON artist.id_artista = appointment.id_artista
             LEFT JOIN correos_salida mail
                ON mail.clave_evento = CONCAT('cita_confirmada:', appointment.id_cita)
             WHERE appointment.id_cotizacion = :quote_id
             ORDER BY appointment.numero_sesion, appointment.fecha_hora_inicio"
        );
        $query->execute(['quote_id' => $quoteId]);

        return $query->fetchAll();
    }

    public function weeklyScheduleForArtist(int $artistId): array
    {
        $query = $this->database->prepare(
            'SELECT dia_semana, hora_inicio, hora_fin
             FROM horarios_artistas
             WHERE id_artista = :artist_id
               AND activo = TRUE
             ORDER BY dia_semana, hora_inicio'
        );
        $query->execute(['artist_id' => $artistId]);

        return $query->fetchAll();
    }

    public function unavailablePeriodsForArtist(int $artistId, string $from, string $to): array
    {
        $query = $this->database->prepare(
            "SELECT
                'bloqueo' AS tipo,
                block.fecha_hora_inicio,
                block.fecha_hora_fin,
                COALESCE(NULLIF(block.motivo, ''), 'Bloqueo de agenda') AS descripcion
             FROM bloqueos_agenda block
             WHERE block.id_artista = :block_artist_id
               AND block.activo = TRUE
               AND block.fecha_hora_inicio < :block_to
               AND block.fecha_hora_fin > :block_from

             UNION ALL

             SELECT
                'cita' AS tipo,
                appointment.fecha_hora_inicio,
                appointment.fecha_hora_fin,
                CONCAT('Cita #', appointment.id_cita) AS descripcion
             FROM citas appointment
             WHERE appointment.id_artista = :appointment_artist_id
               AND appointment.estado NOT IN ('cancelada', 'no_asistio')
               AND appointment.fecha_hora_inicio < :appointment_to
               AND appointment.fecha_hora_fin > :appointment_from

             ORDER BY fecha_hora_inicio"
        );
        $query->execute([
            'block_artist_id' => $artistId,
            'block_from' => $from,
            'block_to' => $to,
            'appointment_artist_id' => $artistId,
            'appointment_from' => $from,
            'appointment_to' => $to,
        ]);

        return $query->fetchAll();
    }

    public function findForAdmin(int $appointmentId): ?array
    {
        $query = $this->database->prepare(
            "SELECT
                appointment.*,
                quote.estado AS estado_cotizacion,
                quote.precio_cotizado,
                quote.anticipo_requerido,
                quote.duracion_estimada_minutos,
                quote.zona_cuerpo,
                category.nombre AS categoria,
                CONCAT_WS(' ', client.nombre, client.apellidos) AS cliente,
                account.id_cuenta AS id_cuenta_cliente,
                account.correo AS correo_cliente,
                COALESCE(
                    NULLIF(artist.nombre_artistico, ''),
                    CONCAT_WS(' ', artist.nombre, artist.apellidos)
                ) AS artista,
                mail.id_correo,
                mail.estado AS estado_correo,
                mail.intentos AS intentos_correo,
                mail.ultimo_error AS error_correo,
                mail.fecha_envio
             FROM citas appointment
             INNER JOIN cotizaciones quote
                ON quote.id_cotizacion = appointment.id_cotizacion
             INNER JOIN categorias_tatuajes category
                ON category.id_categoria = quote.id_categoria
             INNER JOIN clientes client
                ON client.id_cliente = appointment.id_cliente
             INNER JOIN cuentas account
                ON account.id_cuenta = client.id_cuenta
             INNER JOIN artistas artist
                ON artist.id_artista = appointment.id_artista
             LEFT JOIN correos_salida mail
                ON mail.clave_evento = CONCAT('cita_confirmada:', appointment.id_cita)
             WHERE appointment.id_cita = :appointment_id
             LIMIT 1"
        );
        $query->execute(['appointment_id' => $appointmentId]);
        $appointment = $query->fetch();

        return $appointment === false ? null : $appointment;
    }

    public function scheduleFromQuote(
        int $quoteId,
        int $accountId,
        string $start,
        string $end,
        int $sessionNumber,
        ?string $notes
    ): int {
        $this->database->beginTransaction();

        try {
            $quoteQuery = $this->database->prepare(
                'SELECT id_cliente, id_artista, estado, sesiones_estimadas
                 FROM cotizaciones
                 WHERE id_cotizacion = :quote_id
                 FOR UPDATE'
            );
            $quoteQuery->execute(['quote_id' => $quoteId]);
            $quote = $quoteQuery->fetch();

            if ($quote === false) {
                throw new DomainException('La cotización ya no existe.');
            }

            if ($quote['estado'] !== 'aceptada') {
                throw new DomainException('Solo se pueden agendar cotizaciones aceptadas por el cliente.');
            }

            if ($quote['id_cliente'] === null || $quote['id_artista'] === null) {
                throw new DomainException('La cotización debe tener cliente y artista asignados antes de agendar.');
            }

            if ($quote['sesiones_estimadas'] !== null && $sessionNumber > (int) $quote['sesiones_estimadas']) {
                throw new DomainException('El número de sesión supera las sesiones estimadas en la cotización.');
            }

            $duplicate = $this->database->prepare(
                "SELECT COUNT(*)
                 FROM citas
                 WHERE id_cotizacion = :quote_id
                   AND numero_sesion = :session_number
                   AND estado NOT IN ('cancelada', 'no_asistio')"
            );
            $duplicate->execute([
                'quote_id' => $quoteId,
                'session_number' => $sessionNumber,
            ]);

            if ((int) $duplicate->fetchColumn() > 0) {
                throw new DomainException('Ya existe una cita activa para ese número de sesión.');
            }

            $insert = $this->database->prepare(
                "INSERT INTO citas (
                    id_cliente,
                    id_artista,
                    id_cotizacion,
                    fecha_hora_inicio,
                    fecha_hora_fin,
                    numero_sesion,
                    origen,
                    estado,
                    observaciones
                 ) VALUES (
                    :client_id,
                    :artist_id,
                    :quote_id,
                    :starts_at,
                    :ends_at,
                    :session_number,
                    'administracion',
                    'pendiente',
                    :notes
                 )"
            );
            $insert->execute([
                'client_id' => (int) $quote['id_cliente'],
                'artist_id' => (int) $quote['id_artista'],
                'quote_id' => $quoteId,
                'starts_at' => $start,
                'ends_at' => $end,
                'session_number' => $sessionNumber,
                'notes' => $notes,
            ]);
            $appointmentId = (int) $this->database->lastInsertId();

            $history = $this->database->prepare(
                "INSERT INTO historial_citas (
                    id_cita,
                    id_cuenta_responsable,
                    estado_anterior,
                    estado_nuevo,
                    observacion
                 ) VALUES (
                    :appointment_id,
                    :account_id,
                    NULL,
                    'pendiente',
                    'El administrador creó la cita pendiente.'
                 )"
            );
            $history->execute([
                'appointment_id' => $appointmentId,
                'account_id' => $accountId,
            ]);

            $this->database->commit();

            return $appointmentId;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    public function confirmAndQueueEmail(
        int $appointmentId,
        int $accountId,
        string $subject,
        string $html,
        array $mailData
    ): int {
        $this->database->beginTransaction();

        try {
            $query = $this->database->prepare(
                "SELECT
                    appointment.estado,
                    appointment.id_cotizacion,
                    client.id_cuenta,
                    account.correo
                 FROM citas appointment
                 INNER JOIN clientes client
                    ON client.id_cliente = appointment.id_cliente
                 INNER JOIN cuentas account
                    ON account.id_cuenta = client.id_cuenta
                 WHERE appointment.id_cita = :appointment_id
                 FOR UPDATE"
            );
            $query->execute(['appointment_id' => $appointmentId]);
            $appointment = $query->fetch();

            if ($appointment === false) {
                throw new DomainException('La cita ya no existe.');
            }

            if ($appointment['estado'] !== 'pendiente') {
                throw new DomainException('Solo una cita pendiente puede marcarse como confirmada.');
            }

            if (filter_var($appointment['correo'], FILTER_VALIDATE_EMAIL) === false) {
                throw new DomainException('La cuenta del cliente no tiene un correo válido.');
            }

            $update = $this->database->prepare(
                "UPDATE citas
                 SET estado = 'confirmada'
                 WHERE id_cita = :appointment_id
                   AND estado = 'pendiente'"
            );
            $update->execute(['appointment_id' => $appointmentId]);

            if ($update->rowCount() !== 1) {
                throw new DomainException('La cita cambió mientras se procesaba. Recarga e intenta nuevamente.');
            }

            $history = $this->database->prepare(
                "INSERT INTO historial_citas (
                    id_cita,
                    id_cuenta_responsable,
                    estado_anterior,
                    estado_nuevo,
                    observacion
                 ) VALUES (
                    :appointment_id,
                    :account_id,
                    'pendiente',
                    'confirmada',
                    'El administrador confirmó la cita y generó el correo de confirmación.'
                 )"
            );
            $history->execute([
                'appointment_id' => $appointmentId,
                'account_id' => $accountId,
            ]);

            $mail = $this->database->prepare(
                "INSERT INTO correos_salida (
                    id_cuenta,
                    id_plantilla,
                    clave_evento,
                    destinatario,
                    asunto,
                    contenido,
                    datos_plantilla,
                    estado,
                    fecha_programada
                 ) VALUES (
                    :client_account_id,
                    NULL,
                    :event_key,
                    :recipient,
                    :subject,
                    :content,
                    :template_data,
                    'pendiente',
                    UTC_TIMESTAMP()
                 )"
            );
            $mail->execute([
                'client_account_id' => (int) $appointment['id_cuenta'],
                'event_key' => 'cita_confirmada:' . $appointmentId,
                'recipient' => strtolower((string) $appointment['correo']),
                'subject' => $subject,
                'content' => $html,
                'template_data' => json_encode($mailData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);
            $mailId = (int) $this->database->lastInsertId();

            $this->database->commit();

            return $mailId;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    public function cancelPending(int $quoteId, int $appointmentId, int $accountId): void
    {
        $this->database->beginTransaction();

        try {
            $update = $this->database->prepare(
                "UPDATE citas
                 SET estado = 'cancelada'
                 WHERE id_cita = :appointment_id
                   AND id_cotizacion = :quote_id
                   AND estado = 'pendiente'"
            );
            $update->execute([
                'appointment_id' => $appointmentId,
                'quote_id' => $quoteId,
            ]);

            if ($update->rowCount() !== 1) {
                throw new DomainException('Solo se puede cancelar una cita pendiente de esta cotización.');
            }

            $history = $this->database->prepare(
                "INSERT INTO historial_citas (
                    id_cita,
                    id_cuenta_responsable,
                    estado_anterior,
                    estado_nuevo,
                    observacion
                 ) VALUES (
                    :appointment_id,
                    :account_id,
                    'pendiente',
                    'cancelada',
                    'El administrador canceló la propuesta de cita antes de confirmarla.'
                 )"
            );
            $history->execute([
                'appointment_id' => $appointmentId,
                'account_id' => $accountId,
            ]);

            $this->database->commit();
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    public function emailForAppointment(int $appointmentId): ?array
    {
        $query = $this->database->prepare(
            "SELECT id_correo, estado
             FROM correos_salida
             WHERE clave_evento = :event_key
             LIMIT 1"
        );
        $query->execute(['event_key' => 'cita_confirmada:' . $appointmentId]);
        $email = $query->fetch();

        return $email === false ? null : $email;
    }

    public function refreshQueuedEmail(
        int $mailId,
        string $subject,
        string $html,
        array $mailData
    ): void {
        $query = $this->database->prepare(
            "UPDATE correos_salida
             SET asunto = :subject,
                 contenido = :content,
                 datos_plantilla = :template_data,
                 ultimo_error = NULL
             WHERE id_correo = :mail_id
               AND estado IN ('pendiente', 'fallido')"
        );
        $query->execute([
            'mail_id' => $mailId,
            'subject' => $subject,
            'content' => $html,
            'template_data' => json_encode($mailData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }

    public function claimEmail(int $mailId): ?array
    {
        $claim = $this->database->prepare(
            "UPDATE correos_salida
             SET estado = 'enviando',
                 intentos = intentos + 1,
                 ultimo_error = NULL
             WHERE id_correo = :mail_id
               AND estado IN ('pendiente', 'fallido')"
        );
        $claim->execute(['mail_id' => $mailId]);

        if ($claim->rowCount() !== 1) {
            return null;
        }

        $query = $this->database->prepare(
            'SELECT id_correo, destinatario, asunto, contenido, datos_plantilla
             FROM correos_salida
             WHERE id_correo = :mail_id
             LIMIT 1'
        );
        $query->execute(['mail_id' => $mailId]);
        $mail = $query->fetch();

        return $mail === false ? null : $mail;
    }

    public function markEmailSent(int $mailId): void
    {
        $query = $this->database->prepare(
            "UPDATE correos_salida
             SET estado = 'enviado',
                 ultimo_error = NULL,
                 fecha_envio = UTC_TIMESTAMP()
             WHERE id_correo = :mail_id"
        );
        $query->execute(['mail_id' => $mailId]);
    }

    public function markEmailFailed(int $mailId, string $error): void
    {
        $query = $this->database->prepare(
            "UPDATE correos_salida
             SET estado = 'fallido',
                 ultimo_error = :error
             WHERE id_correo = :mail_id"
        );
        $query->execute([
            'mail_id' => $mailId,
            'error' => function_exists('mb_substr') ? mb_substr($error, 0, 2000) : substr($error, 0, 2000),
        ]);
    }
}
