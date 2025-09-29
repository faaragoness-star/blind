<?php

declare(strict_types=1);

namespace G3D\AdminOps\Audit;

interface EditorialActionLogger
{
    /**
     * TODO: ver Capa5 §Auditoría y logs.
     */
    public function logAction(string $actorId, string $action, array $context = []): void;
}
