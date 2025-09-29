<?php

declare(strict_types=1);

namespace G3D\AdminOps\Audit;

interface ValidatorLogWriter
{
    /**
     * TODO: ver Capa5 §Validadores.
     */
    public function storeValidatorRun(string $snapshotId, array $results): void;
}
