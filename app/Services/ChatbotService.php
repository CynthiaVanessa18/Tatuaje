<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/ChatbotRepository.php';

final class ChatbotService
{
    private const MAX_MESSAGE_LENGTH = 700;

    public function __construct(private ChatbotRepository $repository)
    {
    }

    public function identity(?array $account): array
    {
        $_SESSION['chatbot_visitor_id'] ??= bin2hex(random_bytes(24));
        $clientId = $this->repository->clientIdForAccount(
            isset($account['id_cuenta']) ? (int) $account['id_cuenta'] : null
        );

        return [$clientId, (string) $_SESSION['chatbot_visitor_id']];
    }

    public function history(?array $account): array
    {
        [$clientId, $visitorId] = $this->identity($account);
        $preferredId = isset($_SESSION['chatbot_conversation_id'])
            ? (int) $_SESSION['chatbot_conversation_id']
            : null;
        $conversation = $this->repository->conversationForIdentity($clientId, $visitorId, $preferredId);
        $_SESSION['chatbot_conversation_id'] = (int) $conversation['id_conversacion'];
        $config = $this->repository->configurations();

        return [
            'conversation_id' => (int) $conversation['id_conversacion'],
            'state' => $conversation['estado'],
            'welcome' => (string) ($config['mensaje_bienvenida'] ?? 'Hola. ¿Qué deseas conocer del estudio?'),
            'messages' => $this->repository->messages((int) $conversation['id_conversacion']),
        ];
    }

    public function answer(?array $account, string $rawMessage): array
    {
        $message = trim(preg_replace('/\s+/u', ' ', $rawMessage) ?? $rawMessage);
        if ($message === '') throw new DomainException('Escribe un mensaje antes de enviarlo.');
        if ($this->length($message) > self::MAX_MESSAGE_LENGTH) {
            throw new DomainException('El mensaje puede tener hasta ' . self::MAX_MESSAGE_LENGTH . ' caracteres.');
        }

        $startedAt = microtime(true);
        [$clientId, $visitorId] = $this->identity($account);
        $preferredId = isset($_SESSION['chatbot_conversation_id'])
            ? (int) $_SESSION['chatbot_conversation_id']
            : null;
        $conversation = $this->repository->conversationForIdentity($clientId, $visitorId, $preferredId);
        $conversationId = (int) $conversation['id_conversacion'];
        $_SESSION['chatbot_conversation_id'] = $conversationId;
        $this->repository->addMessage($conversationId, 'usuario', $message);

        if ($conversation['estado'] === 'escalada') {
            $reply = [
                'text' => 'Tu conversación ya está marcada para atención personal. Puedes dejar aquí otro detalle y el estudio lo verá en su panel.',
                'action' => null,
                'escalated' => true,
            ];
        } else {
            $reply = $this->buildReply($message, $account);
            if ($reply['escalated']) $this->repository->setConversationState($conversationId, 'escalada');
        }

        $duration = max(1, (int) round((microtime(true) - $startedAt) * 1000));
        $messageId = $this->repository->addMessage(
            $conversationId,
            'asistente',
            $reply['text'],
            'tinta-viva-local',
            'base-conocimiento-v1',
            $duration
        );

        return [
            'message' => [
                'id_mensaje' => $messageId,
                'rol' => 'asistente',
                'contenido' => $reply['text'],
                'fecha' => date('Y-m-d H:i:s'),
                'fue_util' => null,
            ],
            'action' => $reply['action'],
            'state' => $reply['escalated'] ? 'escalada' : 'activa',
        ];
    }

