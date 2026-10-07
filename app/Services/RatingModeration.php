<?php
declare(strict_types=1);

final class RatingModeration
{
    public function __construct(private PDO $db) {}

    public function moderate(array $input): string
    {
        $admin = requireRole('administrador');
        verifyCsrf();

        $id = filter_var(
            $input['id_calificacion'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $decision = $input['decision'] ?? '';
        $category = $input['categoria_motivo'] ?? '';
        $reason = $input['motivo'] ?? '';

        if (!$id || !is_string($decision)
            || !in_array($decision, ['aprobar', 'ocultar', 'pendiente'], true)) {
            throw new DomainException('Acción de moderación inválida.');
        }

        if (!is_string($reason)) {
            throw new DomainException('Motivo inválido.');
        }

        $reason = trim($reason);

        if ($reason === '' || mb_strlen($reason) > 1000) {
            throw new DomainException(
                'Registra un motivo de entre 1 y 1000 caracteres.'
            );
        }

        $categories = [
            'insultos',
            'amenazas',
            'publicidad',
            'datos_personales',
            'contenido_ajeno',
        ];

        if ($decision === 'ocultar'
            && (!is_string($category)
                || !in_array($category, $categories, true))) {
            throw new DomainException(
                'Selecciona el motivo por el que ocultas la calificación.'
            );
        }

        $newState = ['aprobar'=>'publicado', 'ocultar'=>'rechazado', 'pendiente'=>'pendiente'][$decision];

        $this->db->beginTransaction();

        try {
            $query = $this->db->prepare(
                'SELECT estado_publicacion
                 FROM calificaciones_artistas
                 WHERE id_calificacion = ?
                 FOR UPDATE'
            );
            $query->execute([$id]);
            $rating = $query->fetch();

            if (!$rating) {
                throw new DomainException('La calificación no existe.');
            }

            if ($rating['estado_publicacion'] === $newState) {
                throw new DomainException(
                    'La calificación ya tiene ese estado.'
                );
            }

            $this->db->prepare(
                'UPDATE calificaciones_artistas
                 SET estado_publicacion = ?
                 WHERE id_calificacion = ?'
            )->execute([$newState, $id]);

            $detail = json_encode([
                'decision' => $decision,
                'estado_anterior' => $rating['estado_publicacion'],
                'estado_nuevo' => $newState,
                'categoria' => $decision === 'ocultar'
                    ? $category
                    : ($decision === 'aprobar' ? 'aprobacion' : 'revision'),
                'motivo' => $reason,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $this->db->prepare(
                'INSERT INTO auditoria_sistema
                    (id_cuenta, evento, entidad, id_entidad, detalle)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $admin['id_cuenta'],
                'moderacion_calificacion',
                'calificaciones_artistas',
                (string) $id,
                $detail,
            ]);

            $this->db->commit();

            return [
                'aprobar'=>'Calificación aprobada. Motivo registrado.',
                'ocultar'=>'Calificación oculta. Motivo registrado.',
                'pendiente'=>'Calificación pendiente de revisión. Motivo registrado.',
            ][$decision];
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $ex;
        }
    }

    public function history(int $id): array
    {
        return $this->historyListing($id,1)['rows'];
    }

    public function historyListing(int $id, int $page): array
    {
        $count=$this->db->prepare("SELECT COUNT(*) FROM auditoria_sistema WHERE evento='moderacion_calificacion' AND entidad='calificaciones_artistas' AND id_entidad=?");
        $count->execute([(string)$id]); $total=(int)$count->fetchColumn();
        $page=max(1,min($page,max(1,(int)ceil($total/20))));
        $offset=($page-1)*20;
        $query = $this->db->prepare(
            "SELECT a.fecha, a.detalle, c.usuario
             FROM auditoria_sistema a
             LEFT JOIN cuentas c ON c.id_cuenta = a.id_cuenta
             WHERE a.evento = 'moderacion_calificacion'
               AND a.entidad = 'calificaciones_artistas'
               AND a.id_entidad = ?
             ORDER BY a.fecha DESC, a.id_evento DESC
             LIMIT 20 OFFSET $offset"
        );
        $query->execute([(string) $id]);

        return ['rows'=>$query->fetchAll(),'total'=>$total,'page'=>$page];
    }
}
