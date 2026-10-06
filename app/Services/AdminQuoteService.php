<?php
declare(strict_types=1);

final class AdminQuoteService
{
    private const STATUSES = [
        'solicitada',
        'en_revision',
        'enviada',
        'aceptada',
        'rechazada',
        'vencida',
        'cancelada',
    ];

    public function __construct(private AdminQuoteRepository $repository)
    {
    }

    public function update(int $quoteId, int $accountId, array $input): void
    {
        if ($this->repository->find($quoteId) === null) {
            throw new DomainException('La solicitud ya no existe.');
        }

        $status = is_scalar($input['estado'] ?? null) ? (string) $input['estado'] : '';

        if (!in_array($status, self::STATUSES, true)) {
            throw new DomainException('Selecciona un estado válido.');
        }

        $price = $this->optionalDecimal($input['precio_cotizado'] ?? null, 'El precio cotizado');
        $deposit = $this->decimal($input['anticipo_requerido'] ?? 0, 'El anticipo requerido');

        if ($price !== null && (float) $deposit > (float) $price) {
            throw new DomainException('El anticipo no puede superar el precio cotizado.');
        }

        if (in_array($status, ['enviada', 'aceptada'], true) && $price === null) {
            throw new DomainException('Indica el precio antes de marcar la cotización como enviada o aceptada.');
        }

        $data = [
            'artist_id' => $this->optionalPositiveInteger($input['id_artista'] ?? null, 'El artista'),
            'quoted_price' => $price,
            'required_deposit' => $deposit,
            'estimated_minutes' => $this->optionalHoursToMinutes($input['duracion_estimada_horas'] ?? null),
            'estimated_sessions' => $this->optionalPositiveInteger($input['sesiones_estimadas'] ?? null, 'Las sesiones'),
            'status' => $status,
            'expiration_date' => $this->optionalExpiration($input['fecha_vencimiento'] ?? null),
            'notes' => $this->optionalText($input['observaciones'] ?? null, 'Las observaciones', 5000),
        ];

        if ($data['estimated_sessions'] !== null && $data['estimated_sessions'] > 30) {
            throw new DomainException('Las sesiones estimadas no pueden superar 30.');
        }

        $this->repository->updateResponse($quoteId, $accountId, $data);
    }

    private function optionalPositiveInteger(mixed $value, string $label): ?int
    {
        if ($value === null || (is_scalar($value) && trim((string) $value) === '')) {
            return null;
        }

        if (!is_scalar($value) || !ctype_digit((string) $value) || (int) $value < 1) {
            throw new DomainException($label . ' debe ser un número entero positivo.');
        }

        return (int) $value;
    }

    private function optionalHoursToMinutes(mixed $value): ?int
    {
        if ($value === null || (is_scalar($value) && trim((string) $value) === '')) {
            return null;
        }

        if (!is_scalar($value)) {
            throw new DomainException('La duración debe indicarse en horas.');
        }

        $normalized = str_replace(',', '.', trim((string) $value));

        if (!is_numeric($normalized) || (float) $normalized <= 0 || (float) $normalized > 168) {
            throw new DomainException('La duración debe ser mayor que 0 y no superar 168 horas.');
        }

        return max(1, (int) round((float) $normalized * 60));
    }

    private function decimal(mixed $value, string $label): string
    {
        $decimal = $this->optionalDecimal($value, $label);

        return $decimal ?? '0.00';
    }

    private function optionalDecimal(mixed $value, string $label): ?string
    {
        if ($value === null || (is_scalar($value) && trim((string) $value) === '')) {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));

        if (!is_numeric($normalized) || (float) $normalized < 0 || (float) $normalized > 9999999999.99) {
            throw new DomainException($label . ' no es válido.');
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function optionalExpiration(mixed $value): ?string
    {
        $date = is_scalar($value) ? trim((string) $value) : '';

        if ($date === '') {
            return null;
        }

        $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($dateObject === false || $dateObject->format('Y-m-d') !== $date) {
            throw new DomainException('La fecha de vencimiento no es válida.');
        }

        if ($dateObject < new DateTimeImmutable('today')) {
            throw new DomainException('La fecha de vencimiento no puede estar en el pasado.');
        }

        return $date . ' 23:59:59';
    }

    private function optionalText(mixed $value, string $label, int $maximum): ?string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';

        if ($text === '') {
            return null;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);

        if ($length > $maximum) {
            throw new DomainException($label . ' no puede superar ' . $maximum . ' caracteres.');
        }

        return $text;
    }
}
