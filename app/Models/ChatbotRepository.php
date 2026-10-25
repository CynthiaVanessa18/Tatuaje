<?php
declare(strict_types=1);

final class ChatbotRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function configurations(): array
    {
        $rows = $this->database->query(
            'SELECT clave, valor, tipo_dato FROM configuraciones_chatbot WHERE activo = TRUE'
        )->fetchAll();
        $values = [];
        foreach ($rows as $row) {
            $values[$row['clave']] = match ($row['tipo_dato']) {
                'numero' => (int) $row['valor'],
                'booleano' => filter_var($row['valor'], FILTER_VALIDATE_BOOL),
                'json' => json_decode($row['valor'], true) ?? [],
                default => $row['valor'],
            };
        }

        return $values;
    }

    public function activeKnowledge(): array
    {
        return $this->database->query(
            "SELECT f.id_fuente, f.tipo,
                    COALESCE(NULLIF(f.titulo, ''), p.pregunta, c.titulo) AS titulo,
                    COALESCE(p.respuesta, c.contenido, f.contenido) AS contenido
             FROM fuentes_conocimiento_chatbot f
             LEFT JOIN preguntas_frecuentes p
               ON p.id_pregunta = f.id_pregunta AND p.activo = TRUE
             LEFT JOIN cuidados_tatuaje c
               ON c.id_cuidado = f.id_cuidado AND c.activo = TRUE
             WHERE f.activo = TRUE
               AND ((f.tipo = 'pregunta_frecuente' AND p.id_pregunta IS NOT NULL)
                 OR (f.tipo = 'cuidado_tatuaje' AND c.id_cuidado IS NOT NULL)
                 OR (f.tipo = 'contenido_personalizado' AND f.contenido IS NOT NULL))
             ORDER BY f.id_fuente"
        )->fetchAll();
    }

    public function clientIdForAccount(?int $accountId): ?int
    {
        if ($accountId === null) return null;
        $query = $this->database->prepare('SELECT id_cliente FROM clientes WHERE id_cuenta = ? LIMIT 1');
        $query->execute([$accountId]);
        $clientId = $query->fetchColumn();

        return $clientId === false ? null : (int) $clientId;
    }

    public function conversationForIdentity(?int $clientId, string $visitorId, ?int $preferredId): array
    {
        if ($preferredId !== null) {
            $conversation = $this->ownedConversation($preferredId, $clientId, $visitorId);
            if ($conversation !== null && in_array($conversation['estado'], ['activa', 'escalada'], true)) {
                return $conversation;
            }
        }

        $identitySql = $clientId === null
            ? 'id_cliente IS NULL AND identificador_visitante = :visitor_id'
            : 'id_cliente = :client_id';
        $query = $this->database->prepare(
            "SELECT * FROM conversaciones_chat
             WHERE {$identitySql} AND estado IN ('activa', 'escalada')
             ORDER BY fecha_ultima_actividad DESC, id_conversacion DESC LIMIT 1"
        );
        $query->execute($clientId === null ? ['visitor_id' => $visitorId] : ['client_id' => $clientId]);
        $conversation = $query->fetch();
        if ($conversation !== false) return $conversation;

        $query = $this->database->prepare(
            'INSERT INTO conversaciones_chat (id_cliente, identificador_visitante, canal, estado)
             VALUES (:client_id, :visitor_id, \'web\', \'activa\')'
        );
        $query->execute([
            'client_id' => $clientId,
            'visitor_id' => $clientId === null ? $visitorId : null,
        ]);

        return $this->ownedConversation((int) $this->database->lastInsertId(), $clientId, $visitorId)
            ?? throw new RuntimeException('No fue posible abrir la conversación.');
    }

    public function ownedConversation(int $conversationId, ?int $clientId, string $visitorId): ?array
    {
        $identitySql = $clientId === null
            ? 'id_cliente IS NULL AND identificador_visitante = :visitor_id'
            : 'id_cliente = :client_id';
        $query = $this->database->prepare(
            "SELECT * FROM conversaciones_chat WHERE id_conversacion = :conversation_id AND {$identitySql} LIMIT 1"
        );
        $parameters = ['conversation_id' => $conversationId];
        $parameters[$clientId === null ? 'visitor_id' : 'client_id'] = $clientId ?? $visitorId;
        $query->execute($parameters);
        $conversation = $query->fetch();

        return $conversation === false ? null : $conversation;
    }

    public function messages(int $conversationId, int $limit = 40): array
    {
        $limit = max(1, min(100, $limit));
        $query = $this->database->prepare(
            "SELECT m.id_mensaje, m.rol, m.contenido, m.proveedor, m.fecha,
                    r.fue_util
             FROM mensajes_chat m
             LEFT JOIN retroalimentacion_chatbot r ON r.id_mensaje = m.id_mensaje
             WHERE m.id_conversacion = :conversation_id
             ORDER BY m.id_mensaje DESC LIMIT {$limit}"
        );
        $query->execute(['conversation_id' => $conversationId]);

        return array_reverse($query->fetchAll());
    }

    public function addMessage(
        int $conversationId,
        string $role,
        string $content,
        ?string $provider = null,
        ?string $model = null,
        ?int $duration = null
    ): int {
        $query = $this->database->prepare(
            'INSERT INTO mensajes_chat
                (id_conversacion, rol, contenido, proveedor, modelo, duracion_milisegundos)
             VALUES (:conversation_id, :role, :content, :provider, :model, :duration)'
        );
        $query->execute([
            'conversation_id' => $conversationId,
            'role' => $role,
            'content' => $content,
            'provider' => $provider,
            'model' => $model,
            'duration' => $duration,
        ]);
        $this->database->prepare(
            'UPDATE conversaciones_chat SET fecha_ultima_actividad = CURRENT_TIMESTAMP WHERE id_conversacion = ?'
        )->execute([$conversationId]);

        return (int) $this->database->lastInsertId();
    }

    public function setConversationState(int $conversationId, string $state): void
    {
        $closedAt = $state === 'cerrada' ? 'CURRENT_TIMESTAMP' : 'NULL';
        $query = $this->database->prepare(
            "UPDATE conversaciones_chat
             SET estado = :state, fecha_cierre = {$closedAt}, fecha_ultima_actividad = CURRENT_TIMESTAMP
             WHERE id_conversacion = :conversation_id"
        );
        $query->execute(['state' => $state, 'conversation_id' => $conversationId]);
    }

    public function saveFeedback(int $messageId, ?int $clientId, bool $useful, ?string $comment): void
    {
        $query = $this->database->prepare(
            'INSERT INTO retroalimentacion_chatbot (id_mensaje, id_cliente, fue_util, comentario)
             VALUES (:message_id, :client_id, :useful, :comment)
             ON DUPLICATE KEY UPDATE fue_util = VALUES(fue_util), comentario = VALUES(comentario), id_cliente = VALUES(id_cliente)'
        );
        $query->execute([
            'message_id' => $messageId,
            'client_id' => $clientId,
            'useful' => $useful ? 1 : 0,
            'comment' => $comment,
        ]);
    }

    public function assistantMessageBelongsTo(int $messageId, int $conversationId): bool
    {
        $query = $this->database->prepare(
            "SELECT COUNT(*) FROM mensajes_chat WHERE id_mensaje = ? AND id_conversacion = ? AND rol = 'asistente'"
        );
        $query->execute([$messageId, $conversationId]);

        return (int) $query->fetchColumn() === 1;
    }

    public function adminSummary(): array
    {
        return $this->database->query(
            "SELECT
                (SELECT COUNT(*) FROM conversaciones_chat) AS conversaciones,
                (SELECT COUNT(*) FROM conversaciones_chat WHERE estado = 'activa') AS activas,
                (SELECT COUNT(*) FROM conversaciones_chat WHERE estado = 'escalada') AS escaladas,
                (SELECT COUNT(*) FROM mensajes_chat) AS mensajes,
                (SELECT COUNT(*) FROM fuentes_conocimiento_chatbot WHERE activo = TRUE) AS fuentes_activas,
                (SELECT COUNT(*) FROM retroalimentacion_chatbot WHERE fue_util = TRUE) AS utiles,
                (SELECT COUNT(*) FROM retroalimentacion_chatbot) AS opiniones"
        )->fetch() ?: [];
    }

    public function adminConversations(string $state = '', string $search = ''): array
    {
        $conditions = [];
        $parameters = [];
        if (in_array($state, ['activa', 'cerrada', 'escalada'], true)) {
            $conditions[] = 'cc.estado = :state';
            $parameters['state'] = $state;
        }
        if ($search !== '') {
            $conditions[] = "(CONCAT_WS(' ', cl.nombre, cl.apellidos) LIKE :search OR cu.correo LIKE :search OR CAST(cc.id_conversacion AS CHAR) = :exact)";
            $parameters['search'] = '%' . $search . '%';
            $parameters['exact'] = $search;
        }
        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $query = $this->database->prepare(
            "SELECT cc.*,
                    COALESCE(NULLIF(CONCAT_WS(' ', cl.nombre, cl.apellidos), ''), 'Visitante') AS persona,
                    cu.correo,
                    (SELECT COUNT(*) FROM mensajes_chat m WHERE m.id_conversacion = cc.id_conversacion) AS total_mensajes,
                    (SELECT LEFT(m2.contenido, 150) FROM mensajes_chat m2
                     WHERE m2.id_conversacion = cc.id_conversacion ORDER BY m2.id_mensaje DESC LIMIT 1) AS ultimo_mensaje
             FROM conversaciones_chat cc
             LEFT JOIN clientes cl ON cl.id_cliente = cc.id_cliente
             LEFT JOIN cuentas cu ON cu.id_cuenta = cl.id_cuenta
             {$where}
             ORDER BY (cc.estado = 'escalada') DESC, cc.fecha_ultima_actividad DESC
             LIMIT 100"
        );
        $query->execute($parameters);

        return $query->fetchAll();
    }

    public function adminConversation(int $conversationId): ?array
    {
        $query = $this->database->prepare(
            "SELECT cc.*, COALESCE(NULLIF(CONCAT_WS(' ', cl.nombre, cl.apellidos), ''), 'Visitante') AS persona,
                    cu.correo
             FROM conversaciones_chat cc
             LEFT JOIN clientes cl ON cl.id_cliente = cc.id_cliente
             LEFT JOIN cuentas cu ON cu.id_cuenta = cl.id_cuenta
             WHERE cc.id_conversacion = ? LIMIT 1"
        );
        $query->execute([$conversationId]);
        $conversation = $query->fetch();

        return $conversation === false ? null : $conversation;
    }

    public function adminSources(): array
    {
        return $this->database->query(
            "SELECT f.*,
                    COALESCE(p.pregunta, c.titulo, f.titulo) AS titulo_resuelto,
                    COALESCE(p.respuesta, c.contenido, f.contenido) AS contenido_resuelto
             FROM fuentes_conocimiento_chatbot f
             LEFT JOIN preguntas_frecuentes p ON p.id_pregunta = f.id_pregunta
             LEFT JOIN cuidados_tatuaje c ON c.id_cuidado = f.id_cuidado
             ORDER BY f.activo DESC, f.id_fuente DESC"
        )->fetchAll();
    }

    public function saveCustomSource(?int $sourceId, string $title, string $content, bool $active): int
    {
        if ($sourceId === null) {
            $query = $this->database->prepare(
                "INSERT INTO fuentes_conocimiento_chatbot (tipo, titulo, contenido, activo)
                 VALUES ('contenido_personalizado', :title, :content, :active)"
            );
            $query->execute(['title' => $title, 'content' => $content, 'active' => $active ? 1 : 0]);
            return (int) $this->database->lastInsertId();
        }
        $query = $this->database->prepare(
            "UPDATE fuentes_conocimiento_chatbot
             SET titulo = :title, contenido = :content, activo = :active
             WHERE id_fuente = :source_id AND tipo = 'contenido_personalizado'"
        );
        $query->execute([
            'title' => $title,
            'content' => $content,
            'active' => $active ? 1 : 0,
            'source_id' => $sourceId,
        ]);
        if ($query->rowCount() === 0 && $this->findSource($sourceId) === null) {
            throw new DomainException('La fuente que intentas editar ya no existe.');
        }

        return $sourceId;
    }

    public function findSource(int $sourceId): ?array
    {
        $query = $this->database->prepare('SELECT * FROM fuentes_conocimiento_chatbot WHERE id_fuente = ? LIMIT 1');
        $query->execute([$sourceId]);
        $source = $query->fetch();

        return $source === false ? null : $source;
    }

    public function setSourceActive(int $sourceId, bool $active): void
    {
        $query = $this->database->prepare(
            'UPDATE fuentes_conocimiento_chatbot SET activo = ? WHERE id_fuente = ?'
        );
        $query->execute([$active ? 1 : 0, $sourceId]);
        if ($query->rowCount() === 0 && $this->findSource($sourceId) === null) {
            throw new DomainException('La fuente seleccionada ya no existe.');
        }
    }

    public function updateConfiguration(string $key, string $value): void
    {
        $query = $this->database->prepare(
            "UPDATE configuraciones_chatbot SET valor = :value WHERE clave = :key AND tipo_dato = 'texto'"
        );
        $query->execute(['value' => $value, 'key' => $key]);
    }
}
