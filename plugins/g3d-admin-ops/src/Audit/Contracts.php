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

interface WorkflowTransitionLogger
{
    /**
     * TODO: ver Capa5 §Estados y flujo de trabajo editorial.
     */
    public function logTransition(string $fromState, string $toState, array $diff): void;
}

interface PublicationAuditTrail
{
    /**
     * TODO: ver Capa5 §Versionado y publicación.
     */
    public function snapshotPublished(string $snapshotId, string $versionId): void;

    /**
     * TODO: ver Capa5 §Versionado y publicación.
     */
    public function rollbackTriggered(string $versionId): void;
}

interface ValidatorLogWriter
{
    /**
     * TODO: ver Capa5 §Validadores.
     */
    public function storeValidatorRun(string $snapshotId, array $results): void;
}

interface PairingConsistencyChecker
{
    /**
     * TODO: ver Capa5 §Addenda 2025-09-27.
     */
    public function verifyPair(string $pairId, string $leftModelId, string $rightModelId): void;
}
