<?php

declare(strict_types=1);

namespace G3D\AdminOps\Audit;

use InvalidArgumentException;

final class InMemoryEditorialActionLogger implements AuditLogReader, EditorialActionLogger
{
    /**
     * @var list<
     *   array{
     *     actor_id:string,
     *     action:string,
     *     what:string,
     *     occurred_at:string,
     *     context:array<string,mixed>
     *   }
     * >
     * Registro de acciones "quién/cuándo/qué" en memoria
     * (docs/plugin-5-g3d-admin-ops.md §13).
     */
    private array $events = [];

    public function logAction(string $actorId, string $action, array $context = []): void
    {
        $what = $context['what'] ?? null;
        if (!is_string($what) || $what === '') {
            throw new InvalidArgumentException(
                'Missing context["what"] to registrar "qué" '
                . '(docs/plugin-5-g3d-admin-ops.md §13).'
            );
        }

        $occurredAt = $context['occurred_at'] ?? gmdate('c');
        if (!is_string($occurredAt) || $occurredAt === '') {
            throw new InvalidArgumentException(
                'Invalid occurred_at context '
                . '(docs/Capa 5 — Admin & Operaciones — Addenda Aplicada 2025-09-27.md '
                . '§Auditoría y logs).'
            );
        }

        unset($context['what'], $context['occurred_at']);

        $this->events[] = [
            'actor_id' => $actorId,
            'action' => $action,
            'what' => $what,
            'occurred_at' => $occurredAt,
            'context' => $context,
        ];

        // TODO: persistir 90 días (docs/Capa 5 — Admin & Operaciones —
        // Addenda Aplicada 2025-09-27.md §Auditoría y logs).
    }

    /**
     * @return list<
     *   array{
     *     actor_id:string,
     *     action:string,
     *     what:string,
     *     occurred_at:string,
     *     context:array<string,mixed>
     *   }
     * >
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}
