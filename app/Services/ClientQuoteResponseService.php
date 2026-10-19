<?php
declare(strict_types=1);

final class ClientQuoteResponseService
{
    private const ACTIONS = [
        'aceptar' => 'aceptada',
        'solicitar_cambios' => 'en_revision',
        'rechazar' => 'rechazada',
    ];

    public function __construct(
        private QuoteRepository $repository,
        private ArtistAvailabilityService $availability
    )
    {
    }

    public function respond(
        int $quoteId,
        int $clientId,
        int $accountId,
        mixed $actionValue,
        mixed $messageValue,
        mixed $sessionDatesValue = null
    ): string {
        $action = is_scalar($actionValue) ? trim((string) $actionValue) : '';

        if (!isset(self::ACTIONS[$action])) {
            throw new DomainException('Selecciona una respuesta válida.');
        }

        $message = is_scalar($messageValue) ? trim((string) $messageValue) : '';
        $length = function_exists('mb_strlen') ? mb_strlen($message) : strlen($message);

        if ($length > 1500) {
            throw new DomainException('Tu mensaje no puede superar 1500 caracteres.');
        }

        if ($action === 'solicitar_cambios' && $message === '') {
            throw new DomainException('Cuéntanos qué deseas cambiar en la propuesta.');
        }

        $sessionDates = [];
        if ($action === 'aceptar') {
            $quote = $this->repository->findForClient($quoteId, $clientId);
            $sessionDates = $this->validatedSessionDates($quote, $sessionDatesValue);
        }

        $historyMessage = match ($action) {
            'aceptar' => $message !== '' ? $message : 'El cliente aceptó la cotización.',
            'rechazar' => $message !== '' ? $message : 'El cliente rechazó la cotización.',
            default => $message,
        };

        $this->repository->recordClientResponse(
            $quoteId,
            $clientId,
            $accountId,
            self::ACTIONS[$action],
            $historyMessage,
            $sessionDates
        );

        return match ($action) {
            'aceptar' => 'Aceptaste la cotización y enviaste tus fechas preferidas. El estudio coordinará las horas definitivas.',
            'rechazar' => 'La cotización fue rechazada y el estudio recibió tu respuesta.',
            default => 'Tu solicitud de cambios fue enviada al estudio.',
        };
    }

    public function saveSessionDates(int $quoteId, int $clientId, mixed $sessionDatesValue): string
    {
        $quote = $this->repository->findForClient($quoteId, $clientId);

        if ($quote === null || $quote['estado'] !== 'aceptada') {
            throw new DomainException('Solo puedes completar fechas para una cotización aceptada.');
        }

        $sessionDates = $this->validatedSessionDates($quote, $sessionDatesValue);
        $this->repository->saveSessionPreferences($quoteId, $clientId, $sessionDates);

        return 'Guardamos las fechas preferidas de todas tus sesiones. El estudio ahora podrá asignar las horas definitivas.';
    }

    private function validatedSessionDates(?array $quote, mixed $sessionDatesValue): array
    {
        if ($quote === null || $quote['id_artista'] === null || $quote['duracion_estimada_minutos'] === null) {
            throw new DomainException('La propuesta todavía no tiene artista y duración definidos.');
        }

        $estimatedSessions = max(1, (int) ($quote['sesiones_estimadas'] ?? 1));
        $submittedDates = is_array($sessionDatesValue) ? $sessionDatesValue : [];
        $availableDates = array_keys($this->availability->availableSlotsForArtist(
            (int) $quote['id_artista'],
            (int) $quote['duracion_estimada_minutos'],
            180
        ));
        $sessionDates = [];
        $previousDate = null;

        for ($session = 1; $session <= $estimatedSessions; $session++) {
            $date = is_scalar($submittedDates[$session] ?? null)
                ? trim((string) $submittedDates[$session])
                : '';

            if (!in_array($date, $availableDates, true)) {
                throw new DomainException('Selecciona una fecha disponible para la sesión ' . $session . '.');
            }
            if ($previousDate !== null && $date <= $previousDate) {
                throw new DomainException('Las fechas de las sesiones deben ser diferentes y estar en orden cronológico.');
            }

            $sessionDates[$session] = $date;
            $previousDate = $date;
        }

        return $sessionDates;
    }
}
