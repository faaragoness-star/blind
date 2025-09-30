<?php

declare(strict_types=1);

namespace G3D\VendorBase\Tests\Routes;

use PHPUnit\Framework\TestCase;

final class HealthRouteRegistrationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        require_once __DIR__ . '/../../plugin.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['g3d_tests_registered_rest_routes'] = [];
    }

    public function testHealthRouteRegistered(): void
    {
        \do_action('rest_api_init');

        self::assertTrue(self::routeExists('g3d/v1', '/health', 'GET'));
    }

    private static function routeExists(string $ns, string $route, string $method): bool
    {
        /**
         * @var list<array{namespace:string,route:string,args:array<string,mixed>}> $routes
         */
        $routes = $GLOBALS['g3d_tests_registered_rest_routes'];

        foreach ($routes as $r) {
            if ($r['namespace'] === $ns && $r['route'] === $route) {
                $methods = $r['args']['methods'] ?? '';

                return \is_string($methods) ? \str_contains($methods, $method) : false;
            }
        }

        return false;
    }
}
