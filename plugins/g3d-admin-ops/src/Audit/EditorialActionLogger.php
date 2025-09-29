<?php

declare(strict_types=1);

namespace G3D\AdminOps\Audit;

interface EditorialActionLogger
{
    /**
     * Registra "quién/cuándo/qué" de una acción editorial (docs/plugin-5-g3d-admin-ops.md §13).
     *
     * @param array<string,mixed> $context Debe incluir la clave 'what';
     *     opcionalmente 'occurred_at', 'snapshot_id', 'resultado',
     *     'latency_ms' según Capa 3 — Validación, Firma Y Caducidad —
     *     Actualizada (slots Abiertos) — V2 (urls).md §Auditoría.
     */
    public function logAction(string $actorId, string $action, array $context = []): void;
}