    public function feedback(?array $account, int $messageId, bool $useful, ?string $comment): void
    {
        [$clientId, $visitorId] = $this->identity($account);
        $conversationId = isset($_SESSION['chatbot_conversation_id'])
            ? (int) $_SESSION['chatbot_conversation_id']
            : 0;
        if ($conversationId < 1 || $this->repository->ownedConversation($conversationId, $clientId, $visitorId) === null) {
            throw new DomainException('La conversación ya no está disponible.');
        }
        if (!$this->repository->assistantMessageBelongsTo($messageId, $conversationId)) {
            throw new DomainException('La respuesta seleccionada no pertenece a esta conversación.');
        }
        $comment = $comment === null ? null : trim($comment);
        if ($comment !== null && $this->length($comment) > 500) {
            throw new DomainException('El comentario puede tener hasta 500 caracteres.');
        }
        $this->repository->saveFeedback($messageId, $clientId, $useful, $comment ?: null);
    }

    private function buildReply(string $message, ?array $account): array
    {
        $normalized = $this->normalize($message);
        $config = $this->repository->configurations();

        if ($this->containsAny($normalized, ['humano', 'persona real', 'persona del estudio', 'asesor', 'hablar con el estudio', 'contactar el estudio', 'ayuda personal'])) {
            return [
                'text' => 'He marcado esta conversación para que una persona del estudio pueda continuar contigo. Déjanos cualquier detalle adicional aquí.',
                'action' => null,
                'escalated' => true,
            ];
        }
        if ($this->containsAny($normalized, ['hola', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches'])) {
            return [
                'text' => (string) ($config['mensaje_bienvenida'] ?? 'Hola. Puedo orientarte sobre el estudio.'),
                'action' => ['label' => 'Explorar el estudio', 'url' => 'galeria/'],
                'escalated' => false,
            ];
        }
        if ($this->containsAny($normalized, ['artista', 'tatuador', 'quien tatua', 'especialidad'])) {
            return [
                'text' => 'Cada artista de Tinta Viva tiene un lenguaje propio. En sus perfiles puedes ver biografía, especialidades y trabajos antes de elegir.',
                'action' => ['label' => 'Conocer artistas', 'url' => 'artistas/'],
                'escalated' => false,
            ];
        }
        if ($this->containsAny($normalized, ['galeria', 'trabajos', 'portafolio', 'inspiracion', 'fotos'])) {
            return [
                'text' => 'El archivo visual reúne obras reales por estilo y artista. Puedes recorrerlo para guardar referencias para tu cotización.',
                'action' => ['label' => 'Abrir la galería', 'url' => 'galeria/'],
                'escalated' => false,
            ];
        }
        if ($this->containsAny($normalized, ['testimonio', 'opinion', 'experiencia'])) {
            return [
                'text' => 'Puedes leer experiencias publicadas por clientes. Si ya tienes una cita finalizada y tu sesión está iniciada, también podrás dejar la tuya.',
                'action' => ['label' => 'Ver testimonios', 'url' => 'testimonios/'],
                'escalated' => false,
            ];
        }
        if ($this->containsAny($normalized, ['cotiz', 'presupuesto', 'cuanto cuesta', 'precio del tatuaje', 'costo del tatuaje'])) {
            $source = $this->knowledgeContaining('cotiz');
            return [
                'text' => $source['contenido'] ?? 'Completa el formulario con tu idea, zona del cuerpo, tamaño aproximado, estilo y referencias visuales. El estudio revisará los detalles antes de enviarte una propuesta.',
                'action' => ['label' => 'Solicitar cotización', 'url' => 'cotizaciones/'],
                'escalated' => false,
            ];
        }
        if ($this->containsAny($normalized, ['tienda', 'producto', 'camiseta', 'comprar'])) {
            return [
                'text' => 'La tienda está disponible para clientes con sesión iniciada. Desde tu espacio puedes consultar artículos, promociones y el carrito.',
                'action' => $account === null
                    ? ['label' => 'Iniciar sesión', 'url' => 'auth/login.php']
                    : ['label' => 'Entrar a la tienda', 'url' => 'index.php?section=tienda'],
                'escalated' => false,
            ];
        }
        if ($this->containsAny($normalized, ['cita', 'agenda', 'reservar', 'reservacion', 'horario disponible'])) {
            return [
                'text' => 'La cita definitiva se crea después de aceptar una cotización. El administrador valida el horario del artista y, al confirmarla, el sistema envía los detalles al correo de tu cuenta.',
                'action' => ['label' => 'Solicitar cotización', 'url' => 'cotizaciones/'],
                'escalated' => false,
            ];
        }

        $knowledge = $this->bestKnowledge($normalized, $this->repository->activeKnowledge());
        if ($knowledge !== null) {
            $action = match ($knowledge['tipo']) {
                'cuidado_tatuaje' => ['label' => 'Ver guía de cuidados', 'url' => 'cuidados/'],
                'pregunta_frecuente' => ['label' => 'Ver preguntas frecuentes', 'url' => 'preguntas/'],
                default => null,
            };
            $text = trim((string) $knowledge['contenido']);
            if ($this->containsAny($normalized, ['infeccion', 'infectado', 'pus', 'fiebre', 'dolor intenso', 'muy rojo'])) {
                $text .= ' Si notas fiebre, pus, dolor creciente o enrojecimiento que se extiende, busca valoración de un profesional de salud; el chatbot no realiza diagnósticos.';
            }
            return ['text' => $text, 'action' => $action, 'escalated' => false];
        }

        return [
            'text' => (string) ($config['mensaje_sin_respuesta'] ?? 'No encontré una respuesta segura. Puedo comunicarte con el estudio.'),
            'action' => ['label' => 'Hablar con el estudio', 'prompt' => 'Quiero hablar con una persona del estudio'],
            'escalated' => false,
        ];
    }

    private function bestKnowledge(string $message, array $sources): ?array
    {
        $messageTokens = $this->tokens($message);
        if ($messageTokens === []) return null;
        $careWords = ['cuidado', 'lavar', 'limpieza', 'crema', 'cicatriz', 'sanar', 'proteccion', 'jabon', 'agua', 'sol', 'costra'];
        $quoteWords = ['cotizacion', 'cotizar', 'precio', 'costo', 'presupuesto', 'cuanto'];
        $painWords = ['dolor', 'duele', 'dolera', 'doloroso'];
        $best = null;
        $bestScore = 0;

        foreach ($sources as $source) {
            $titleTokens = $this->tokens($this->normalize((string) $source['titulo']));
            $contentTokens = $this->tokens($this->normalize((string) $source['contenido']));
            $score = 0;
            foreach ($messageTokens as $token) {
                if (in_array($token, $titleTokens, true)) $score += 5;
                elseif (in_array($token, $contentTokens, true)) $score += 1;
            }
            if ($source['tipo'] === 'cuidado_tatuaje' && array_intersect($messageTokens, $careWords)) $score += 5;
            if (str_contains($this->normalize((string) $source['titulo']), 'cotiz') && array_intersect($messageTokens, $quoteWords)) $score += 7;
            if (str_contains($this->normalize((string) $source['titulo']), 'dolor') && array_intersect($messageTokens, $painWords)) $score += 7;
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $source;
            }
        }

        return $bestScore >= 4 ? $best : null;
    }

    private function knowledgeContaining(string $needle): ?array
    {
        foreach ($this->repository->activeKnowledge() as $source) {
            if (str_contains($this->normalize((string) $source['titulo']), $needle)) return $source;
        }
        return null;
    }

    private function tokens(string $value): array
    {
        $stopWords = ['a', 'al', 'algo', 'como', 'con', 'de', 'del', 'el', 'en', 'es', 'la', 'las', 'lo', 'los', 'me', 'mi', 'para', 'por', 'que', 'se', 'si', 'un', 'una', 'y'];
        $tokens = preg_split('/\s+/u', trim($value)) ?: [];

        return array_values(array_unique(array_filter(
            $tokens,
            fn(string $token): bool => $this->length($token) > 2 && !in_array($token, $stopWords, true)
        )));
    }

    private function normalize(string $value): string
    {
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) $value = strtolower($transliterated);

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value);
    }

    private function containsAny(string $value, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($value, $needle)) return true;
        }
        return false;
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
