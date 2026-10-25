<?php
declare(strict_types=1);

final class TestimonialRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function published(int $limit = 24): array
    {
        $limit = max(1, min($limit, 60));
        $query = $this->database->query(
            "SELECT
                testimonial.id_testimonio,
                testimonial.titulo,
                testimonial.contenido,
                testimonial.nombre_publico,
                testimonial.fecha,
                testimonial.destacado,
                COALESCE(NULLIF(artist.nombre_artistico, ''), CONCAT_WS(' ', artist.nombre, artist.apellidos)) AS artista,
                category.nombre AS estilo
             FROM testimonios testimonial
             LEFT JOIN citas appointment ON appointment.id_cita = testimonial.id_cita
             LEFT JOIN artistas artist ON artist.id_artista = appointment.id_artista
             LEFT JOIN cotizaciones quote ON quote.id_cotizacion = appointment.id_cotizacion
             LEFT JOIN categorias_tatuajes category ON category.id_categoria = quote.id_categoria
             WHERE testimonial.estado_publicacion = 'publicado'
             ORDER BY testimonial.destacado DESC, testimonial.fecha DESC
             LIMIT {$limit}"
        );

        return $query->fetchAll();
    }

    public function publicSummary(): array
    {
        $query = $this->database->query(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(destacado = TRUE), 0) AS destacados,
                COUNT(DISTINCT appointment.id_artista) AS artistas
             FROM testimonios testimonial
             LEFT JOIN citas appointment ON appointment.id_cita = testimonial.id_cita
             WHERE testimonial.estado_publicacion = 'publicado'"
        );

        return $query->fetch() ?: ['total' => 0, 'destacados' => 0, 'artistas' => 0];
    }

    public function clientForAccount(int $accountId): ?array
    {
        $query = $this->database->prepare(
            'SELECT id_cliente, nombre, apellidos
             FROM clientes
             WHERE id_cuenta = :account_id
             LIMIT 1'
        );
        $query->execute(['account_id' => $accountId]);
        $client = $query->fetch();

        return $client === false ? null : $client;
    }

    public function eligibleAppointments(int $clientId): array
    {
        $query = $this->database->prepare(
            "SELECT
                appointment.id_cita,
                appointment.fecha_hora_inicio,
                COALESCE(NULLIF(artist.nombre_artistico, ''), CONCAT_WS(' ', artist.nombre, artist.apellidos)) AS artista,
                COALESCE(category.nombre, 'Tatuaje personalizado') AS estilo
             FROM citas appointment
             INNER JOIN artistas artist ON artist.id_artista = appointment.id_artista
             LEFT JOIN cotizaciones quote ON quote.id_cotizacion = appointment.id_cotizacion
             LEFT JOIN categorias_tatuajes category ON category.id_categoria = quote.id_categoria
             LEFT JOIN testimonios testimonial ON testimonial.id_cita = appointment.id_cita
             WHERE appointment.id_cliente = :client_id
               AND appointment.estado = 'finalizada'
               AND testimonial.id_testimonio IS NULL
             ORDER BY appointment.fecha_hora_inicio DESC"
        );
        $query->execute(['client_id' => $clientId]);

        return $query->fetchAll();
    }

    public function forClient(int $clientId): array
    {
        $query = $this->database->prepare(
            "SELECT
                testimonial.*,
                appointment.fecha_hora_inicio,
                COALESCE(NULLIF(artist.nombre_artistico, ''), CONCAT_WS(' ', artist.nombre, artist.apellidos)) AS artista,
                COALESCE(category.nombre, 'Tatuaje personalizado') AS estilo
             FROM testimonios testimonial
             LEFT JOIN citas appointment ON appointment.id_cita = testimonial.id_cita
             LEFT JOIN artistas artist ON artist.id_artista = appointment.id_artista
             LEFT JOIN cotizaciones quote ON quote.id_cotizacion = appointment.id_cotizacion
             LEFT JOIN categorias_tatuajes category ON category.id_categoria = quote.id_categoria
             WHERE testimonial.id_cliente = :client_id
             ORDER BY testimonial.fecha DESC"
        );
        $query->execute(['client_id' => $clientId]);

        return $query->fetchAll();
    }

    public function createForAppointment(
        int $clientId,
        int $appointmentId,
        string $title,
        string $content,
        string $publicName
    ): int {
        $this->database->beginTransaction();

        try {
            $appointmentQuery = $this->database->prepare(
                "SELECT id_cita
                 FROM citas
                 WHERE id_cita = :appointment_id
                   AND id_cliente = :client_id
                   AND estado = 'finalizada'
                 FOR UPDATE"
            );
            $appointmentQuery->execute([
                'appointment_id' => $appointmentId,
                'client_id' => $clientId,
            ]);

            if ($appointmentQuery->fetchColumn() === false) {
                throw new DomainException('La cita seleccionada no está disponible para dejar un testimonio.');
            }

            $duplicateQuery = $this->database->prepare(
                'SELECT id_testimonio FROM testimonios WHERE id_cita = :appointment_id LIMIT 1'
            );
            $duplicateQuery->execute(['appointment_id' => $appointmentId]);

            if ($duplicateQuery->fetchColumn() !== false) {
                throw new DomainException('Esta cita ya tiene un testimonio registrado.');
            }

            $insert = $this->database->prepare(
                "INSERT INTO testimonios (
                    id_cliente,
                    id_cita,
                    titulo,
                    contenido,
                    nombre_publico,
                    estado_publicacion,
                    destacado
                ) VALUES (
                    :client_id,
                    :appointment_id,
                    :title,
                    :content,
                    :public_name,
                    'pendiente',
                    FALSE
                )"
            );
            $insert->execute([
                'client_id' => $clientId,
                'appointment_id' => $appointmentId,
                'title' => $title === '' ? null : $title,
                'content' => $content,
                'public_name' => $publicName,
            ]);

            $testimonialId = (int) $this->database->lastInsertId();
            $this->database->commit();

            return $testimonialId;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    public function adminListing(string $status = ''): array
    {
        $allowed = ['pendiente', 'publicado', 'rechazado'];
        $parameters = [];
        $where = '';

        if (in_array($status, $allowed, true)) {
            $where = 'WHERE testimonial.estado_publicacion = :status';
            $parameters['status'] = $status;
        }

        $query = $this->database->prepare(
            "SELECT
                testimonial.*,
                CONCAT_WS(' ', client.nombre, client.apellidos) AS cliente,
                account.correo AS correo_cliente,
                appointment.fecha_hora_inicio,
                COALESCE(NULLIF(artist.nombre_artistico, ''), CONCAT_WS(' ', artist.nombre, artist.apellidos)) AS artista,
                COALESCE(category.nombre, 'Tatuaje personalizado') AS estilo
             FROM testimonios testimonial
             INNER JOIN clientes client ON client.id_cliente = testimonial.id_cliente
             INNER JOIN cuentas account ON account.id_cuenta = client.id_cuenta
             LEFT JOIN citas appointment ON appointment.id_cita = testimonial.id_cita
             LEFT JOIN artistas artist ON artist.id_artista = appointment.id_artista
             LEFT JOIN cotizaciones quote ON quote.id_cotizacion = appointment.id_cotizacion
             LEFT JOIN categorias_tatuajes category ON category.id_categoria = quote.id_categoria
             {$where}
             ORDER BY
                testimonial.estado_publicacion = 'pendiente' DESC,
                testimonial.destacado DESC,
                testimonial.fecha DESC"
        );
        $query->execute($parameters);

        return $query->fetchAll();
    }

    public function moderate(int $testimonialId, string $status, bool $featured): bool
    {
        $query = $this->database->prepare(
            'UPDATE testimonios
             SET estado_publicacion = :status,
                 destacado = :featured
             WHERE id_testimonio = :testimonial_id'
        );
        $query->execute([
            'status' => $status,
            'featured' => $featured ? 1 : 0,
            'testimonial_id' => $testimonialId,
        ]);

        return $query->rowCount() > 0;
    }

    public function adminSummary(): array
    {
        $query = $this->database->query(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(estado_publicacion = 'pendiente'), 0) AS pendientes,
                COALESCE(SUM(estado_publicacion = 'publicado'), 0) AS publicados,
                COALESCE(SUM(destacado = TRUE), 0) AS destacados
             FROM testimonios"
        );

        return $query->fetch() ?: ['total' => 0, 'pendientes' => 0, 'publicados' => 0, 'destacados' => 0];
    }
}
