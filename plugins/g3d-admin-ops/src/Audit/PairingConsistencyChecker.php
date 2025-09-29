<?php

declare(strict_types=1);

namespace G3D\AdminOps\Audit;

interface PairingConsistencyChecker
{
    /**
     * TODO: ver Capa5 §Addenda 2025-09-27.
     */
    public function verifyPair(string $pairId, string $leftModelId, string $rightModelId): void;
}
