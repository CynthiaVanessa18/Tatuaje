<?php
declare(strict_types=1);

final class AdminAppointmentService
{
    private const LOCAL_TIMEZONE = 'America/Costa_Rica';

    public function __construct(
        private AppointmentRepository $repository,
        private AppointmentEmailService $emailService,
        private ArtistAvailabilityService $availability
    ) {
    }

    public function schedule(int $quoteId, int $accountId, array $input): array
    {
        $quote = $this->repository->quoteSchedulingContext($quoteId);
        if ($quote === null || $quote['estado'] !== 'aceptada' || $quote['id_artista'] === null) {
            throw new DomainException('La cotización debe estar aceptada y tener un artista asignado.');
        }

        $dates = is_array($input['fecha_sesion'] ?? null) ? $input['fecha_sesion'] : [];
        $times = is_array($input['hora_sesion'] ?? null) ? $input['hora_sesion'] : [];
        $durations = is_array($input['duracion_horas'] ?? null) ? $input['duracion_horas'] : [];
        $notes = is_array($input['observaciones_cita'] ?? null) ? $input['observaciones_cita'] : [];
        $expected = max(1, (int) ($quote['sesiones_estimadas'] ?? 1));
        if ((int) ($quote['total_preferencias_sesiones'] ?? 0) < $expected) {
            throw new DomainException('El cliente todavía debe proponer una fecha para cada sesión antes de agendar.');
        }
        $existing = $this->repository->activeSessionNumbers($quoteId);
        $missing = array_values(array_diff(range(1, $expected), $existing));

        if ($missing === []) {
            throw new DomainException('Todas las sesiones de esta cotización ya están agendadas.');
        }

        $sessions = [];
        $now = new DateTimeImmutable('now', new DateTimeZone(self::LOCAL_TIMEZONE));
        $previousStart = null;

        foreach ($missing as $number) {
            $dateValue = is_scalar($dates[$number] ?? null) ? trim((string) $dates[$number]) : '';
            $timeValue = is_scalar($times[$number] ?? null) ? trim((string) $times[$number]) : '';
            $startValue = $dateValue . 'T' . $timeValue;
            $start = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $startValue, new DateTimeZone(self::LOCAL_TIMEZONE));
            $hoursValue = is_scalar($durations[$number] ?? null)
                ? str_replace(',', '.', trim((string) $durations[$number]))
                : '';

            if ($start === false || $start->format('Y-m-d\TH:i') !== $startValue || $start <= $now) {
                throw new DomainException('Selecciona una fecha y hora futuras para la sesión ' . $number . '.');
            }
            if (!is_numeric($hoursValue) || (float) $hoursValue < 0.25 || (float) $hoursValue > 16) {
                throw new DomainException('La duración de la sesión ' . $number . ' debe estar entre 0.25 y 16 horas.');
            }

            $end = $start->modify('+' . (int) round((float) $hoursValue * 60) . ' minutes');
            $this->availability->assertSlotAvailable((int) $quote['id_artista'], $start, $end);

            if ($previousStart !== null && $start <= $previousStart) {
                throw new DomainException('Las sesiones deben programarse en orden cronológico.');
            }

            foreach ($sessions as $scheduled) {
                if ($start < $scheduled['end_object'] && $end > $scheduled['start_object']) {
                    throw new DomainException('Las sesiones nuevas no pueden superponerse entre sí.');
                }
            }

            $sessions[] = [
                'number' => $number,
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
                'start_object' => $start,
                'end_object' => $end,
                'notes' => $this->optionalText($notes[$number] ?? null, 2000),
            ];
            $previousStart = $start;
        }

        return $this->repository->scheduleSessionsFromQuote($quoteId, $accountId, $sessions);
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
