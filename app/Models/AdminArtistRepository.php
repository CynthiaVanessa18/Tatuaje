<?php
declare(strict_types=1);

final class AdminArtistRepository
{
    public function __construct(
        private PDO $database
    ) {
    }

    public function listing(string $search = '', string $status = ''): array
    {
        $conditions = [];
        $parameters = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $conditions[] = "(
                a.nombre_artistico LIKE :artistic_name
                OR a.nombre LIKE :first_name
                OR a.apellidos LIKE :last_name
                OR a.slug LIKE :slug
                OR c.usuario LIKE :username
                OR c.correo LIKE :email
            )";

            $parameters = [
                'artistic_name' => $like,
                'first_name' => $like,
                'last_name' => $like,
                'slug' => $like,
                'username' => $like,
                'email' => $like,
            ];
        }

        if (in_array($status, ['activo', 'inactivo'], true)) {
            $conditions[] = 'a.activo = :active';
            $parameters['active'] = $status === 'activo' ? 1 : 0;
        }

        $where = $conditions === []
            ? ''
            : 'WHERE ' . implode(' AND ', $conditions);

        $query = $this->database->prepare(
            "SELECT
                a.id_artista,
                a.id_cuenta,
                a.nombre_artistico,
                a.nombre,
                a.apellidos,
                a.biografia,
                a.foto_url,
                a.slug,
                a.sitio_web_url,
                a.activo,
                c.usuario,
                c.correo,
                specialties.especialidades,
                COALESCE(ratings.promedio, 0) AS promedio,
                COALESCE(ratings.cantidad_calificaciones, 0)
                    AS cantidad_calificaciones,
                (
                    SELECT COUNT(*)
                    FROM tatuajes_realizados work
                    WHERE work.id_artista = a.id_artista
                      AND work.publicado = TRUE
                      AND work.autorizacion_publicacion = TRUE
                ) AS total_trabajos
             FROM artistas a
             INNER JOIN cuentas c
                ON c.id_cuenta = a.id_cuenta
             LEFT JOIN (
                SELECT
                    ae.id_artista,
                    GROUP_CONCAT(
                        DISTINCT ct.nombre
                        ORDER BY ct.nombre
                        SEPARATOR ' · '
                    ) AS especialidades
                FROM artistas_especialidades ae
                INNER JOIN categorias_tatuajes ct
                    ON ct.id_categoria = ae.id_categoria
                GROUP BY ae.id_artista
             ) specialties
                ON specialties.id_artista = a.id_artista
             LEFT JOIN vista_calificaciones_artistas ratings
                ON ratings.id_artista = a.id_artista
             {$where}
             ORDER BY a.activo DESC, a.fecha_actualizacion DESC,
                      a.id_artista DESC"
        );
        $query->execute($parameters);

        return $query->fetchAll();
    }

    public function find(int $artistId): ?array
    {
        $query = $this->database->prepare(
            "SELECT
                a.id_artista,
                a.id_cuenta,
                a.nombre_artistico,
                a.nombre,
                a.apellidos,
                a.telefono,
                a.biografia,
                a.foto_url,
                a.slug,
                a.sitio_web_url,
                a.activo,
                c.usuario,
                c.correo
             FROM artistas a
             INNER JOIN cuentas c
                ON c.id_cuenta = a.id_cuenta
             WHERE a.id_artista = :artist_id
             LIMIT 1"
        );
        $query->execute(['artist_id' => $artistId]);

        $artist = $query->fetch();

        return $artist === false ? null : $artist;
    }

    public function categoryIds(int $artistId): array
    {
        $query = $this->database->prepare(
            'SELECT id_categoria
             FROM artistas_especialidades
             WHERE id_artista = :artist_id'
        );
        $query->execute(['artist_id' => $artistId]);

        return array_map(
            'intval',
            $query->fetchAll(PDO::FETCH_COLUMN)
        );
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

    public function availableAccounts(?int $artistId = null): array
    {
        $query = $this->database->prepare(
            "SELECT
                c.id_cuenta,
                c.usuario,
                c.correo
             FROM cuentas c
             INNER JOIN roles r
                ON r.id_rol = c.id_rol
             LEFT JOIN artistas a
                ON a.id_cuenta = c.id_cuenta
             WHERE r.nombre_rol = 'artista'
               AND r.activo = TRUE
               AND c.estado = 'activo'
               AND (
                    a.id_artista IS NULL
                    OR a.id_artista = :artist_id
               )
             ORDER BY c.usuario"
        );
        $query->bindValue(
            ':artist_id',
            $artistId,
            $artistId === null ? PDO::PARAM_NULL : PDO::PARAM_INT
        );
        $query->execute();

        return $query->fetchAll();
    }

    public function save(
        array $data,
        array $categoryIds,
        ?int $artistId = null
    ): int {
        $this->database->beginTransaction();

        try {
            $this->assertAccountAvailable(
                (int) $data['id_cuenta'],
                $artistId
            );

            if ($artistId === null) {
                $query = $this->database->prepare(
                    'INSERT INTO artistas (
                        id_cuenta,
                        nombre_artistico,
                        nombre,
                        apellidos,
                        telefono,
                        biografia,
                        foto_url,
                        slug,
                        instagram_url,
                        sitio_web_url,
                        activo
                    ) VALUES (
                        :id_cuenta,
                        :nombre_artistico,
                        :nombre,
                        :apellidos,
                        :telefono,
                        :biografia,
                        :foto_url,
                        :slug,
                        NULL,
                        :sitio_web_url,
                        :activo
                    )'
                );
                $query->execute($data);
                $artistId = (int) $this->database->lastInsertId();
            } else {
                $query = $this->database->prepare(
                    'UPDATE artistas
                     SET
                        id_cuenta = :id_cuenta,
                        nombre_artistico = :nombre_artistico,
                        nombre = :nombre,
                        apellidos = :apellidos,
                        telefono = :telefono,
                        biografia = :biografia,
                        foto_url = :foto_url,
                        slug = :slug,
                        instagram_url = NULL,
                        sitio_web_url = :sitio_web_url,
                        activo = :activo
                     WHERE id_artista = :id_artista'
                );
                $query->execute(
                    $data + ['id_artista' => $artistId]
                );
            }

            $delete = $this->database->prepare(
                'DELETE FROM artistas_especialidades
                 WHERE id_artista = :artist_id'
            );
            $delete->execute(['artist_id' => $artistId]);

            if ($categoryIds !== []) {
                $insert = $this->database->prepare(
                    'INSERT INTO artistas_especialidades (
                        id_artista,
                        id_categoria
                    ) VALUES (:artist_id, :category_id)'
                );

                foreach ($categoryIds as $categoryId) {
                    $insert->execute([
                        'artist_id' => $artistId,
                        'category_id' => $categoryId,
                    ]);
                }
            }

            $this->database->commit();

            return $artistId;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    public function setActive(int $artistId, bool $active): void
    {
        $query = $this->database->prepare(
            'UPDATE artistas
             SET activo = :active
             WHERE id_artista = :artist_id'
        );
        $query->execute([
            'active' => $active ? 1 : 0,
            'artist_id' => $artistId,
        ]);

        if ($query->rowCount() === 0 && $this->find($artistId) === null) {
            throw new DomainException('El artista ya no existe.');
        }
    }

    private function assertAccountAvailable(
        int $accountId,
        ?int $artistId
    ): void {
        $query = $this->database->prepare(
            "SELECT c.id_cuenta
             FROM cuentas c
             INNER JOIN roles r
                ON r.id_rol = c.id_rol
             WHERE c.id_cuenta = :account_id
               AND c.estado = 'activo'
               AND r.nombre_rol = 'artista'
               AND r.activo = TRUE
               AND NOT EXISTS (
                    SELECT 1
                    FROM artistas other_artist
                    WHERE other_artist.id_cuenta = c.id_cuenta
                      AND (
                        :artist_id IS NULL
                        OR other_artist.id_artista <> :artist_id_value
                      )
               )
             FOR UPDATE"
        );
        $query->bindValue(':account_id', $accountId, PDO::PARAM_INT);
        $query->bindValue(
            ':artist_id',
            $artistId,
            $artistId === null ? PDO::PARAM_NULL : PDO::PARAM_INT
        );
        $query->bindValue(
            ':artist_id_value',
            $artistId ?? 0,
            PDO::PARAM_INT
        );
        $query->execute();

        if ($query->fetchColumn() === false) {
            throw new DomainException(
                'Selecciona una cuenta activa con rol de artista que no esté vinculada a otro perfil.'
            );
        }
    }
}
