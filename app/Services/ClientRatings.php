<?php
declare(strict_types=1);

final class ClientRatings
{
    public function __construct(private PDO $db, private int $accountId) {}

    public function appointments(): array
    {
        $query = $this->db->prepare("SELECT c.id_cita, c.fecha_hora_inicio,
            COALESCE(NULLIF(a.nombre_artistico, ''), CONCAT_WS(' ', a.nombre, a.apellidos)) AS artista,
            r.id_calificacion, r.puntuacion, r.comentario, r.estado_publicacion
            FROM citas c JOIN clientes cl ON cl.id_cliente=c.id_cliente
            JOIN artistas a ON a.id_artista=c.id_artista
            LEFT JOIN calificaciones_artistas r ON r.id_cita=c.id_cita
            WHERE cl.id_cuenta=? AND c.estado='finalizada'
            ORDER BY (r.id_calificacion IS NULL) DESC, c.fecha_hora_inicio DESC, c.id_cita DESC");
        $query->execute([$this->accountId]);
        return $query->fetchAll();
    }

    public function submit(array $input): void
    {
        $appointmentId = filter_var($input['id_cita'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $score = filter_var($input['puntuacion'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>5]]);
        if (!$appointmentId || !$score) throw new DomainException('Selecciona una cita válida y una puntuación de 1 a 5 estrellas.');
        if (!is_string($input['comentario'] ?? '')) throw new DomainException('El comentario no es válido.');
        $comment = trim($input['comentario'] ?? '');
        if ((function_exists('mb_strlen') ? mb_strlen($comment) : strlen($comment)) > 1500) {
            throw new DomainException('El comentario debe tener como máximo 1500 caracteres.');
        }
        $this->db->beginTransaction();
        try {
            $query=$this->db->prepare("SELECT c.id_cita FROM citas c
                JOIN clientes cl ON cl.id_cliente=c.id_cliente
                WHERE c.id_cita=? AND cl.id_cuenta=? AND c.estado='finalizada' FOR UPDATE");
            $query->execute([$appointmentId,$this->accountId]);
            if ($query->fetchColumn() === false) throw new DomainException('Solo puedes calificar tus propias citas finalizadas.');
            $query=$this->db->prepare('SELECT id_calificacion FROM calificaciones_artistas WHERE id_cita=?');
            $query->execute([$appointmentId]);
            if ($query->fetchColumn() !== false) throw new DomainException('Ya calificaste esta cita.');
            $query=$this->db->prepare("INSERT INTO calificaciones_artistas (id_cita,puntuacion,comentario,estado_publicacion) VALUES (?,?,?,'pendiente')");
            $query->execute([$appointmentId,$score,$comment === '' ? null : $comment]);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
