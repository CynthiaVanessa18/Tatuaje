<?php
declare(strict_types=1);

final class PublicQuoteService
{
    private const MAX_REFERENCE_COUNT = 3;
    private const MAX_REFERENCE_BYTES = 6 * 1024 * 1024;

    public function __construct(
        private QuoteRepository $repository,
        private string $projectRoot
    ) {
    }

    public function submit(array $input, array $files, int $clientId): int
    {
        if (!empty($input['sitio_web'])) {
            throw new DomainException('No fue posible procesar la solicitud.');
        }

        if (!isset($input['acepta_contacto'])) {
            throw new DomainException('Debes autorizar el contacto para enviar la solicitud.');
        }

        $width = $this->optionalDecimal($input['ancho_cm'] ?? null, 'El ancho');
        $height = $this->optionalDecimal($input['alto_cm'] ?? null, 'El alto');

        if (($width === null) !== ($height === null)) {
            throw new DomainException('Indica tanto el ancho como el alto, o deja ambas medidas vacías.');
        }

        $artistId = $this->optionalPositiveInteger($input['id_artista'] ?? null, 'El artista');
        $categoryId = $this->positiveInteger($input['id_categoria'] ?? null, 'Selecciona un estilo.');
        $preferredDate = $this->optionalDate($input['fecha_preferida'] ?? null);
        $email = $this->requiredText($input['correo_contacto'] ?? null, 'El correo', 254, 5);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('Escribe un correo electrónico válido.');
        }

        $phone = $this->optionalText($input['telefono_contacto'] ?? null, 'El teléfono', 25);

        $data = [
            'client_id' => $clientId,
            'artist_id' => $artistId,
            'category_id' => $categoryId,
            'contact_name' => $this->requiredText($input['nombre_contacto'] ?? null, 'El nombre', 180, 3),
            'contact_email' => strtolower($email),
            'contact_phone' => $phone,
            'preferred_contact_method' => 'correo',
            'idea_description' => $this->requiredText($input['descripcion_idea'] ?? null, 'La historia de tu idea', 5000, 20),
            'body_area' => $this->requiredText($input['zona_cuerpo'] ?? null, 'La zona del cuerpo', 100, 2),
            'width_cm' => $width,
            'height_cm' => $height,
            'size_description' => $this->optionalText($input['tamano_descripcion'] ?? null, 'El tamaño aproximado', 120),
            'in_color' => isset($input['a_color']) ? 1 : 0,
            'preferred_date' => $preferredDate,
        ];

        $storedReferences = [];

