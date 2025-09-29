<?php

declare(strict_types=1);

namespace G3D\AdminOps\Tests\Rbac;

use G3D\AdminOps\Rbac\Capabilities;
use G3D\AdminOps\Rbac\CapabilityGuard;
use PHPUnit\Framework\TestCase;

final class CapabilityGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['g3d_admin_ops_allowed_caps'] = [];
    }

    public function testCanReturnsFalseWhenCapabilityMissing(): void
    {
        $guard = new CapabilityGuard();

        self::assertFalse($guard->can(Capabilities::CAP_MANAGE_PUBLICATION));
    }

    public function testCanReturnsTrueWhenCapabilityGranted(): void
    {
        $GLOBALS['g3d_admin_ops_allowed_caps'][] = Capabilities::CAP_MANAGE_PUBLICATION;
        $guard = new CapabilityGuard();

        self::assertTrue($guard->can(Capabilities::CAP_MANAGE_PUBLICATION));
    }

    public function testRequireClosureReflectsCapabilityChecks(): void
    {
        $guard = new CapabilityGuard();
        $callback = $guard->require(Capabilities::CAP_MANAGE_PUBLICATION);

        self::assertFalse($callback());

        $GLOBALS['g3d_admin_ops_allowed_caps'][] = Capabilities::CAP_MANAGE_PUBLICATION;

        self::assertTrue($callback());
    }
}
