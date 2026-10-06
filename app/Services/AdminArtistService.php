<?php
declare(strict_types=1);

final class AdminArtistService
{
    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private AdminArtistRepository $repository,
        private string $projectRoot
    ) {
    }

    public function save(
        array $input,
        array $files,
        ?int $artistId = null
    ): int {
        $existing = $artistId === null
            ? null
            : $this->repository->find($artistId);

        if ($artistId !== null && $existing === null) {
            throw new DomainException('El artista ya no existe.');
        }

        $data = $this->validatedData($input);
        $categoryIds = $this->validatedCategoryIds(
            $input['especialidades'] ?? []
        );

        $newImagePath = null;
        $data['foto_url'] = $existing['foto_url'] ?? null;

        try {
            $newImagePath = $this->storeUploadedPhoto(
                $files['foto'] ?? null,
                $data['slug']
            );

            if ($newImagePath !== null) {
                $data['foto_url'] = $newImagePath;
            }

            return $this->repository->save(
                $data,
                $categoryIds,
                $artistId
            );
        } catch (Throwable $exception) {
            if ($newImagePath !== null) {
                $absolutePath = $this->projectRoot
                    . '/public/'
                    . $newImagePath;

                if (is_file($absolutePath)) {
                    @unlink($absolutePath);
                }
            }

            throw $exception;
        }
    }

    private function validatedData(array $input): array
    {
        $accountId = filter_var(
            $input['id_cuenta'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($accountId === false) {
            throw new DomainException('Selecciona una cuenta de artista.');
        }

        $firstName = $this->requiredText(
            $input['nombre'] ?? null,
            'El nombre',
            100
        );
        $lastName = $this->requiredText(
            $input['apellidos'] ?? null,
            'Los apellidos',
            150
        );
        $artisticName = $this->optionalText(
            $input['nombre_artistico'] ?? null,
            'El nombre artístico',
            120
        );
        $phone = $this->optionalText(
            $input['telefono'] ?? null,
            'El teléfono',
            25
        );
        $biography = $this->optionalText(
            $input['biografia'] ?? null,
            'La biografía',
            5000
        );

        $slugSource = trim((string) ($input['slug'] ?? ''));

        if ($slugSource === '') {
            $slugSource = $artisticName ?: $firstName . '-' . $lastName;
        }

        $slug = $this->slugify($slugSource);

        if ($slug === '' || strlen($slug) > 160) {
            throw new DomainException(
                'El identificador público debe contener letras o números y no superar 160 caracteres.'
            );
        }

        $website = $this->optionalUrl(
            $input['sitio_web_url'] ?? null
        );

        return [
            'id_cuenta' => (int) $accountId,
            'nombre_artistico' => $artisticName,
            'nombre' => $firstName,
            'apellidos' => $lastName,
            'telefono' => $phone,
            'biografia' => $biography,
            'foto_url' => null,
            'slug' => $slug,
            'sitio_web_url' => $website,
            'activo' => isset($input['activo']) ? 1 : 0,
        ];
    }

    private function validatedCategoryIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $categoryIds = [];

        foreach ($value as $categoryId) {
            if (
                !is_scalar($categoryId)
                || !ctype_digit((string) $categoryId)
                || (int) $categoryId < 1
            ) {
                throw new DomainException(
                    'Una de las especialidades seleccionadas no es válida.'
                );
            }

            $categoryIds[] = (int) $categoryId;
        }

        return array_values(array_unique($categoryIds));
    }

    private function storeUploadedPhoto(
        mixed $file,
        string $slug
    ): ?string {
        if (!is_array($file)) {
            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new DomainException(
                'La fotografía no pudo cargarse. Intenta nuevamente.'
            );
        }

        $temporaryPath = $file['tmp_name'] ?? null;
        $size = (int) ($file['size'] ?? 0);

        if (
            !is_string($temporaryPath)
            || !is_uploaded_file($temporaryPath)
            || $size < 1
            || $size > self::MAX_IMAGE_BYTES
        ) {
            throw new DomainException(
                'La fotografía debe ser una imagen válida de hasta 5 MB.'
            );
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!is_string($mimeType) || !isset($extensions[$mimeType])) {
            throw new DomainException(
                'La fotografía debe estar en formato JPG, PNG o WEBP.'
            );
        }

        $dimensions = @getimagesize($temporaryPath);

        if (
            $dimensions === false
            || $dimensions[0] < 300
            || $dimensions[1] < 300
            || $dimensions[0] > 6000
            || $dimensions[1] > 6000
        ) {
            throw new DomainException(
                'La fotografía debe medir entre 300 y 6000 píxeles por lado.'
            );
        }

        $relativeDirectory = 'assets/images/artistas/uploads';
        $absoluteDirectory = $this->projectRoot
            . '/public/'
            . $relativeDirectory;

        if (
            !is_dir($absoluteDirectory)
            && !mkdir($absoluteDirectory, 0755, true)
            && !is_dir($absoluteDirectory)
        ) {
            throw new RuntimeException(
                'No fue posible preparar la carpeta de fotografías.'
            );
        }

        $fileName = $slug
            . '-'
            . bin2hex(random_bytes(8))
            . '.'
            . $extensions[$mimeType];
        $absolutePath = $absoluteDirectory . '/' . $fileName;

        if (!move_uploaded_file($temporaryPath, $absolutePath)) {
            throw new RuntimeException(
                'No fue posible guardar la fotografía.'
            );
        }

        return $relativeDirectory . '/' . $fileName;
    }

    private function requiredText(
        mixed $value,
        string $label,
        int $maximumLength
    ): string {
        $text = $this->text($value);

        if ($text === '') {
            throw new DomainException($label . ' es obligatorio.');
        }

        $this->assertMaximumLength($text, $label, $maximumLength);

        return $text;
    }

    private function optionalText(
        mixed $value,
        string $label,
        int $maximumLength
    ): ?string {
        $text = $this->text($value);

        if ($text === '') {
            return null;
        }

        $this->assertMaximumLength($text, $label, $maximumLength);

        return $text;
    }

    private function optionalUrl(mixed $value): ?string
    {
        $url = $this->text($value);

        if ($url === '') {
            return null;
        }

        if (
            strlen($url) > 1024
            || filter_var($url, FILTER_VALIDATE_URL) === false
            || !in_array(
                strtolower((string) parse_url($url, PHP_URL_SCHEME)),
                ['http', 'https'],
                true
            )
        ) {
            throw new DomainException(
                'El enlace del portafolio debe ser una dirección HTTP o HTTPS válida.'
            );
        }

        return $url;
    }

    private function text(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        return trim(
            preg_replace('/\s+/u', ' ', (string) $value) ?? ''
        );
    }

    private function assertMaximumLength(
        string $value,
        string $label,
        int $maximumLength
    ): void {
        $length = function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8')
            : strlen($value);

        if ($length > $maximumLength) {
            throw new DomainException(
                $label . ' no puede superar '
                . $maximumLength
                . ' caracteres.'
            );
        }
    }

    private function slugify(string $value): string
    {
        $transliterated = function_exists('iconv')
            ? iconv(
                'UTF-8',
                'ASCII//TRANSLIT//IGNORE',
                $value
            )
            : false;

        if ($transliterated !== false) {
            $value = $transliterated;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
