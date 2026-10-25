<?php
declare(strict_types=1);

final class ArtistAvailabilityService
{
    private const TIMEZONE = 'America/Costa_Rica';

    public function __construct(private PDO $database)
    {
    }

    public function availableArtistsOnDate(string $date, int $durationMinutes = 60): array
    {
        $this->assertDate($date);
        $durationMinutes = max(15, $durationMinutes);
        $weekday = (int) (new DateTimeImmutable($date, new DateTimeZone(self::TIMEZONE)))->format('N');
        $query = $this->database->prepare(
            "SELECT
                artist.id_artista,
                artist.slug,
                COALESCE(NULLIF(artist.nombre_artistico, ''), CONCAT_WS(' ', artist.nombre, artist.apellidos)) AS nombre_publico,
                GROUP_CONCAT(DISTINCT category.nombre ORDER BY category.nombre SEPARATOR ' · ') AS especialidades
             FROM artistas artist
             INNER JOIN horarios_artistas schedule
                ON schedule.id_artista = artist.id_artista
               AND schedule.activo = TRUE
               AND schedule.dia_semana = :weekday
             LEFT JOIN artistas_especialidades specialty ON specialty.id_artista = artist.id_artista
             LEFT JOIN categorias_tatuajes category ON category.id_categoria = specialty.id_categoria
             WHERE artist.activo = TRUE
             GROUP BY artist.id_artista
             ORDER BY nombre_publico"
        );
        $query->execute(['weekday' => $weekday]);

        return array_values(array_filter(
            $query->fetchAll(),
            fn(array $artist): bool => $this->hasFreeSlot((int) $artist['id_artista'], $date, $durationMinutes)
        ));
    }

    public function assertArtistHasFreeSlot(int $artistId, string $date, int $durationMinutes): void
    {
        $this->assertDate($date);

        if (!$this->hasFreeSlot($artistId, $date, $durationMinutes)) {
            throw new DomainException('El artista seleccionado no tiene un espacio continuo disponible ese día para la duración indicada.');
        }
    }

    public function assertSlotAvailable(int $artistId, DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        if ($end <= $start || $start->format('Y-m-d') !== $end->format('Y-m-d')) {
            throw new DomainException('La cita debe comenzar y terminar el mismo día.');
        }

        $query = $this->database->prepare(
            "SELECT EXISTS(
                SELECT 1
                FROM horarios_artistas
                WHERE id_artista = :artist_id
                  AND activo = TRUE
                  AND dia_semana = :weekday
                  AND hora_inicio <= :start_time
                  AND hora_fin >= :end_time
            )"
        );
        $query->execute([
            'artist_id' => $artistId,
            'weekday' => (int) $start->format('N'),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
        ]);

        if (!(bool) $query->fetchColumn()) {
            throw new DomainException('Ese intervalo está fuera del horario laboral activo del artista.');
        }