        try {
            $storedReferences = $this->storeReferences($files['referencias'] ?? null);
            return $this->repository->create($data, $storedReferences);
        } catch (Throwable $exception) {
            foreach ($storedReferences as $reference) {
                $absolutePath = $this->projectRoot . '/public/' . $reference['imagen_url'];

                if (is_file($absolutePath)) {
                    @unlink($absolutePath);
                }
            }

            throw $exception;
        }
    }

    private function storeReferences(mixed $files): array
    {
        if (!is_array($files) || !is_array($files['name'] ?? null)) {
            return [];
        }

        $stored = [];
        $fileCount = count($files['name']);

        if ($fileCount > self::MAX_REFERENCE_COUNT) {
            throw new DomainException('Puedes adjuntar como máximo tres imágenes de referencia.');
        }

        try {
            for ($index = 0; $index < $fileCount; $index++) {
                $error = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);

                if ($error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($error !== UPLOAD_ERR_OK) {
                    throw new DomainException('Una imagen de referencia no pudo cargarse.');
                }

                $temporaryPath = $files['tmp_name'][$index] ?? null;
                $size = (int) ($files['size'][$index] ?? 0);

                if (
                    !is_string($temporaryPath)
                    || !is_uploaded_file($temporaryPath)
                    || $size < 1
                    || $size > self::MAX_REFERENCE_BYTES
                ) {
                    throw new DomainException('Cada referencia debe ser una imagen válida de hasta 6 MB.');
                }

                $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
                $extensions = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];

                if (!is_string($mimeType) || !isset($extensions[$mimeType])) {
                    throw new DomainException('Las referencias deben estar en formato JPG, PNG o WEBP.');
                }

                $dimensions = @getimagesize($temporaryPath);

                if (
                    $dimensions === false
                    || $dimensions[0] < 300
                    || $dimensions[1] < 300
                    || $dimensions[0] > 7000
                    || $dimensions[1] > 7000
                ) {
                    throw new DomainException('Cada referencia debe medir entre 300 y 7000 píxeles por lado.');
                }

                $relativeDirectory = 'assets/images/cotizaciones/uploads';
                $absoluteDirectory = $this->projectRoot . '/public/' . $relativeDirectory;

                if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
                    throw new RuntimeException('No se pudo preparar el directorio de referencias.');
                }

                $filename = 'referencia-' . bin2hex(random_bytes(12)) . '.' . $extensions[$mimeType];
                $absolutePath = $absoluteDirectory . '/' . $filename;

                if (!move_uploaded_file($temporaryPath, $absolutePath)) {
                    throw new RuntimeException('No se pudo guardar una referencia visual.');
                }

                $stored[] = [
                    'imagen_url' => $relativeDirectory . '/' . $filename,
                    'nombre_original' => $this->safeOriginalName($files['name'][$index] ?? null),
                    'tipo_mime' => $mimeType,
                ];
            }
        } catch (Throwable $exception) {
            foreach ($stored as $reference) {
                $absolutePath = $this->projectRoot . '/public/' . $reference['imagen_url'];

                if (is_file($absolutePath)) {
                    @unlink($absolutePath);
                }
            }

            throw $exception;
        }

        return $stored;
    }

    private function requiredText(mixed $value, string $label, int $maximum, int $minimum = 1): string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';
        $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);

        if ($length < $minimum) {
            throw new DomainException($label . ' debe contener al menos ' . $minimum . ' caracteres.');
        }

        if ($length > $maximum) {
            throw new DomainException($label . ' no puede superar ' . $maximum . ' caracteres.');
        }

        return $text;
    }

    private function optionalText(mixed $value, string $label, int $maximum): ?string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';

        if ($text === '') {
            return null;
        }

        return $this->requiredText($text, $label, $maximum);
    }

    private function positiveInteger(mixed $value, string $message): int
    {
        if (!is_scalar($value) || !ctype_digit((string) $value) || (int) $value < 1) {
            throw new DomainException($message);
        }

        return (int) $value;
    }

    private function optionalPositiveInteger(mixed $value, string $label): ?int
    {
        if ($value === null || (is_scalar($value) && trim((string) $value) === '')) {
            return null;
        }

        return $this->positiveInteger($value, $label . ' seleccionado no es válido.');
    }

    private function optionalDecimal(mixed $value, string $label): ?string
    {
        if ($value === null || (is_scalar($value) && trim((string) $value) === '')) {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));

        if (!is_numeric($normalized) || (float) $normalized <= 0 || (float) $normalized > 250) {
            throw new DomainException($label . ' debe ser una medida válida entre 0 y 250 cm.');
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function optionalDate(mixed $value): ?string
    {
        $date = is_scalar($value) ? trim((string) $value) : '';

        if ($date === '') {
            return null;
        }

        $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($dateObject === false || $dateObject->format('Y-m-d') !== $date) {
            throw new DomainException('Selecciona una fecha preferida válida.');
        }

        if ($dateObject < new DateTimeImmutable('today')) {
            throw new DomainException('La fecha preferida no puede estar en el pasado.');
        }

        return $date;
    }

    private function safeOriginalName(mixed $value): string
    {
        $name = is_scalar($value) ? basename((string) $value) : 'referencia';
        $name = preg_replace('/[^\pL\pN._ -]+/u', '-', $name) ?: 'referencia';

        return function_exists('mb_substr') ? mb_substr($name, 0, 255) : substr($name, 0, 255);
    }
}
