<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Tests\Routes;

use PHPUnit\Framework\TestCase;

final class RouteRegistrationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!function_exists('sodium_crypto_sign_keypair')) {
            self::markTestSkipped('ext-sodium requerida para las pruebas (ver docs/plugin-3-g3d-validate-sign.md §4.1).');
        }

        require_once __DIR__ . '/../../plugin.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['g3d_tests_registered_rest_routes'] = [];
    }

    public function testValidateSignRouteRegistered(): void
    {
        \do_action('rest_api_init');

        self::assertTrue(self::routeExists('g3d/v1', '/validate-sign', 'POST'));
    }

    public function testVerifyRouteRegistered(): void
    {
        \do_action('rest_api_init');

        self::assertTrue(self::routeExists('g3d/v1', '/verify', 'POST'));
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
