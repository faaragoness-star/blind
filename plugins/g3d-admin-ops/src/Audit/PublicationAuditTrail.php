<?php

declare(strict_types=1);

namespace G3D\AdminOps\Audit;

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
