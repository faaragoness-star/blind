<?php

declare(strict_types=1);

namespace G3D\AdminOps\Rbac;

final class CapabilityGuard
{
    public function can(string $capability): bool
    {
        return \function_exists('current_user_can') ? \current_user_can($capability) : true;
    }

    public function require(string $capability): callable
    {
        return function () use ($capability): bool {
            return $this->can($capability);
        };
    }
}
