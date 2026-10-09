<?php
declare(strict_types=1);

final class FaqService
{
    public function __construct(private FaqRepository $repository)
    {
    }

    public function save(array $input): string
    {
        $rawId = filter_var($input['id_pregunta'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $faqId = $rawId === false ? null : (int) $rawId;
        $category = trim((string) ($input['categoria'] ?? ''));
        $question = trim((string) ($input['pregunta'] ?? ''));
        $answer = trim((string) ($input['respuesta'] ?? ''));
        $order = filter_var($input['orden'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]);
        $active = isset($input['activo']) && (string) $input['activo'] === '1';

        if ($category === '') {
            throw new DomainException('Escribe una categoría para organizar la pregunta.');
        }
        if ($this->length($category) > 100) {
            throw new DomainException('La categoría no puede superar 100 caracteres.');
        }
        if ($this->length($question) < 10 || $this->length($question) > 500) {
            throw new DomainException('La pregunta debe tener entre 10 y 500 caracteres.');
        }
        if ($this->length($answer) < 20) {
            throw new DomainException('La respuesta debe contener al menos 20 caracteres.');
        }
        if ($order === false) {
            throw new DomainException('El orden debe ser un número entre 0 y 9999.');
        }

        $this->repository->save($faqId, $category, $question, $answer, (int) $order, $active);

        return $faqId === null ? 'La pregunta fue creada correctamente.' : 'La pregunta fue actualizada correctamente.';
    }

    public function toggle(array $input): string
    {
        $faqId = filter_var($input['id_pregunta'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($faqId === false) {
            throw new DomainException('La pregunta seleccionada no es válida.');
        }

        $active = isset($input['activo']) && (string) $input['activo'] === '1';
        if (!$this->repository->setActive((int) $faqId, $active) && $this->repository->find((int) $faqId) === null) {
            throw new DomainException('La pregunta seleccionada ya no existe.');
        }

        return $active ? 'La pregunta volvió a publicarse.' : 'La pregunta se ocultó del sitio público.';
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}
