<?php
declare(strict_types=1);

final class CareService
{
    public function __construct(private CareRepository $repository)
    {
    }

    public function save(array $input): string
    {
        $rawId = filter_var($input['id_cuidado'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $careId = $rawId === false ? null : (int) $rawId;
        $title = trim((string) ($input['titulo'] ?? ''));
        $slug = $this->slug(trim((string) ($input['slug'] ?? '')) ?: $title);
        $category = trim((string) ($input['categoria'] ?? ''));
        $content = trim((string) ($input['contenido'] ?? ''));
        $imageUrl = trim((string) ($input['imagen_url'] ?? ''));
        $alternativeText = trim((string) ($input['texto_alternativo'] ?? ''));
        $order = filter_var($input['orden'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]);
        $active = isset($input['activo']) && (string) $input['activo'] === '1';

        if ($this->length($title) < 4 || $this->length($title) > 180) {
            throw new DomainException('El título debe tener entre 4 y 180 caracteres.');
        }
        if ($category === '' || $this->length($category) > 100) {
            throw new DomainException('Escribe una categoría de hasta 100 caracteres.');
        }
        if ($this->length($content) < 20) {
            throw new DomainException('La instrucción debe contener al menos 20 caracteres.');
        }
        if ($slug === '' || $this->length($slug) > 220) {
            throw new DomainException('No fue posible generar un identificador válido para esta instrucción.');
        }
        if ($this->repository->slugExists($slug, $careId)) {
            throw new DomainException('Ya existe otra instrucción con ese identificador.');
        }
        if ($imageUrl !== '' && $this->length($imageUrl) > 1024) {
            throw new DomainException('La ruta de la imagen es demasiado extensa.');
        }
        if ($this->length($alternativeText) > 255) {
            throw new DomainException('El texto alternativo no puede superar 255 caracteres.');
        }
        if ($order === false) {
            throw new DomainException('El orden debe ser un número entre 0 y 9999.');
        }

        $this->repository->save(
            $careId,
            $title,
            $slug,
            $category,
            $content,
            $imageUrl,
            $alternativeText,
            (int) $order,
            $active
        );

        return $careId === null ? 'La instrucción fue creada correctamente.' : 'La instrucción fue actualizada correctamente.';
    }

    public function toggle(array $input): string
    {
        $careId = filter_var($input['id_cuidado'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($careId === false) {
            throw new DomainException('La instrucción seleccionada no es válida.');
        }

        $active = isset($input['activo']) && (string) $input['activo'] === '1';
        if (!$this->repository->setActive((int) $careId, $active) && $this->repository->find((int) $careId) === null) {
            throw new DomainException('La instrucción seleccionada ya no existe.');
        }

        return $active ? 'La instrucción volvió a publicarse.' : 'La instrucción se ocultó de la guía pública.';
    }

    private function slug(string $value): string
    {
        $converted = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
        $converted = $converted === false ? $value : $converted;
        $converted = strtolower($converted);
        $converted = preg_replace('/[^a-z0-9]+/', '-', $converted) ?? '';
        return trim($converted, '-');
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}
