<?php
declare(strict_types=1);

final class FaqRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function published(): array
    {
        return $this->database->query(
            "SELECT id_pregunta, categoria, pregunta, respuesta, orden
             FROM preguntas_frecuentes
             WHERE activo = TRUE
             ORDER BY COALESCE(NULLIF(categoria, ''), 'General'), orden, id_pregunta"
        )->fetchAll();
    }

    public function categories(bool $onlyActive = false): array
    {
        $where = $onlyActive ? 'WHERE activo = TRUE' : '';
        $query = $this->database->query(
            "SELECT COALESCE(NULLIF(categoria, ''), 'General') AS categoria, COUNT(*) AS total
             FROM preguntas_frecuentes
             {$where}
             GROUP BY COALESCE(NULLIF(categoria, ''), 'General')
             ORDER BY categoria"
        );

        return $query->fetchAll();
    }

    public function adminListing(string $search = '', string $category = ''): array
    {
        $conditions = [];
        $parameters = [];

        if ($search !== '') {
            $conditions[] = '(pregunta LIKE :search OR respuesta LIKE :search)';
            $parameters['search'] = '%' . $search . '%';
        }

        if ($category !== '') {
            $conditions[] = "COALESCE(NULLIF(categoria, ''), 'General') = :category";
            $parameters['category'] = $category;
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $query = $this->database->prepare(
            "SELECT *
             FROM preguntas_frecuentes
             {$where}
             ORDER BY activo DESC, orden, categoria, id_pregunta"
        );
        $query->execute($parameters);

        return $query->fetchAll();
    }

    public function find(int $faqId): ?array
    {
        $query = $this->database->prepare(
            'SELECT * FROM preguntas_frecuentes WHERE id_pregunta = :faq_id LIMIT 1'
        );
        $query->execute(['faq_id' => $faqId]);
        $faq = $query->fetch();

        return $faq === false ? null : $faq;
    }

    public function save(?int $faqId, string $category, string $question, string $answer, int $order, bool $active): int
    {
        if ($faqId === null) {
            $query = $this->database->prepare(
                'INSERT INTO preguntas_frecuentes (categoria, pregunta, respuesta, orden, activo)
                 VALUES (:category, :question, :answer, :sort_order, :active)'
            );
            $query->execute([
                'category' => $category === '' ? null : $category,
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $order,
                'active' => $active ? 1 : 0,
            ]);

            return (int) $this->database->lastInsertId();
        }

        $query = $this->database->prepare(
            'UPDATE preguntas_frecuentes
             SET categoria = :category,
                 pregunta = :question,
                 respuesta = :answer,
                 orden = :sort_order,
                 activo = :active
             WHERE id_pregunta = :faq_id'
        );
        $query->execute([
            'category' => $category === '' ? null : $category,
            'question' => $question,
            'answer' => $answer,
            'sort_order' => $order,
            'active' => $active ? 1 : 0,
            'faq_id' => $faqId,
        ]);

        if ($query->rowCount() === 0 && $this->find($faqId) === null) {
            throw new DomainException('La pregunta que intentas editar ya no existe.');
        }

        return $faqId;
    }

    public function setActive(int $faqId, bool $active): bool
    {
        $query = $this->database->prepare(
            'UPDATE preguntas_frecuentes SET activo = :active WHERE id_pregunta = :faq_id'
        );
        $query->execute([
            'active' => $active ? 1 : 0,
            'faq_id' => $faqId,
        ]);

        return $query->rowCount() > 0;
    }

    public function summary(): array
    {
        $summary = $this->database->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(activo = TRUE), 0) AS activas,
                    COALESCE(SUM(activo = FALSE), 0) AS ocultas,
                    COUNT(DISTINCT COALESCE(NULLIF(categoria, ''), 'General')) AS categorias
             FROM preguntas_frecuentes"
        )->fetch();

        return $summary ?: ['total' => 0, 'activas' => 0, 'ocultas' => 0, 'categorias' => 0];
    }
}
