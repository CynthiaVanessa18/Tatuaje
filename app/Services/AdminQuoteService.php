<?php
declare(strict_types=1);

final class AdminQuoteService
{
    public function __construct(
        private AdminQuoteRepository $repository,
        private ArtistAvailabilityService $availability,
        private QuoteValidityPolicy $validityPolicy
    )
    {
    }

    public function update(int $quoteId, int $accountId, array $input): void
    {
        $quote = $this->repository->find($quoteId);

        if ($quote === null) {
            throw new DomainException('La solicitud ya no existe.');
        }

        if (!in_array($quote['estado'], ['solicitada', 'en_revision'], true)) {
            throw new DomainException('La cotización ya fue enviada o respondida; sus datos no se pueden modificar.');
        }

        $price = $this->optionalDecimal($input['precio_cotizado'] ?? null, 'El precio cotizado');
        $deposit = $this->decimal($input['anticipo_requerido'] ?? 0, 'El anticipo requerido');

        if ($price !== null && (float) $deposit > (float) $price) {
            throw new DomainException('El anticipo no puede superar el precio cotizado.');
        }

        if ($price === null) {
            throw new DomainException('Indica el precio antes de enviar la propuesta al cliente.');
        }

        $artistId = $this->optionalPositiveInteger($input['id_artista'] ?? null, 'El artista');
        $estimatedMinutes = $this->optionalHoursToMinutes($input['duracion_estimada_horas'] ?? null);
        $estimatedSessions = $this->optionalPositiveInteger($input['sesiones_estimadas'] ?? null, 'Las sesiones');

        if ($artistId === null || $estimatedMinutes === null || $estimatedSessions === null) {
            throw new DomainException('Asigna artista, duración y cantidad de sesiones antes de enviar la propuesta.');
        }

        if (empty($quote['fecha_preferida'])) {
            throw new DomainException('Esta solicitud antigua no tiene fecha ideal. Pide al cliente una fecha antes de cotizar.');
        }

        $this->availability->assertArtistHasFreeSlot(
            $artistId,
            (string) $quote['fecha_preferida'],
            $estimatedMinutes
        );

        $data = [
            'artist_id' => $artistId,
            'quoted_price' => $price,
            'required_deposit' => $deposit,
            'estimated_minutes' => $estimatedMinutes,
            'estimated_sessions' => $estimatedSessions,
            'status' => 'enviada',
            'expiration_date' => $this->validityPolicy
                ->expirationFor((string) $quote['fecha_preferida'])
                ->format('Y-m-d H:i:s'),
            'notes' => $this->optionalText($input['observaciones'] ?? null, 'Las observaciones', 5000),
        ];

        if ($data['estimated_sessions'] > 30) {
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
