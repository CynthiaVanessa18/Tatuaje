<?php
declare(strict_types=1);

final class CareRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function published(): array
    {
        return $this->database->query(
            'SELECT id_cuidado, titulo, slug, categoria, contenido, imagen_url, texto_alternativo, orden
             FROM cuidados_tatuaje
             WHERE activo = TRUE
             ORDER BY orden, id_cuidado'
        )->fetchAll();
    }

    public function adminListing(string $search = '', string $status = ''): array
    {
        $conditions = [];
        $parameters = [];

        if ($search !== '') {
            $conditions[] = '(titulo LIKE :search OR categoria LIKE :search OR contenido LIKE :search)';
            $parameters['search'] = '%' . $search . '%';
        }

        if ($status === 'activo') {
            $conditions[] = 'activo = TRUE';
        } elseif ($status === 'oculto') {
            $conditions[] = 'activo = FALSE';
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $query = $this->database->prepare(
            "SELECT * FROM cuidados_tatuaje {$where} ORDER BY activo DESC, orden, id_cuidado"
        );
        $query->execute($parameters);

        return $query->fetchAll();
    }

    public function find(int $careId): ?array
    {
        $query = $this->database->prepare('SELECT * FROM cuidados_tatuaje WHERE id_cuidado = :care_id LIMIT 1');
        $query->execute(['care_id' => $careId]);
        $care = $query->fetch();

        return $care === false ? null : $care;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM cuidados_tatuaje WHERE slug = :slug';
        $parameters = ['slug' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id_cuidado <> :care_id';
            $parameters['care_id'] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $query = $this->database->prepare($sql);
        $query->execute($parameters);

        return $query->fetchColumn() !== false;
    }

    public function save(
        ?int $careId,
        string $title,
        string $slug,
        string $category,
        string $content,
        string $imageUrl,
        string $alternativeText,
        int $order,
        bool $active
    ): int {
        $parameters = [
            'title' => $title,
            'slug' => $slug,
            'category' => $category === '' ? null : $category,
            'content' => $content,
            'image_url' => $imageUrl === '' ? null : $imageUrl,
            'alternative_text' => $alternativeText === '' ? null : $alternativeText,
            'sort_order' => $order,
            'active' => $active ? 1 : 0,
        ];

        if ($careId === null) {
            $query = $this->database->prepare(
                'INSERT INTO cuidados_tatuaje
                    (titulo, slug, categoria, contenido, imagen_url, texto_alternativo, orden, activo)
                 VALUES
                    (:title, :slug, :category, :content, :image_url, :alternative_text, :sort_order, :active)'
            );
            $query->execute($parameters);
            return (int) $this->database->lastInsertId();
        }

        $parameters['care_id'] = $careId;
        $query = $this->database->prepare(
            'UPDATE cuidados_tatuaje
             SET titulo = :title,
                 slug = :slug,
                 categoria = :category,
                 contenido = :content,
                 imagen_url = :image_url,
                 texto_alternativo = :alternative_text,
                 orden = :sort_order,
                 activo = :active
             WHERE id_cuidado = :care_id'
        );
        $query->execute($parameters);

        if ($query->rowCount() === 0 && $this->find($careId) === null) {
            throw new DomainException('La instrucción que intentas editar ya no existe.');
        }

        return $careId;
    }

    public function setActive(int $careId, bool $active): bool
    {
        $query = $this->database->prepare(
            'UPDATE cuidados_tatuaje SET activo = :active WHERE id_cuidado = :care_id'
        );
        $query->execute(['active' => $active ? 1 : 0, 'care_id' => $careId]);

        return $query->rowCount() > 0;
    }

    public function summary(): array
    {
        $summary = $this->database->query(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(activo = TRUE), 0) AS activos,
                    COALESCE(SUM(activo = FALSE), 0) AS ocultos,
                    COUNT(DISTINCT COALESCE(NULLIF(categoria, ""), "General")) AS categorias
             FROM cuidados_tatuaje'
        )->fetch();

        return $summary ?: ['total' => 0, 'activos' => 0, 'ocultos' => 0, 'categorias' => 0];
    }
}
