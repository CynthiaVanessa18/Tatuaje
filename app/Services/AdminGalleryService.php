<?php
declare(strict_types=1);

final class AdminGalleryService
{
    private const MAX_IMAGE_BYTES = 8 * 1024 * 1024;

    public function __construct(
        private AdminGalleryRepository $repository,
        private string $projectRoot
    ) {
    }

    public function save(array $input, array $files, ?int $workId = null): int
    {
        $existing = $workId === null ? null : $this->repository->find($workId);

        if ($workId !== null && $existing === null) {
            throw new DomainException('La obra ya no existe.');
        }

        $data = $this->validatedData($input);
        $newImagePath = null;

        try {
            $newImagePath = $this->storeUploadedImage(
                $files['imagen'] ?? null,
                $data['slug']
            );

            if ($workId === null && $newImagePath === null) {
                throw new DomainException('Selecciona una fotografía para la nueva obra.');
            }

            $image = $newImagePath === null
                ? null
                : [
                    'imagen_url' => $newImagePath,
                    'descripcion' => $this->optionalText(
                        $input['imagen_descripcion'] ?? null,
                        'La descripción de la fotografía',
                        255
                    ),
                    'texto_alternativo' => $this->optionalText(
                        $input['texto_alternativo'] ?? null,
                        'El texto alternativo',
                        255
                    ) ?: 'Tatuaje ' . $data['titulo'],
                ];

            return $this->repository->save($data, $image, $workId);
        } catch (Throwable $exception) {
            if ($newImagePath !== null) {
                $absolutePath = $this->projectRoot . '/public/' . $newImagePath;

                if (is_file($absolutePath)) {
                    @unlink($absolutePath);
                }
            }

            throw $exception;
        }
    }

    private function validatedData(array $input): array
    {
        $clientId = $this->positiveInteger($input['id_cliente'] ?? null, 'Selecciona un cliente.');
        $artistId = $this->positiveInteger($input['id_artista'] ?? null, 'Selecciona un artista.');
        $categoryId = $this->positiveInteger($input['id_categoria'] ?? null, 'Selecciona un estilo.');
        $title = $this->requiredText($input['titulo'] ?? null, 'El título', 180);
        $description = $this->optionalText($input['descripcion'] ?? null, 'La descripción', 5000);

        $slugSource = trim((string) ($input['slug'] ?? '')) ?: $title;
        $slug = $this->slugify($slugSource);

        if ($slug === '' || strlen($slug) > 220) {
            throw new DomainException('El identificador público de la obra no es válido.');
        }

        $date = is_scalar($input['fecha_realizacion'] ?? null)
            ? trim((string) $input['fecha_realizacion'])
            : '';
        $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($dateObject === false || $dateObject->format('Y-m-d') !== $date) {
            throw new DomainException('Selecciona una fecha de realización válida.');
        }

        if ($dateObject > new DateTimeImmutable('today')) {
            throw new DomainException('La fecha de realización no puede estar en el futuro.');
        }

        $authorized = isset($input['autorizacion_publicacion']);
        $published = isset($input['publicado']);

        if ($published && !$authorized) {
            throw new DomainException('Para publicar la obra debes confirmar la autorización del cliente.');
        }

        return [
            'id_cliente' => $clientId,
            'id_artista' => $artistId,
            'id_categoria' => $categoryId,
            'titulo' => $title,
            'descripcion' => $description,
            'slug' => $slug,
            'fecha_realizacion' => $date,
            'autorizacion_publicacion' => $authorized ? 1 : 0,
            'publicado' => $published ? 1 : 0,
        ];
    }

    private function storeUploadedImage(mixed $file, string $slug): ?string
    {
        if (!is_array($file)) {
            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new DomainException('La fotografía no pudo cargarse. Intenta nuevamente.');
        }

        $temporaryPath = $file['tmp_name'] ?? null;
        $size = (int) ($file['size'] ?? 0);

        if (
            !is_string($temporaryPath)
            || !is_uploaded_file($temporaryPath)
            || $size < 1
            || $size > self::MAX_IMAGE_BYTES
        ) {
            throw new DomainException('La fotografía debe ser una imagen válida de hasta 8 MB.');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!is_string($mimeType) || !isset($extensions[$mimeType])) {
            throw new DomainException('La fotografía debe estar en formato JPG, PNG o WEBP.');
        }

        $dimensions = @getimagesize($temporaryPath);

        if (
            $dimensions === false
            || $dimensions[0] < 600
            || $dimensions[1] < 600
            || $dimensions[0] > 7000
            || $dimensions[1] > 7000
        ) {
            throw new DomainException('La fotografía debe medir entre 600 y 7000 píxeles por lado.');
        }

        $relativeDirectory = 'assets/images/galeria/uploads';
        $absoluteDirectory = $this->projectRoot . '/public/' . $relativeDirectory;

        if (
            !is_dir($absoluteDirectory)
            && !mkdir($absoluteDirectory, 0755, true)
            && !is_dir($absoluteDirectory)
        ) {
            throw new RuntimeException('No fue posible preparar la carpeta de la galería.');
        }

        $fileName = $slug . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mimeType];
        $absolutePath = $absoluteDirectory . '/' . $fileName;

        if (!move_uploaded_file($temporaryPath, $absolutePath)) {
            throw new RuntimeException('No fue posible guardar la fotografía.');
        }

        return $relativeDirectory . '/' . $fileName;
    }

    private function positiveInteger(mixed $value, string $message): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($integer === false) {
            throw new DomainException($message);
        }

        return (int) $integer;
    }

    private function requiredText(mixed $value, string $label, int $maximumLength): string
    {
        $text = $this->text($value);

        if ($text === '') {
            throw new DomainException($label . ' es obligatorio.');
        }

        $this->assertMaximumLength($text, $label, $maximumLength);

        return $text;
    }

    private function optionalText(mixed $value, string $label, int $maximumLength): ?string
    {
        $text = $this->text($value);

        if ($text === '') {
            return null;
        }

        $this->assertMaximumLength($text, $label, $maximumLength);

        return $text;
    }

    private function text(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }

    private function assertMaximumLength(string $value, string $label, int $maximumLength): void
    {
        $length = function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8')
            : strlen($value);

        if ($length > $maximumLength) {
            throw new DomainException($label . ' no puede superar ' . $maximumLength . ' caracteres.');
        }
    }

    private function slugify(string $value): string
    {
        $transliterated = function_exists('iconv')
            ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value)
            : false;

        if ($transliterated !== false) {
            $value = $transliterated;
        }

        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)) ?? '', '-');
    }
}
