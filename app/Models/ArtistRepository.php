<?php
declare(strict_types=1);

final class ArtistRepository
{
    public function __construct(
        private PDO $database
    ) {
    }

    public function categories(): array
    {
        $query = $this->database->query(
            "SELECT
                ct.id_categoria,
                ct.nombre,
                COUNT(DISTINCT ae.id_artista) AS total_artistas
             FROM categorias_tatuajes ct
             INNER JOIN artistas_especialidades ae
                ON ae.id_categoria = ct.id_categoria
             INNER JOIN artistas a
                ON a.id_artista = ae.id_artista
               AND a.activo = TRUE
             WHERE ct.activo = TRUE
             GROUP BY
                ct.id_categoria,
                ct.nombre
             ORDER BY ct.nombre"
        );

        return $query->fetchAll();
    }

    public function search(
        string $search = '',
        ?int $categoryId = null
    ): array {
        $conditions = ['a.activo = TRUE'];
        $parameters = [];

        if ($search !== '') {
            $like = '%' . $search . '%';

            $conditions[] = "
                (
                    a.nombre_artistico LIKE :artistic_name
                    OR a.nombre LIKE :first_name
                    OR a.apellidos LIKE :last_name
                    OR a.biografia LIKE :biography
                    OR EXISTS (
                        SELECT 1
                        FROM artistas_especialidades search_specialty
                        INNER JOIN categorias_tatuajes search_category
                            ON search_category.id_categoria =
                               search_specialty.id_categoria
                        WHERE search_specialty.id_artista = a.id_artista
                          AND search_category.nombre LIKE :specialty_name
                    )
                )
            ";

            $parameters['artistic_name'] = $like;
            $parameters['first_name'] = $like;
            $parameters['last_name'] = $like;
            $parameters['biography'] = $like;
            $parameters['specialty_name'] = $like;
        }

        if ($categoryId !== null) {
            $conditions[] = "
                EXISTS (
                    SELECT 1
                    FROM artistas_especialidades filter_specialty
                    WHERE filter_specialty.id_artista = a.id_artista
                      AND filter_specialty.id_categoria = :category_id
                )
            ";

            $parameters['category_id'] = $categoryId;
        }

        $sql = "
            SELECT
                a.id_artista,
                a.nombre_artistico,
                a.nombre,
                a.apellidos,
                a.biografia,
                a.foto_url,
                a.slug,
                a.instagram_url,
                a.sitio_web_url,

                COALESCE(
                    NULLIF(a.nombre_artistico, ''),
                    CONCAT_WS(' ', a.nombre, a.apellidos)
                ) AS nombre_publico,

                specialties.especialidades,

                COALESCE(
                    ratings.cantidad_calificaciones,
                    0
                ) AS cantidad_calificaciones,

                COALESCE(
                    ratings.promedio,
                    0
                ) AS promedio,

                (
                    SELECT COUNT(*)
                    FROM tatuajes_realizados work
                    WHERE work.id_artista = a.id_artista
                      AND work.publicado = TRUE
                      AND work.autorizacion_publicacion = TRUE
                ) AS total_trabajos,

                COALESCE(
                    NULLIF(a.foto_url, ''),
                    (
                        SELECT image.imagen_url
                        FROM tatuajes_realizados tattoo
                        INNER JOIN imagenes_tatuajes image
                            ON image.id_tatuaje_realizado =
                               tattoo.id_tatuaje_realizado
                        WHERE tattoo.id_artista = a.id_artista
                          AND tattoo.publicado = TRUE
                          AND tattoo.autorizacion_publicacion = TRUE
                        ORDER BY
                            image.es_portada DESC,
                            tattoo.fecha_realizacion DESC,
                            image.orden,
                            image.id_imagen
                        LIMIT 1
                    )
                ) AS imagen_principal

            FROM artistas a

            LEFT JOIN (
                SELECT
                    ae.id_artista,
                    GROUP_CONCAT(
                        DISTINCT ct.nombre
                        ORDER BY ct.nombre
                        SEPARATOR '||'
                    ) AS especialidades
                FROM artistas_especialidades ae
                INNER JOIN categorias_tatuajes ct
                    ON ct.id_categoria = ae.id_categoria
                   AND ct.activo = TRUE
                GROUP BY ae.id_artista
            ) specialties
                ON specialties.id_artista = a.id_artista

            LEFT JOIN vista_calificaciones_artistas ratings
                ON ratings.id_artista = a.id_artista

            WHERE " . implode(' AND ', $conditions) . "

            ORDER BY
                cantidad_calificaciones DESC,
                promedio DESC,
                nombre_publico
        ";

        $query = $this->database->prepare($sql);
        $query->execute($parameters);

        return $query->fetchAll();
    }

