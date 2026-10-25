<?php
declare(strict_types=1);

final class QuoteValidityPolicy
{
    private const TIMEZONE = 'America/Costa_Rica';
    private const MINIMUM_ADVANCE_DAYS = 7;
    private const RESPONSE_WINDOW_DAYS = 7;
    private const CUTOFF_DAYS_BEFORE_PREFERRED_DATE = 2;

    public function minimumPreferredDate(?DateTimeImmutable $now = null): string
    {
        $localNow = ($now ?? new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE)))
            ->setTimezone(new DateTimeZone(self::TIMEZONE));

        return $localNow->setTime(0, 0)->modify('+' . self::MINIMUM_ADVANCE_DAYS . ' days')->format('Y-m-d');
    }

    public function validatePreferredDate(string $date, ?DateTimeImmutable $now = null): string
    {
        $timezone = new DateTimeZone(self::TIMEZONE);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $timezone);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new DomainException('Selecciona una fecha preferida válida.');
        }

        if ($date < $this->minimumPreferredDate($now)) {
            throw new DomainException('La fecha preferida debe tener al menos siete días de anticipación.');
        }

        return $date;
    }

    public function expirationFor(string $preferredDate, ?DateTimeImmutable $sentAt = null): DateTimeImmutable
    {
        $timezone = new DateTimeZone(self::TIMEZONE);
        $localSentAt = ($sentAt ?? new DateTimeImmutable('now', $timezone))->setTimezone($timezone);
        $preferred = DateTimeImmutable::createFromFormat('!Y-m-d', $preferredDate, $timezone);

        if ($preferred === false || $preferred->format('Y-m-d') !== $preferredDate) {
            throw new DomainException('La cotización necesita una fecha preferida válida.');
        }

        $sevenDaysAfterSending = $localSentAt->modify('+' . self::RESPONSE_WINDOW_DAYS . ' days');
        $beforePreferredDate = $preferred
            ->modify('-' . self::CUTOFF_DAYS_BEFORE_PREFERRED_DATE . ' days')
            ->setTime(23, 59, 59);
        $expiration = $sevenDaysAfterSending < $beforePreferredDate
            ? $sevenDaysAfterSending
            : $beforePreferredDate;

        if ($expiration <= $localSentAt) {
            throw new DomainException('La fecha preferida está demasiado próxima para enviar esta propuesta. Solicita al cliente una nueva fecha.');
        }

        return $expiration->setTimezone(new DateTimeZone('UTC'));
    }
}
