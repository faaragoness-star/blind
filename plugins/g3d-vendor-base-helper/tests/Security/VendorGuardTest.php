<?php

declare(strict_types=1);

namespace G3D\VendorBase\Tests\Security;

use G3D\VendorBase\Security\VendorGuard;
use PHPUnit\Framework\TestCase;

final class VendorGuardTest extends TestCase
{
    public function testAssertReadyPassesInSupportedEnvironment(): void
    {
        VendorGuard::assertReady();

        self::assertTrue(true);
    }
}
