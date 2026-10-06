<?php
declare(strict_types=1);

final class ClientQuoteResponseService
{
    private const ACTIONS = [
        'aceptar' => 'aceptada',
        'solicitar_cambios' => 'en_revision',
        'rechazar' => 'rechazada',
    ];

    public function __construct(private QuoteRepository $repository)
    {
    }

    public function respond(
        int $quoteId,
        int $clientId,
        int $accountId,
        mixed $actionValue,
        mixed $messageValue
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
            $historyMessage
        );

        return match ($action) {
            'aceptar' => 'Aceptaste la cotización. El estudio podrá continuar con la coordinación de tu cita.',
            'rechazar' => 'La cotización fue rechazada y el estudio recibió tu respuesta.',
            default => 'Tu solicitud de cambios fue enviada al estudio.',
        };
    }
}
