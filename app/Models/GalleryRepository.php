<?php
declare(strict_types=1);

final class GalleryRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function categories(): array
    {
        return $this->database->query(
            "SELECT
                category.id_categoria,
                category.nombre,
                COUNT(DISTINCT tattoo.id_tatuaje_realizado) AS total_obras
             FROM categorias_tatuajes category
             INNER JOIN tatuajes_realizados tattoo
                ON tattoo.id_categoria = category.id_categoria
               AND tattoo.publicado = TRUE
               AND tattoo.autorizacion_publicacion = TRUE
             INNER JOIN imagenes_tatuajes image
                ON image.id_tatuaje_realizado = tattoo.id_tatuaje_realizado
             WHERE category.activo = TRUE
             GROUP BY category.id_categoria, category.nombre
             ORDER BY category.nombre"
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
                COUNT(DISTINCT tattoo.id_tatuaje_realizado) AS total_obras
             FROM artistas artist
             INNER JOIN tatuajes_realizados tattoo
                ON tattoo.id_artista = artist.id_artista
               AND tattoo.publicado = TRUE
               AND tattoo.autorizacion_publicacion = TRUE
             INNER JOIN imagenes_tatuajes image
                ON image.id_tatuaje_realizado = tattoo.id_tatuaje_realizado
             WHERE artist.activo = TRUE
             GROUP BY
                artist.id_artista,
                artist.slug,
                artist.nombre_artistico,
                artist.nombre,
                artist.apellidos
             ORDER BY nombre_publico"
        )->fetchAll();
    }

    public function search(
        string $search = '',
        ?int $categoryId = null,
        ?int $artistId = null
    ): array {
        $conditions = [
            'tattoo.publicado = TRUE',
            'tattoo.autorizacion_publicacion = TRUE',
            'artist.activo = TRUE',
            'category.activo = TRUE',
        ];
        $parameters = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $conditions[] = "(
                tattoo.titulo LIKE :title
                OR tattoo.descripcion LIKE :description
                OR category.nombre LIKE :category_name
                OR artist.nombre_artistico LIKE :artist_name
                OR artist.nombre LIKE :artist_first_name
                OR artist.apellidos LIKE :artist_last_name
            )";
            $parameters += [
                'title' => $like,
                'description' => $like,
                'category_name' => $like,
                'artist_name' => $like,
                'artist_first_name' => $like,
                'artist_last_name' => $like,
            ];
        }

        if ($categoryId !== null) {
            $conditions[] = 'tattoo.id_categoria = :category_id';
            $parameters['category_id'] = $categoryId;
        }

        if ($artistId !== null) {
            $conditions[] = 'tattoo.id_artista = :artist_id';
            $parameters['artist_id'] = $artistId;
        }

        $query = $this->database->prepare(
            "SELECT
                tattoo.id_tatuaje_realizado,
                tattoo.titulo,
                tattoo.descripcion,
                tattoo.slug,
                tattoo.fecha_realizacion,
                category.id_categoria,
                category.nombre AS categoria,
                artist.id_artista,
                artist.slug AS artista_slug,
                COALESCE(
                    NULLIF(artist.nombre_artistico, ''),
                    CONCAT_WS(' ', artist.nombre, artist.apellidos)
                ) AS artista,
                image.id_imagen,
                image.imagen_url,
                image.descripcion AS imagen_descripcion,
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
             INNER JOIN imagenes_tatuajes image
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
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY
                tattoo.fecha_realizacion DESC,
                tattoo.id_tatuaje_realizado DESC
             LIMIT 120"
        );
        $query->execute($parameters);

        return $query->fetchAll();
    }

    public function summary(): array
    {
        $query = $this->database->query(
            "SELECT
                COUNT(DISTINCT tattoo.id_tatuaje_realizado) AS total_obras,
                COUNT(DISTINCT tattoo.id_artista) AS total_artistas,
                COUNT(DISTINCT tattoo.id_categoria) AS total_estilos
             FROM tatuajes_realizados tattoo
             INNER JOIN imagenes_tatuajes image
                ON image.id_tatuaje_realizado = tattoo.id_tatuaje_realizado
             INNER JOIN artistas artist
                ON artist.id_artista = tattoo.id_artista
               AND artist.activo = TRUE
             INNER JOIN categorias_tatuajes category
                ON category.id_categoria = tattoo.id_categoria
               AND category.activo = TRUE
             WHERE tattoo.publicado = TRUE
               AND tattoo.autorizacion_publicacion = TRUE"
        );

        return $query->fetch() ?: [
            'total_obras' => 0,
            'total_artistas' => 0,
            'total_estilos' => 0,
        ];
    }
}
