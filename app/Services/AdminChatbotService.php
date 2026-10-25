<?php
declare(strict_types=1);

final class AdminChatbotService
{
    public function __construct(private ChatbotRepository $repository)
    {
    }

    public function saveSource(array $input): string
    {
        $rawId = filter_var($input['id_fuente'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $sourceId = $rawId === false ? null : (int) $rawId;
        $title = trim((string) ($input['titulo'] ?? ''));
        $content = trim((string) ($input['contenido'] ?? ''));
        $active = (string) ($input['activo'] ?? '0') === '1';
        if ($this->length($title) < 4 || $this->length($title) > 255) {
            throw new DomainException('El título debe tener entre 4 y 255 caracteres.');
        }
        if ($this->length($content) < 15) {
            throw new DomainException('La respuesta debe contener al menos 15 caracteres.');
        }
        if ($sourceId !== null) {
            $source = $this->repository->findSource($sourceId);
            if ($source === null || $source['tipo'] !== 'contenido_personalizado') {
                throw new DomainException('Solo se pueden editar directamente las fuentes personalizadas.');
            }
        }
        $this->repository->saveCustomSource($sourceId, $title, $content, $active);

        return $sourceId === null ? 'La nueva respuesta ya forma parte del conocimiento del oráculo.' : 'La fuente fue actualizada.';
    }

    public function toggleSource(array $input): string
    {
        $sourceId = filter_var($input['id_fuente'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($sourceId === false) throw new DomainException('La fuente seleccionada no es válida.');
        $active = (string) ($input['activo'] ?? '0') === '1';
        $this->repository->setSourceActive((int) $sourceId, $active);

        return $active ? 'La fuente volvió a alimentar al oráculo.' : 'La fuente fue retirada de las respuestas.';
    }

    public function saveConfiguration(array $input): string
    {
        $welcome = trim((string) ($input['mensaje_bienvenida'] ?? ''));
        $fallback = trim((string) ($input['mensaje_sin_respuesta'] ?? ''));
        $tone = trim((string) ($input['tono_respuesta'] ?? ''));
        foreach ([$welcome, $fallback, $tone] as $value) {
            if ($this->length($value) < 8 || $this->length($value) > 600) {
                throw new DomainException('Los textos de configuración deben tener entre 8 y 600 caracteres.');
            }
        }
        $this->repository->updateConfiguration('mensaje_bienvenida', $welcome);
        $this->repository->updateConfiguration('mensaje_sin_respuesta', $fallback);
        $this->repository->updateConfiguration('tono_respuesta', $tone);

        return 'La voz del oráculo fue actualizada.';
    }

    public function changeConversationState(array $input): string
    {
        $conversationId = filter_var($input['id_conversacion'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $state = (string) ($input['estado'] ?? '');
        if ($conversationId === false || !in_array($state, ['activa', 'cerrada', 'escalada'], true)) {
            throw new DomainException('No fue posible cambiar el estado de la conversación.');
        }
        if ($this->repository->adminConversation((int) $conversationId) === null) {
            throw new DomainException('La conversación ya no existe.');
        }
        $this->repository->setConversationState((int) $conversationId, $state);

        return 'La conversación ahora está ' . $state . '.';
    }

    public function reply(array $input): string
    {
        $conversationId = filter_var($input['id_conversacion'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $content = trim((string) ($input['respuesta'] ?? ''));
        if ($conversationId === false || $this->repository->adminConversation((int) $conversationId) === null) {
            throw new DomainException('La conversación seleccionada no está disponible.');
        }
        if ($this->length($content) < 3 || $this->length($content) > 1500) {
            throw new DomainException('La respuesta debe tener entre 3 y 1500 caracteres.');
        }
        $this->repository->addMessage((int) $conversationId, 'asistente', $content, 'administracion', 'respuesta-humana');
        $this->repository->setConversationState((int) $conversationId, 'activa');

        return 'La respuesta humana quedó disponible en el chat del visitante.';
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
