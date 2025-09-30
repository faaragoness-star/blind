<?php

declare(strict_types=1);

namespace G3D\AdminOps\Tests\Rbac;

use G3D\AdminOps\Rbac\Capabilities;
use G3D\AdminOps\Rbac\CapabilityGuard;
use PHPUnit\Framework\TestCase;
use Test_Env\Perms;

final class CapabilityGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Perms::denyAll();
    }

    public function testCanReturnsFalseWhenCapabilityMissing(): void
    {
        $guard = new CapabilityGuard();

        self::assertFalse($guard->can(Capabilities::CAP_MANAGE_PUBLICATION));
    }

    public function testCanReturnsTrueWhenCapabilityGranted(): void
    {
        Perms::allowAll();
        $guard = new CapabilityGuard();

        self::assertTrue($guard->can(Capabilities::CAP_MANAGE_PUBLICATION));
    }

    public function testRequireClosureReflectsCapabilityChecks(): void
    {
        $guard = new CapabilityGuard();
        $callback = $guard->require(Capabilities::CAP_MANAGE_PUBLICATION);

        self::assertFalse($callback());

        Perms::allowAll();

        self::assertTrue($callback());
    }
}
