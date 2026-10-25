<?php
declare(strict_types=1);

final class AdminQuoteRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function summary(): array
    {
        $row = $this->database->query(
            "SELECT
                COUNT(*) AS total,
                SUM(estado = 'solicitada') AS nuevas,
                SUM(estado = 'en_revision') AS en_revision,
                SUM(estado = 'enviada') AS enviadas,
                SUM(estado = 'aceptada' AND (
                    SELECT COUNT(*) FROM citas appointment
                    WHERE appointment.id_cotizacion = cotizaciones.id_cotizacion
                      AND appointment.estado NOT IN ('cancelada', 'no_asistio')
                ) < COALESCE(sesiones_estimadas, 1)) AS aceptadas,
                SUM(estado = 'aceptada' AND (
                    SELECT COUNT(*) FROM citas appointment
                    WHERE appointment.id_cotizacion = cotizaciones.id_cotizacion
                      AND appointment.estado NOT IN ('cancelada', 'no_asistio')
                ) >= COALESCE(sesiones_estimadas, 1)) AS agendadas,
                SUM(estado IN ('rechazada', 'vencida', 'cancelada')) AS cerradas
             FROM cotizaciones"
        )->fetch();

        return $row ?: ['total' => 0, 'nuevas' => 0, 'en_revision' => 0, 'enviadas' => 0, 'aceptadas' => 0, 'agendadas' => 0, 'cerradas' => 0];
    }

    public function listing(string $search = '', string $status = '', string $view = 'active'): array
    {
        $conditions = [];
        $parameters = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $conditions[] = "(
                quote.nombre_contacto LIKE :contact_name
                OR quote.correo_contacto LIKE :contact_email
                OR quote.descripcion_idea LIKE :idea
                OR quote.zona_cuerpo LIKE :body_area
                OR category.nombre LIKE :category_name
                OR artist.nombre_artistico LIKE :artist_name
                OR CAST(quote.id_cotizacion AS CHAR) = :quote_number
            )";
            $parameters += [
                'contact_name' => $like,
                'contact_email' => $like,
                'idea' => $like,
                'body_area' => $like,
                'category_name' => $like,
                'artist_name' => $like,
                'quote_number' => $search,
            ];
        }

        if ($status !== '') {
            $conditions[] = 'quote.estado = :status';
            $parameters['status'] = $status;
        }

        if ($status === '') {
            $activeAppointments = "(SELECT COUNT(*) FROM citas appointment
                WHERE appointment.id_cotizacion = quote.id_cotizacion
                  AND appointment.estado NOT IN ('cancelada', 'no_asistio'))";

            if ($view === 'scheduled') {
                $conditions[] = "quote.estado = 'aceptada' AND {$activeAppointments} >= COALESCE(quote.sesiones_estimadas, 1)";
            } elseif ($view === 'closed') {
                $conditions[] = "quote.estado IN ('rechazada', 'vencida', 'cancelada')";
            } else {
                $conditions[] = "(quote.estado IN ('solicitada', 'en_revision', 'enviada')
                    OR (quote.estado = 'aceptada' AND {$activeAppointments} < COALESCE(quote.sesiones_estimadas, 1)))";
            }
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $query = $this->database->prepare(
            "SELECT
                quote.id_cotizacion,
                quote.nombre_contacto,
                quote.correo_contacto,
                quote.telefono_contacto,
                quote.metodo_contacto_preferido,
                quote.descripcion_idea,
                quote.zona_cuerpo,
                quote.tamano_descripcion,
                quote.a_color,
                quote.fecha_preferida,
                quote.precio_cotizado,
                quote.anticipo_requerido,
                quote.sesiones_estimadas,
                quote.estado,
                quote.fecha_solicitud,
                category.nombre AS categoria,
                COALESCE(
                    NULLIF(artist.nombre_artistico, ''),
                    CONCAT_WS(' ', artist.nombre, artist.apellidos),
                    'Sin asignar'
                ) AS artista,
                (SELECT COUNT(*) FROM citas appointment
                 WHERE appointment.id_cotizacion = quote.id_cotizacion
                   AND appointment.estado NOT IN ('cancelada', 'no_asistio')) AS sesiones_agendadas,
                (SELECT COUNT(*)
                 FROM referencias_cotizacion reference
                 WHERE reference.id_cotizacion = quote.id_cotizacion) AS total_referencias
             FROM cotizaciones quote
             INNER JOIN categorias_tatuajes category
                ON category.id_categoria = quote.id_categoria
             LEFT JOIN artistas artist ON artist.id_artista = quote.id_artista
             {$where}
             ORDER BY
                FIELD(quote.estado, 'solicitada', 'en_revision', 'enviada', 'aceptada', 'rechazada', 'vencida', 'cancelada'),
                quote.fecha_solicitud DESC,
                quote.id_cotizacion DESC"
        );
        $query->execute($parameters);

        return $query->fetchAll();
    }

    public function find(int $quoteId): ?array
    {
        $query = $this->database->prepare(
            "SELECT
                quote.*,
                category.nombre AS categoria,
                category.descripcion AS categoria_descripcion,
                COALESCE(
                    NULLIF(artist.nombre_artistico, ''),
                    CONCAT_WS(' ', artist.nombre, artist.apellidos),
                    'Sin asignar'
                ) AS artista,
                CONCAT_WS(' ', client.nombre, client.apellidos) AS cliente_registrado
             FROM cotizaciones quote
             INNER JOIN categorias_tatuajes category
                ON category.id_categoria = quote.id_categoria
             LEFT JOIN artistas artist ON artist.id_artista = quote.id_artista
             LEFT JOIN clientes client ON client.id_cliente = quote.id_cliente
             WHERE quote.id_cotizacion = :quote_id
             LIMIT 1"
        );
        $query->execute(['quote_id' => $quoteId]);
        $quote = $query->fetch();

        if ($quote === false) {
            return null;
        }

        $quote['referencias'] = $this->references($quoteId);
        $quote['historial'] = $this->history($quoteId);
        $quote['preferencias_sesiones'] = $this->sessionPreferences($quoteId);

        return $quote;
    }

    public function history(int $quoteId): array
    {
        $query = $this->database->prepare(
            "SELECT
                history.estado_anterior,
                history.estado_nuevo,
                history.precio_cotizado,
                history.observacion,
                history.fecha,
                account.usuario AS responsable,
                role.nombre_rol AS responsable_rol
             FROM historial_cotizaciones history
             LEFT JOIN cuentas account
                ON account.id_cuenta = history.id_cuenta_responsable
             LEFT JOIN roles role
                ON role.id_rol = account.id_rol
             WHERE history.id_cotizacion = :quote_id
             ORDER BY history.fecha DESC, history.id_historial DESC"
        );
        $query->execute(['quote_id' => $quoteId]);

        return $query->fetchAll();
    }

    public function references(int $quoteId): array
    {
        $query = $this->database->prepare(
            "SELECT id_referencia, imagen_url, nombre_archivo_original, texto_alternativo
             FROM referencias_cotizacion
             WHERE id_cotizacion = :quote_id
             ORDER BY orden, id_referencia"
        );
        $query->execute(['quote_id' => $quoteId]);

        return $query->fetchAll();
    }

    private function sessionPreferences(int $quoteId): array
    {
        $query = $this->database->prepare(
            'SELECT numero_sesion, fecha_preferida
             FROM preferencias_sesiones_cotizacion
             WHERE id_cotizacion = :quote_id
             ORDER BY numero_sesion'
        );
        $query->execute(['quote_id' => $quoteId]);
        $preferences = [];
        foreach ($query->fetchAll() as $row) {
            $preferences[(int) $row['numero_sesion']] = (string) $row['fecha_preferida'];
        }
        return $preferences;
    }

    public function artists(): array
    {
        return $this->database->query(
            "SELECT
                id_artista,
                COALESCE(NULLIF(nombre_artistico, ''), CONCAT_WS(' ', nombre, apellidos)) AS nombre_publico
             FROM artistas
             WHERE activo = TRUE
             ORDER BY nombre_publico"
        )->fetchAll();
    }

    public function expireSentQuotes(): void
    {
        $this->database->beginTransaction();

        try {
            $expired = $this->database->query(
                "SELECT id_cotizacion, precio_cotizado FROM cotizaciones
                 WHERE estado = 'enviada'
                   AND fecha_vencimiento IS NOT NULL
                   AND fecha_vencimiento < UTC_TIMESTAMP()
                 FOR UPDATE"
            )->fetchAll();

            if ($expired !== []) {
                $this->database->exec(
                    "UPDATE cotizaciones SET estado = 'vencida'
                     WHERE estado = 'enviada'
                       AND fecha_vencimiento IS NOT NULL
                       AND fecha_vencimiento < UTC_TIMESTAMP()"
                );
                $history = $this->database->prepare(
                    "INSERT INTO historial_cotizaciones
                        (id_cotizacion, id_cuenta_responsable, estado_anterior, estado_nuevo, precio_cotizado, observacion)
                     VALUES (:quote_id, NULL, 'enviada', 'vencida', :price, 'La propuesta venció automáticamente.')"
                );
                foreach ($expired as $quote) {
                    $history->execute(['quote_id' => $quote['id_cotizacion'], 'price' => $quote['precio_cotizado']]);
                }
            }

            $this->database->commit();
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }

    public function updateResponse(int $quoteId, int $accountId, array $data): void
    {
        $this->database->beginTransaction();

        try {
            $currentQuery = $this->database->prepare(
                'SELECT estado
                 FROM cotizaciones
                 WHERE id_cotizacion = :quote_id
                 FOR UPDATE'
            );
            $currentQuery->execute(['quote_id' => $quoteId]);
            $previousStatus = $currentQuery->fetchColumn();

            if ($previousStatus === false) {
                throw new DomainException('La solicitud ya no existe.');
            }

            if (!in_array($previousStatus, ['solicitada', 'en_revision'], true)) {
                throw new DomainException('La propuesta ya fue enviada o respondida y sus datos están bloqueados.');
            }

            $query = $this->database->prepare(
                'UPDATE cotizaciones
                 SET
                    id_artista = :artist_id,
                    precio_cotizado = :quoted_price,
                    anticipo_requerido = :required_deposit,
                    duracion_estimada_minutos = :estimated_minutes,
                    sesiones_estimadas = :estimated_sessions,
                    estado = :status,
                    fecha_vencimiento = :expiration_date,
                    observaciones = :notes
                 WHERE id_cotizacion = :quote_id'
            );
            $query->execute($data + ['quote_id' => $quoteId]);

            $history = $this->database->prepare(
                'INSERT INTO historial_cotizaciones (
                    id_cotizacion,
                    id_cuenta_responsable,
                    estado_anterior,
                    estado_nuevo,
                    precio_cotizado,
                    observacion
                 ) VALUES (
                    :quote_id,
                    :account_id,
                    :previous_status,
                    :new_status,
                    :quoted_price,
                    :observation
                 )'
            );
            $history->execute([
                'quote_id' => $quoteId,
                'account_id' => $accountId,
                'previous_status' => $previousStatus,
                'new_status' => $data['status'],
                'quoted_price' => $data['quoted_price'],
                'observation' => $previousStatus === $data['status']
                    ? 'El estudio actualizó los detalles de la propuesta.'
                    : 'El estudio cambió el estado de la propuesta.',
            ]);

            $this->database->commit();
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    public function markUnderReview(int $quoteId, int $accountId): bool
    {
        $this->database->beginTransaction();

        try {
            $update = $this->database->prepare(
                "UPDATE cotizaciones SET estado = 'en_revision'
                 WHERE id_cotizacion = :quote_id AND estado = 'solicitada'"
            );
            $update->execute(['quote_id' => $quoteId]);

            if ($update->rowCount() !== 1) {
                $this->database->commit();
                return false;
            }

            $history = $this->database->prepare(
                "INSERT INTO historial_cotizaciones
                    (id_cotizacion, id_cuenta_responsable, estado_anterior, estado_nuevo, precio_cotizado, observacion)
                 VALUES (:quote_id, :account_id, 'solicitada', 'en_revision', NULL, 'El administrador abrió la solicitud para revisarla.')"
            );
            $history->execute(['quote_id' => $quoteId, 'account_id' => $accountId]);
            $this->database->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }
}