    public function findByReference(string $reference): ?array
    {
        $isId = ctype_digit($reference) && (int) $reference > 0;

        $condition = $isId
            ? 'a.id_artista = :reference'
            : 'a.slug = :reference';

        $query = $this->database->prepare(
            "SELECT
                a.id_artista,
                a.nombre_artistico,
                a.nombre,
                a.apellidos,
                a.telefono,
                a.biografia,
                a.foto_url,
                a.slug,
                a.instagram_url,
                a.sitio_web_url,

                COALESCE(
                    NULLIF(a.nombre_artistico, ''),
                    CONCAT_WS(' ', a.nombre, a.apellidos)
                ) AS nombre_publico,

                specialties.especialidades,

                COALESCE(
                    ratings.cantidad_calificaciones,
                    0
                ) AS cantidad_calificaciones,

                COALESCE(ratings.promedio, 0) AS promedio,

                (
                    SELECT COUNT(*)
                    FROM tatuajes_realizados work
                    WHERE work.id_artista = a.id_artista
                      AND work.publicado = TRUE
                      AND work.autorizacion_publicacion = TRUE
                ) AS total_trabajos

             FROM artistas a

             LEFT JOIN (
                SELECT
                    ae.id_artista,
                    GROUP_CONCAT(
                        DISTINCT ct.nombre
                        ORDER BY ct.nombre
                        SEPARATOR '||'
                    ) AS especialidades
                FROM artistas_especialidades ae
                INNER JOIN categorias_tatuajes ct
                    ON ct.id_categoria = ae.id_categoria
                   AND ct.activo = TRUE
                GROUP BY ae.id_artista
             ) specialties
                ON specialties.id_artista = a.id_artista

             LEFT JOIN vista_calificaciones_artistas ratings
                ON ratings.id_artista = a.id_artista

             WHERE a.activo = TRUE
               AND {$condition}
             LIMIT 1"
        );

        $query->bindValue(
            ':reference',
            $isId ? (int) $reference : $reference,
            $isId ? PDO::PARAM_INT : PDO::PARAM_STR
        );
        $query->execute();

        $artist = $query->fetch();

        return $artist === false ? null : $artist;
    }

    public function specialties(int $artistId): array
    {
        $query = $this->database->prepare(
            "SELECT
                ct.id_categoria,
                ct.nombre,
                ct.descripcion,
                ct.imagen_url
             FROM artistas_especialidades ae
             INNER JOIN categorias_tatuajes ct
                ON ct.id_categoria = ae.id_categoria
             WHERE ae.id_artista = :artist_id
               AND ct.activo = TRUE
             ORDER BY ct.nombre"
        );
        $query->execute(['artist_id' => $artistId]);

        return $query->fetchAll();
    }

    public function certifications(int $artistId): array
    {
        $query = $this->database->prepare(
            "SELECT
                id_certificacion,
                nombre,
                institucion,
                fecha_emision,
                fecha_vencimiento,
                documento_url
             FROM certificaciones_artistas
             WHERE id_artista = :artist_id
             ORDER BY fecha_emision DESC, id_certificacion DESC"
        );
        $query->execute(['artist_id' => $artistId]);

        return $query->fetchAll();
    }

    public function portfolio(int $artistId): array
    {
        $query = $this->database->prepare(
            "SELECT
                tattoo.id_tatuaje_realizado,
                tattoo.titulo,
                tattoo.descripcion,
                tattoo.slug,
                tattoo.fecha_realizacion,
                category.nombre AS categoria,

                (
                    SELECT image.imagen_url
                    FROM imagenes_tatuajes image
                    WHERE image.id_tatuaje_realizado =
                          tattoo.id_tatuaje_realizado
                    ORDER BY
                        image.es_portada DESC,
                        image.orden,
                        image.id_imagen
                    LIMIT 1
                ) AS imagen_url,

                (
                    SELECT image.texto_alternativo
                    FROM imagenes_tatuajes image
                    WHERE image.id_tatuaje_realizado =
                          tattoo.id_tatuaje_realizado
                    ORDER BY
                        image.es_portada DESC,
                        image.orden,
                        image.id_imagen
                    LIMIT 1
                ) AS texto_alternativo

             FROM tatuajes_realizados tattoo
             INNER JOIN categorias_tatuajes category
                ON category.id_categoria = tattoo.id_categoria
             WHERE tattoo.id_artista = :artist_id
               AND tattoo.publicado = TRUE
               AND tattoo.autorizacion_publicacion = TRUE
             ORDER BY
                tattoo.fecha_realizacion DESC,
                tattoo.id_tatuaje_realizado DESC"
        );
        $query->execute(['artist_id' => $artistId]);

        return $query->fetchAll();
    }

    public function reviews(int $artistId): array
    {
        $query = $this->database->prepare(
            "SELECT
                rating.id_calificacion,
                rating.puntuacion,
                rating.comentario,
                rating.fecha,
                CONCAT(
                    client.nombre,
                    ' ',
                    LEFT(client.apellidos, 1),
                    '.'
                ) AS autor
             FROM calificaciones_artistas rating
             INNER JOIN citas appointment
                ON appointment.id_cita = rating.id_cita
             INNER JOIN clientes client
                ON client.id_cliente = appointment.id_cliente
             WHERE appointment.id_artista = :artist_id
               AND rating.estado_publicacion = 'publicado'
               AND rating.comentario IS NOT NULL
               AND TRIM(rating.comentario) <> ''
             ORDER BY rating.fecha DESC, rating.id_calificacion DESC"
        );
        $query->execute(['artist_id' => $artistId]);

        return $query->fetchAll();
    }
}
