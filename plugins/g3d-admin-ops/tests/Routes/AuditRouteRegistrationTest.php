<?php

declare(strict_types=1);

namespace G3D\AdminOps\Tests\Routes;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';

final class AuditRouteRegistrationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        require_once __DIR__ . '/../../plugin.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        /**
         * @var list<array{namespace:string,route:string,args:array<string,mixed>}> $GLOBALS['g3d_tests_registered_rest_routes']
         */
        $GLOBALS['g3d_tests_registered_rest_routes'] = [];
    }

    public function testAuditReadRouteIsRegistered(): void
    {
        \do_action('rest_api_init');

        self::assertTrue(self::routeExists('g3d/v1', '/audit', 'GET'));
    }

    public function testAuditWriteRouteIsRegistered(): void
    {
        \do_action('rest_api_init');

        self::assertTrue(self::routeExists('g3d/v1', '/audit', 'POST'));
    }

    private static function routeExists(string $ns, string $route, string $method): bool
    {
        /**
         * @var list<array{namespace:string,route:string,args:array<string,mixed>}> $routes
         */
        $routes = $GLOBALS['g3d_tests_registered_rest_routes'] ?? [];

        foreach ($routes as $r) {
            if ($r['namespace'] === $ns && $r['route'] === $route) {
                $methods = $r['args']['methods'] ?? '';

                return \is_string($methods) && \str_contains($methods, $method);
            }
        }

        return false;
    }
}
