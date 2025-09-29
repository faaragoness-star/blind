<?php

declare(strict_types=1);

namespace G3D\AdminOps\Rbac;

use function current_user_can;

final class CapabilityGuard
{
    public function can(string $capability): bool
    {
        return current_user_can($capability);
    }

    public function require(string $capability): callable
    {
        return function () use ($capability): bool {
            return $this->can($capability);
        };
    }
}