        $conflict = $this->database->prepare(
            "SELECT EXISTS(
                SELECT 1 FROM bloqueos_agenda
                WHERE id_artista = :block_artist
                  AND activo = TRUE
                  AND fecha_hora_inicio < :block_end
                  AND fecha_hora_fin > :block_start
                UNION ALL
                SELECT 1 FROM citas
                WHERE id_artista = :appointment_artist
                  AND estado NOT IN ('cancelada', 'no_asistio')
                  AND fecha_hora_inicio < :appointment_end
                  AND fecha_hora_fin > :appointment_start
            )"
        );
        $conflict->execute([
            'block_artist' => $artistId,
            'block_start' => $start->format('Y-m-d H:i:s'),
            'block_end' => $end->format('Y-m-d H:i:s'),
            'appointment_artist' => $artistId,
            'appointment_start' => $start->format('Y-m-d H:i:s'),
            'appointment_end' => $end->format('Y-m-d H:i:s'),
        ]);

        if ((bool) $conflict->fetchColumn()) {
            throw new DomainException('El artista ya tiene una cita o un bloqueo durante ese intervalo.');
        }
    }

    public function availableSlotsForArtist(
        int $artistId,
        int $durationMinutes,
        int $days = 120,
        int $stepMinutes = 30
    ): array {
        $timezone = new DateTimeZone(self::TIMEZONE);
        $now = new DateTimeImmutable('now', $timezone);
        $firstDate = $now->setTime(0, 0);
        $lastDate = $firstDate->modify('+' . max(1, $days) . ' days');
        $durationMinutes = max(15, $durationMinutes);
        $stepMinutes = max(15, $stepMinutes);

        $scheduleQuery = $this->database->prepare(
            'SELECT dia_semana, hora_inicio, hora_fin FROM horarios_artistas
             WHERE id_artista = :artist_id AND activo = TRUE
             ORDER BY dia_semana, hora_inicio'
        );
        $scheduleQuery->execute(['artist_id' => $artistId]);
        $schedule = $scheduleQuery->fetchAll();

        $busyQuery = $this->database->prepare(
            "SELECT fecha_hora_inicio, fecha_hora_fin FROM bloqueos_agenda
             WHERE id_artista = :block_artist AND activo = TRUE
               AND fecha_hora_inicio < :block_end AND fecha_hora_fin >= :block_start
             UNION ALL
             SELECT fecha_hora_inicio, fecha_hora_fin FROM citas
             WHERE id_artista = :appointment_artist
               AND estado NOT IN ('cancelada', 'no_asistio')
               AND fecha_hora_inicio < :appointment_end AND fecha_hora_fin >= :appointment_start
             ORDER BY fecha_hora_inicio"
        );
        $busyQuery->execute([
            'block_artist' => $artistId,
            'block_start' => $firstDate->format('Y-m-d H:i:s'),
            'block_end' => $lastDate->modify('+1 day')->format('Y-m-d H:i:s'),
            'appointment_artist' => $artistId,
            'appointment_start' => $firstDate->format('Y-m-d H:i:s'),
            'appointment_end' => $lastDate->modify('+1 day')->format('Y-m-d H:i:s'),
        ]);
        $busy = array_map(
            static fn(array $period): array => [
                'start' => new DateTimeImmutable((string) $period['fecha_hora_inicio'], $timezone),
                'end' => new DateTimeImmutable((string) $period['fecha_hora_fin'], $timezone),
            ],
            $busyQuery->fetchAll()
        );

        $slots = [];
        for ($offset = 0; $offset <= $days; $offset++) {
            $date = $firstDate->modify('+' . $offset . ' days');
            $weekday = (int) $date->format('N');

            foreach ($schedule as $interval) {
                if ((int) $interval['dia_semana'] !== $weekday) {
                    continue;
                }

                $cursor = new DateTimeImmutable($date->format('Y-m-d') . ' ' . $interval['hora_inicio'], $timezone);
                $intervalEnd = new DateTimeImmutable($date->format('Y-m-d') . ' ' . $interval['hora_fin'], $timezone);

                while ($cursor->modify('+' . $durationMinutes . ' minutes') <= $intervalEnd) {
                    $end = $cursor->modify('+' . $durationMinutes . ' minutes');
                    $conflict = false;

                    foreach ($busy as $period) {
                        if ($cursor < $period['end'] && $end > $period['start']) {
                            $conflict = true;
                            break;
                        }
                    }

                    if (!$conflict && $cursor > $now) {
                        $slots[$date->format('Y-m-d')][] = $cursor->format('H:i');
                    }

                    $cursor = $cursor->modify('+' . $stepMinutes . ' minutes');
                }
            }
        }

        return $slots;
    }

    private function hasFreeSlot(int $artistId, string $date, int $durationMinutes): bool
    {
        $weekday = (int) (new DateTimeImmutable($date, new DateTimeZone(self::TIMEZONE)))->format('N');
        $scheduleQuery = $this->database->prepare(
            'SELECT hora_inicio, hora_fin FROM horarios_artistas
             WHERE id_artista = :artist_id AND dia_semana = :weekday AND activo = TRUE
             ORDER BY hora_inicio'
        );
        $scheduleQuery->execute(['artist_id' => $artistId, 'weekday' => $weekday]);
        $intervals = $scheduleQuery->fetchAll();

        if ($intervals === []) {
            return false;
        }

        $dayStart = $date . ' 00:00:00';
        $dayEnd = (new DateTimeImmutable($date, new DateTimeZone(self::TIMEZONE)))->modify('+1 day')->format('Y-m-d H:i:s');
        $busyQuery = $this->database->prepare(
            "SELECT fecha_hora_inicio, fecha_hora_fin FROM bloqueos_agenda
             WHERE id_artista = :block_artist AND activo = TRUE
               AND fecha_hora_inicio < :block_end AND fecha_hora_fin > :block_start
             UNION ALL
             SELECT fecha_hora_inicio, fecha_hora_fin FROM citas
             WHERE id_artista = :appointment_artist
               AND estado NOT IN ('cancelada', 'no_asistio')
               AND fecha_hora_inicio < :appointment_end AND fecha_hora_fin > :appointment_start
             ORDER BY fecha_hora_inicio"
        );
        $busyQuery->execute([
            'block_artist' => $artistId,
            'block_start' => $dayStart,
            'block_end' => $dayEnd,
            'appointment_artist' => $artistId,
            'appointment_start' => $dayStart,
            'appointment_end' => $dayEnd,
        ]);
        $busy = $busyQuery->fetchAll();

        foreach ($intervals as $interval) {
            $cursor = new DateTimeImmutable($date . ' ' . $interval['hora_inicio'], new DateTimeZone(self::TIMEZONE));
            $intervalEnd = new DateTimeImmutable($date . ' ' . $interval['hora_fin'], new DateTimeZone(self::TIMEZONE));

            foreach ($busy as $period) {
                $busyStart = new DateTimeImmutable((string) $period['fecha_hora_inicio'], new DateTimeZone(self::TIMEZONE));
                $busyEnd = new DateTimeImmutable((string) $period['fecha_hora_fin'], new DateTimeZone(self::TIMEZONE));

                if ($busyEnd <= $cursor || $busyStart >= $intervalEnd) {
                    continue;
                }

                if ($cursor->modify('+' . $durationMinutes . ' minutes') <= $busyStart) {
                    return true;
                }

                if ($busyEnd > $cursor) {
                    $cursor = $busyEnd;
                }
            }

            if ($cursor->modify('+' . $durationMinutes . ' minutes') <= $intervalEnd) {
                return true;
            }
        }

        return false;
    }

    private function assertDate(string $date): void
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone(self::TIMEZONE));

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new DomainException('Selecciona una fecha válida.');
        }
    }
}
