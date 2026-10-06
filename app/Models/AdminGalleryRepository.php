<?php
declare(strict_types=1);

final class AdminGalleryRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function listing(string $search = '', string $status = ''): array
    {
        $conditions = [];
        $parameters = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $conditions[] = "(
                tattoo.titulo LIKE :title
                OR tattoo.descripcion LIKE :description
                OR tattoo.slug LIKE :slug
                OR category.nombre LIKE :category_name
                OR artist.nombre_artistico LIKE :artist_name
                OR artist.nombre LIKE :artist_first_name
                OR artist.apellidos LIKE :artist_last_name
            )";
            $parameters += [
                'title' => $like,
                'description' => $like,
                'slug' => $like,
                'category_name' => $like,
                'artist_name' => $like,
                'artist_first_name' => $like,
                'artist_last_name' => $like,
            ];
        }

        if ($status === 'publicado') {
            $conditions[] = 'tattoo.publicado = TRUE';
        } elseif ($status === 'oculto') {
            $conditions[] = 'tattoo.publicado = FALSE';
        } elseif ($status === 'sin_autorizacion') {
            $conditions[] = 'tattoo.autorizacion_publicacion = FALSE';
        }

        $where = $conditions === []
            ? ''
            : 'WHERE ' . implode(' AND ', $conditions);

        $query = $this->database->prepare(
            "SELECT
                tattoo.id_tatuaje_realizado,
                tattoo.titulo,
                tattoo.descripcion,
                tattoo.slug,
                tattoo.fecha_realizacion,
                tattoo.autorizacion_publicacion,
                tattoo.publicado,
                category.nombre AS categoria,
                artist.id_artista,
                artist.slug AS artista_slug,
                COALESCE(
                    NULLIF(artist.nombre_artistico, ''),
                    CONCAT_WS(' ', artist.nombre, artist.apellidos)
                ) AS artista,
                image.imagen_url,
                image.texto_alternativo,
                (
                    SELECT COUNT(*)
                    FROM imagenes_tatuajes all_images
                    WHERE all_images.id_tatuaje_realizado =
                          tattoo.id_tatuaje_realizado
                ) AS total_imagenes
             FROM tatuajes_realizados tattoo
             INNER JOIN categorias_tatuajes category
                ON category.id_categoria = tattoo.id_categoria
             INNER JOIN artistas artist
                ON artist.id_artista = tattoo.id_artista
             LEFT JOIN imagenes_tatuajes image
                ON image.id_imagen = (
                    SELECT selected_image.id_imagen
                    FROM imagenes_tatuajes selected_image
                    WHERE selected_image.id_tatuaje_realizado =
                          tattoo.id_tatuaje_realizado
                    ORDER BY
                        selected_image.es_portada DESC,
                        selected_image.orden,
                        selected_image.id_imagen
                    LIMIT 1
                )
             {$where}
             ORDER BY tattoo.fecha_realizacion DESC,
                      tattoo.id_tatuaje_realizado DESC"
        );
        $query->execute($parameters);

        return $query->fetchAll();
    }

    public function find(int $workId): ?array
    {
        $query = $this->database->prepare(
            "SELECT
                tattoo.*,
                image.imagen_url,
                image.descripcion AS imagen_descripcion,
                image.texto_alternativo
             FROM tatuajes_realizados tattoo
             LEFT JOIN imagenes_tatuajes image
                ON image.id_imagen = (
                    SELECT selected_image.id_imagen
                    FROM imagenes_tatuajes selected_image
                    WHERE selected_image.id_tatuaje_realizado =
                          tattoo.id_tatuaje_realizado
                    ORDER BY
                        selected_image.es_portada DESC,
                        selected_image.orden,
                        selected_image.id_imagen
                    LIMIT 1
                )
             WHERE tattoo.id_tatuaje_realizado = :work_id
             LIMIT 1"
        );
        $query->execute(['work_id' => $workId]);

        $work = $query->fetch();

        return $work === false ? null : $work;
    }

    public function artists(): array
    {
        return $this->database->query(
            "SELECT
                id_artista,
                COALESCE(
                    NULLIF(nombre_artistico, ''),
                    CONCAT_WS(' ', nombre, apellidos)
                ) AS nombre_publico
             FROM artistas
             WHERE activo = TRUE
             ORDER BY nombre_publico"
        )->fetchAll();
    }

    public function clients(): array
    {
        return $this->database->query(
            "SELECT id_cliente, CONCAT_WS(' ', nombre, apellidos) AS nombre_publico
             FROM clientes
             ORDER BY nombre, apellidos"
        )->fetchAll();
    }

    public function categories(): array
    {
        return $this->database->query(
            "SELECT id_categoria, nombre
             FROM categorias_tatuajes
             WHERE activo = TRUE
             ORDER BY nombre"
        )->fetchAll();
    }

    public function save(array $data, ?array $image, ?int $workId = null): int
    {
        $this->database->beginTransaction();

        try {
            if ($workId === null) {
                $query = $this->database->prepare(
                    'INSERT INTO tatuajes_realizados (
                        id_cliente,
                        id_artista,
                        id_boceto,
                        id_cotizacion,
                        id_categoria,
                        titulo,
                        descripcion,
                        slug,
                        fecha_realizacion,
                        autorizacion_publicacion,
                        publicado
                    ) VALUES (
                        :id_cliente,
                        :id_artista,
                        NULL,
                        NULL,
                        :id_categoria,
                        :titulo,
                        :descripcion,
                        :slug,
                        :fecha_realizacion,
                        :autorizacion_publicacion,
                        :publicado
                    )'
                );
                $query->execute($data);
                $workId = (int) $this->database->lastInsertId();
            } else {
                $query = $this->database->prepare(
                    'UPDATE tatuajes_realizados
                     SET
                        id_cliente = :id_cliente,
                        id_artista = :id_artista,
                        id_categoria = :id_categoria,
                        titulo = :titulo,
                        descripcion = :descripcion,
                        slug = :slug,
                        fecha_realizacion = :fecha_realizacion,
                        autorizacion_publicacion = :autorizacion_publicacion,
                        publicado = :publicado
                     WHERE id_tatuaje_realizado = :work_id'
                );
                $query->execute($data + ['work_id' => $workId]);

                if ($query->rowCount() === 0 && $this->find($workId) === null) {
                    throw new DomainException('La obra ya no existe.');
                }
            }

            if ($image !== null) {
                $clearCover = $this->database->prepare(
                    'UPDATE imagenes_tatuajes
                     SET es_portada = FALSE
                     WHERE id_tatuaje_realizado = :work_id
                       AND es_portada = TRUE'
                );
                $clearCover->execute(['work_id' => $workId]);

                $insertImage = $this->database->prepare(
                    'INSERT INTO imagenes_tatuajes (
                        id_tatuaje_realizado,
                        imagen_url,
                        descripcion,
                        texto_alternativo,
                        orden,
                        es_portada
                    ) VALUES (
                        :work_id,
                        :image_url,
                        :image_description,
                        :alt_text,
                        0,
                        TRUE
                    )'
                );
                $insertImage->execute([
                    'work_id' => $workId,
                    'image_url' => $image['imagen_url'],
                    'image_description' => $image['descripcion'],
                    'alt_text' => $image['texto_alternativo'],
                ]);
            }

            $this->database->commit();

            return $workId;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    public function setPublished(int $workId, bool $published): void
    {
        $query = $this->database->prepare(
            'UPDATE tatuajes_realizados
             SET publicado = :published
             WHERE id_tatuaje_realizado = :work_id
               AND (
                    :published_check = FALSE
                    OR autorizacion_publicacion = TRUE
               )'
        );
        $query->execute([
            'published' => $published ? 1 : 0,
            'published_check' => $published ? 1 : 0,
            'work_id' => $workId,
        ]);

        if ($query->rowCount() === 0) {
            $work = $this->find($workId);

            if ($work === null) {
                throw new DomainException('La obra ya no existe.');
            }

            if ($published && !(bool) $work['autorizacion_publicacion']) {
                throw new DomainException('La obra no puede publicarse sin autorización del cliente.');
            }
        }
    }
}
