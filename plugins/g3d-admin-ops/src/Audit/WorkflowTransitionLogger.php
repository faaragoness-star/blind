<?php

declare(strict_types=1);

namespace G3D\AdminOps\Audit;

interface WorkflowTransitionLogger
{
    /**
     * TODO: ver Capa5 §Estados y flujo de trabajo editorial.
     */
    public function logTransition(string $fromState, string $toState, array $diff): void;
}
