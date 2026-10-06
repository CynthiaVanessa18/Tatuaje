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
                SUM(estado = 'aceptada') AS aceptadas
             FROM cotizaciones"
        )->fetch();

        return $row ?: ['total' => 0, 'nuevas' => 0, 'en_revision' => 0, 'enviadas' => 0, 'aceptadas' => 0];
    }

    public function listing(string $search = '', string $status = ''): array
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
}
