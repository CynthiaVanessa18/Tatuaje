<?php
declare(strict_types=1);

final class AdminAppointmentService
{
    private const LOCAL_TIMEZONE = 'America/Costa_Rica';

    public function __construct(
        private AppointmentRepository $repository,
        private AppointmentEmailService $emailService
    ) {
    }

    public function schedule(int $quoteId, int $accountId, array $input): int
    {
        $startValue = is_scalar($input['fecha_hora_inicio'] ?? null)
            ? trim((string) $input['fecha_hora_inicio'])
            : '';
        $start = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i',
            $startValue,
            new DateTimeZone(self::LOCAL_TIMEZONE)
        );

        if ($start === false || $start->format('Y-m-d\TH:i') !== $startValue) {
            throw new DomainException('Selecciona una fecha y hora válidas para la cita.');
        }

        $now = new DateTimeImmutable('now', new DateTimeZone(self::LOCAL_TIMEZONE));

        if ($start <= $now) {
            throw new DomainException('La cita debe programarse en una fecha futura.');
        }

        $hoursValue = is_scalar($input['duracion_horas'] ?? null)
            ? str_replace(',', '.', trim((string) $input['duracion_horas']))
            : '';

        if (!is_numeric($hoursValue) || (float) $hoursValue < 0.25 || (float) $hoursValue > 16) {
            throw new DomainException('La duración de la cita debe estar entre 0.25 y 16 horas.');
        }

        $durationMinutes = (int) round((float) $hoursValue * 60);
        $end = $start->modify('+' . $durationMinutes . ' minutes');

        if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
            throw new DomainException('La cita debe iniciar y terminar el mismo día.');
        }

        $sessionValue = is_scalar($input['numero_sesion'] ?? null)
            ? trim((string) $input['numero_sesion'])
            : '';

        if (!ctype_digit($sessionValue) || (int) $sessionValue < 1 || (int) $sessionValue > 30) {
            throw new DomainException('El número de sesión debe estar entre 1 y 30.');
        }

        $notes = $this->optionalText($input['observaciones_cita'] ?? null, 2000);

        return $this->repository->scheduleFromQuote(
            $quoteId,
            $accountId,
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            (int) $sessionValue,
            $notes
        );
    }

    public function confirm(int $quoteId, int $appointmentId, int $accountId): array
    {
        $appointment = $this->repository->findForAdmin($appointmentId);

        if ($appointment === null) {
            throw new DomainException('La cita ya no existe.');
        }

        if ((int) $appointment['id_cotizacion'] !== $quoteId) {
            throw new DomainException('La cita no pertenece a esta cotización.');
        }

        if ($appointment['estado'] !== 'pendiente') {
            throw new DomainException('Solo una cita pendiente puede confirmarse.');
        }

        $start = new DateTimeImmutable(
            (string) $appointment['fecha_hora_inicio'],
            new DateTimeZone(self::LOCAL_TIMEZONE)
        );

        if ($start <= new DateTimeImmutable('now', new DateTimeZone(self::LOCAL_TIMEZONE))) {
            throw new DomainException('No se puede confirmar una cita cuya hora ya pasó.');
        }

        $email = $this->emailService->compose($appointment);
        $mailId = $this->repository->confirmAndQueueEmail(
            $appointmentId,
            $accountId,
            $email['subject'],
            $email['html'],
            $email['data']
        );

        try {
            $this->emailService->sendQueued($mailId);

            return ['sent' => true, 'message' => 'La cita fue confirmada y el correo se envió al cliente.'];
        } catch (RuntimeException $exception) {
            return ['sent' => false, 'message' => $exception->getMessage()];
        }
    }

    public function retryEmail(int $quoteId, int $appointmentId): array
    {
        $appointment = $this->repository->findForAdmin($appointmentId);

        if ($appointment === null || $appointment['estado'] !== 'confirmada') {
            throw new DomainException('Solo se puede reenviar el correo de una cita confirmada.');
        }

        if ((int) $appointment['id_cotizacion'] !== $quoteId) {
            throw new DomainException('La cita no pertenece a esta cotización.');
        }

        $queued = $this->repository->emailForAppointment($appointmentId);

        if ($queued === null) {
            throw new DomainException('La cita no tiene un correo de confirmación registrado.');
        }

        if ($queued['estado'] === 'enviado') {
            return ['sent' => true, 'message' => 'El correo de esta cita ya fue enviado.'];
        }

        $email = $this->emailService->compose($appointment);
        $this->repository->refreshQueuedEmail(
            (int) $queued['id_correo'],
            $email['subject'],
            $email['html'],
            $email['data']
        );

        try {
            $this->emailService->sendQueued((int) $queued['id_correo']);

            return ['sent' => true, 'message' => 'El correo de confirmación fue enviado al cliente.'];
        } catch (RuntimeException $exception) {
            return ['sent' => false, 'message' => $exception->getMessage()];
        }
    }

    public function cancelPending(int $quoteId, int $appointmentId, int $accountId): void
    {
        $this->repository->cancelPending($quoteId, $appointmentId, $accountId);
    }

    private function optionalText(mixed $value, int $maximum): ?string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';

        if ($text === '') {
            return null;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);

        if ($length > $maximum) {
            throw new DomainException('Las indicaciones de la cita no pueden superar ' . $maximum . ' caracteres.');
        }

        return $text;
    }
}
