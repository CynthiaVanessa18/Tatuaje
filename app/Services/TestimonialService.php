<?php
declare(strict_types=1);

final class TestimonialService
{
    public function __construct(private TestimonialRepository $repository)
    {
    }

    public function submit(int $clientId, array $input): int
    {
        $appointmentId = $this->positiveId($input['id_cita'] ?? null);
        $title = $this->text($input['titulo'] ?? '', 180);
        $content = $this->text($input['contenido'] ?? '', 1500);
        $publicName = $this->text($input['nombre_publico'] ?? '', 180);

        if ($appointmentId === null) {
            throw new DomainException('Selecciona la cita sobre la que deseas escribir.');
        }

        if ($this->length($content) < 40) {
            throw new DomainException('Cuéntanos un poco más: el testimonio debe tener al menos 40 caracteres.');
        }

        if ($this->length($publicName) < 2) {
            throw new DomainException('Indica el nombre o seudónimo que deseas mostrar públicamente.');
        }

        return $this->repository->createForAppointment(
            $clientId,
            $appointmentId,
            $title,
            $content,
            $publicName
        );
    }

    public function moderate(array $input): string
    {
        $testimonialId = $this->positiveId($input['id_testimonio'] ?? null);
        $status = is_scalar($input['estado_publicacion'] ?? null)
            ? (string) $input['estado_publicacion']
            : '';
        $featured = ($input['destacado'] ?? '') === '1';

        if ($testimonialId === null || !in_array($status, ['pendiente', 'publicado', 'rechazado'], true)) {
            throw new DomainException('La moderación solicitada no es válida.');
        }

        if ($status !== 'publicado') {
            $featured = false;
        }

        if (!$this->repository->moderate($testimonialId, $status, $featured)) {
            throw new DomainException('El testimonio ya no existe o no cambió de estado.');
        }

        return match ($status) {
            'publicado' => $featured
                ? 'El testimonio fue publicado y destacado.'
                : 'El testimonio fue publicado.',
            'rechazado' => 'El testimonio fue rechazado y permanece fuera del sitio público.',
            default => 'El testimonio volvió a la bandeja de revisión.',
        };
    }

    private function positiveId(mixed $value): ?int
    {
        return is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0
            ? (int) $value
            : null;
    }

    private function text(mixed $value, int $maximum): string
    {
        $text = is_scalar($value) ? trim((string) $value) : '';

        return function_exists('mb_substr')
            ? mb_substr($text, 0, $maximum)
            : substr($text, 0, $maximum);
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}
