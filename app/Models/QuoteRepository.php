<?php
declare(strict_types=1);

final class QuoteRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function categories(): array
    {
        return $this->database->query(
            "SELECT id_categoria, nombre, descripcion
             FROM categorias_tatuajes
             WHERE activo = TRUE
             ORDER BY nombre"
        )->fetchAll();
    }

    public function artists(): array
    {
        return $this->database->query(
            "SELECT
                artist.id_artista,
                artist.slug,
                COALESCE(
                    NULLIF(artist.nombre_artistico, ''),
                    CONCAT_WS(' ', artist.nombre, artist.apellidos)
                ) AS nombre_publico,
                GROUP_CONCAT(
                    DISTINCT category.nombre
                    ORDER BY category.nombre
                    SEPARATOR ' · '
                ) AS especialidades
             FROM artistas artist
             LEFT JOIN artistas_especialidades specialty
                ON specialty.id_artista = artist.id_artista
             LEFT JOIN categorias_tatuajes category
                ON category.id_categoria = specialty.id_categoria
             WHERE artist.activo = TRUE
             GROUP BY artist.id_artista
             ORDER BY nombre_publico"
        )->fetchAll();
    }

    public function resolveArtistReference(string $reference): ?int
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        if (ctype_digit($reference) && (int) $reference > 0) {
            $query = $this->database->prepare(
                'SELECT id_artista FROM artistas WHERE id_artista = :artist_id AND activo = TRUE'
            );
            $query->execute(['artist_id' => (int) $reference]);
        } else {
            $query = $this->database->prepare(
                'SELECT id_artista FROM artistas WHERE slug = :slug AND activo = TRUE'
            );
            $query->execute(['slug' => $reference]);
        }

        $artistId = $query->fetchColumn();

        return $artistId === false ? null : (int) $artistId;
    }

    public function clientForAccount(int $accountId): ?array
    {
        $query = $this->database->prepare(
            "SELECT
                client.id_cliente,
                client.nombre,
                client.apellidos,
                client.telefono,
                account.correo
             FROM clientes client
             INNER JOIN cuentas account ON account.id_cuenta = client.id_cuenta
             WHERE client.id_cuenta = :account_id
             LIMIT 1"
        );
        $query->execute(['account_id' => $accountId]);
        $profile = $query->fetch();

        return $profile === false ? null : $profile;
    }

    public function create(array $data, array $references): int
    {
        $this->database->beginTransaction();

        try {
            $query = $this->database->prepare(
                'INSERT INTO cotizaciones (
                    id_cliente,
                    id_artista,
                    id_categoria,
                    id_boceto,
                    nombre_contacto,
                    correo_contacto,
                    telefono_contacto,
                    metodo_contacto_preferido,
                    descripcion_idea,
                    zona_cuerpo,
                    ancho_cm,
                    alto_cm,
                    tamano_descripcion,
                    a_color,
                    fecha_preferida,
                    estado,
                    observaciones
                ) VALUES (
                    :client_id,
                    :artist_id,
                    :category_id,
                    NULL,
                    :contact_name,
                    :contact_email,
                    :contact_phone,
                    :preferred_contact_method,
                    :idea_description,
                    :body_area,
                    :width_cm,
                    :height_cm,
                    :size_description,
                    :in_color,
                    :preferred_date,
                    \'solicitada\',
                    \'Solicitud recibida desde el formulario público.\'
                )'
            );
            $query->execute($data);
            $quoteId = (int) $this->database->lastInsertId();

            if ($references !== []) {
                $insertReference = $this->database->prepare(
                    'INSERT INTO referencias_cotizacion (
                        id_cotizacion,
                        imagen_url,
                        nombre_archivo_original,
                        tipo_mime,
                        descripcion,
                        texto_alternativo,
                        orden
                    ) VALUES (
                        :quote_id,
                        :image_url,
                        :original_name,
                        :mime_type,
                        :description,
                        :alt_text,
                        :display_order
                    )'
                );

                foreach ($references as $position => $reference) {
                    $insertReference->execute([
                        'quote_id' => $quoteId,
                        'image_url' => $reference['imagen_url'],
                        'original_name' => $reference['nombre_original'],
                        'mime_type' => $reference['tipo_mime'],
                        'description' => 'Referencia visual enviada por el cliente.',
                        'alt_text' => 'Referencia visual ' . ($position + 1) . ' de la solicitud ' . $quoteId,
                        'display_order' => $position,
                    ]);
                }
            }

            $this->database->commit();

            return $quoteId;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    public function quotesForClient(int $clientId): array
    {
        $query = $this->database->prepare(
            "SELECT
                quote.id_cotizacion,
                quote.descripcion_idea,
                quote.zona_cuerpo,
                quote.tamano_descripcion,
                quote.a_color,
                quote.fecha_preferida,
                quote.precio_cotizado,
                quote.anticipo_requerido,
                quote.duracion_estimada_minutos,
                quote.sesiones_estimadas,
                quote.estado,
                quote.fecha_solicitud,
                quote.fecha_vencimiento,
                quote.observaciones,
                category.nombre AS categoria,
                COALESCE(
                    NULLIF(artist.nombre_artistico, ''),
                    CONCAT_WS(' ', artist.nombre, artist.apellidos),
                    'Artista por asignar'
                ) AS artista,
                (SELECT COUNT(*)
                 FROM referencias_cotizacion reference
                 WHERE reference.id_cotizacion = quote.id_cotizacion) AS total_referencias
             FROM cotizaciones quote
             INNER JOIN categorias_tatuajes category
                ON category.id_categoria = quote.id_categoria
             LEFT JOIN artistas artist
                ON artist.id_artista = quote.id_artista
             WHERE quote.id_cliente = :client_id
             ORDER BY quote.fecha_solicitud DESC, quote.id_cotizacion DESC"
        );
        $query->execute(['client_id' => $clientId]);

        return $query->fetchAll();
    }

    public function findForClient(int $quoteId, int $clientId): ?array
    {
        $query = $this->database->prepare(
            "SELECT
                quote.*,
                category.nombre AS categoria,
                COALESCE(
                    NULLIF(artist.nombre_artistico, ''),
                    CONCAT_WS(' ', artist.nombre, artist.apellidos),
                    'Artista por asignar'
                ) AS artista
             FROM cotizaciones quote
             INNER JOIN categorias_tatuajes category
                ON category.id_categoria = quote.id_categoria
             LEFT JOIN artistas artist ON artist.id_artista = quote.id_artista
             WHERE quote.id_cotizacion = :quote_id
               AND quote.id_cliente = :client_id
             LIMIT 1"
        );
        $query->execute(['quote_id' => $quoteId, 'client_id' => $clientId]);
        $quote = $query->fetch();

        if ($quote === false) {
            return null;
        }

        $quote['referencias'] = $this->references($quoteId);
        $quote['historial'] = $this->historyForClient($quoteId);
        $quote['citas'] = $this->appointmentsForQuote($quoteId, $clientId);

        return $quote;
    }

    public function recordClientResponse(
        int $quoteId,
        int $clientId,
        int $accountId,
        string $newStatus,
        string $message
    ): void {
        $this->database->beginTransaction();

        try {
            $query = $this->database->prepare(
                'SELECT estado, precio_cotizado, fecha_vencimiento
                 FROM cotizaciones
                 WHERE id_cotizacion = :quote_id
                   AND id_cliente = :client_id
                 FOR UPDATE'
            );
            $query->execute([
                'quote_id' => $quoteId,
                'client_id' => $clientId,
            ]);
            $quote = $query->fetch();

            if ($quote === false) {
                throw new DomainException('La cotización no existe o no pertenece a tu cuenta.');
            }

            if ($quote['estado'] !== 'enviada') {
                throw new DomainException('Esta propuesta ya no está disponible para responder.');
            }

            if ($quote['precio_cotizado'] === null) {
                throw new DomainException('El estudio todavía no ha definido el valor de la cotización.');
            }

            if (
                $quote['fecha_vencimiento'] !== null
                && (string) $quote['fecha_vencimiento'] < gmdate('Y-m-d H:i:s')
            ) {
                throw new DomainException('Esta propuesta ya venció. Contacta al estudio para solicitar una actualización.');
            }

            $update = $this->database->prepare(
                'UPDATE cotizaciones
                 SET estado = :new_status
                 WHERE id_cotizacion = :quote_id
                   AND id_cliente = :client_id'
            );
            $update->execute([
                'new_status' => $newStatus,
                'quote_id' => $quoteId,
                'client_id' => $clientId,
            ]);

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
                    :message
                 )'
            );
            $history->execute([
                'quote_id' => $quoteId,
                'account_id' => $accountId,
                'previous_status' => $quote['estado'],
                'new_status' => $newStatus,
                'quoted_price' => $quote['precio_cotizado'],
                'message' => $message,
            ]);

            $this->database->commit();
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    private function historyForClient(int $quoteId): array
    {
        $query = $this->database->prepare(
            "SELECT
                history.estado_anterior,
                history.estado_nuevo,
                history.fecha,
                CASE
                    WHEN role.nombre_rol = 'cliente' THEN 'cliente'
                    ELSE 'estudio'
                END AS responsable,
                CASE
                    WHEN role.nombre_rol = 'cliente' THEN history.observacion
                    ELSE NULL
                END AS mensaje_cliente
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

    private function appointmentsForQuote(int $quoteId, int $clientId): array
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
                ) AS artista
             FROM citas appointment
             INNER JOIN artistas artist
                ON artist.id_artista = appointment.id_artista
             WHERE appointment.id_cotizacion = :quote_id
               AND appointment.id_cliente = :client_id
             ORDER BY appointment.numero_sesion, appointment.fecha_hora_inicio"
        );
        $query->execute([
            'quote_id' => $quoteId,
            'client_id' => $clientId,
        ]);

        return $query->fetchAll();
    }

    public function references(int $quoteId): array
    {
        $query = $this->database->prepare(
            "SELECT id_referencia, imagen_url, texto_alternativo
             FROM referencias_cotizacion
             WHERE id_cotizacion = :quote_id
             ORDER BY orden, id_referencia"
        );
        $query->execute(['quote_id' => $quoteId]);

        return $query->fetchAll();
    }
}
