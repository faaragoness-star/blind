<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
    require_once __DIR__ . '/../../plugin.php';
}

namespace G3D\CatalogRules\Tests\Routes {

    use PHPUnit\Framework\TestCase;

    final class RulesRouteRegistrationTest extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();

            $GLOBALS['g3d_tests_registered_rest_routes'] = [];
        }

        public function testRulesReadRouteIsRegistered(): void
        {
            \do_action('rest_api_init');
            self::assertTrue(self::routeExists('g3d/v1', '/catalog/rules', 'GET'));
        }

        private static function routeExists(string $ns, string $route, string $method): bool
        {
            /** @var list<array{namespace:string,route:string,args:array<string,mixed>}> $routes */
            $routes = $GLOBALS['g3d_tests_registered_rest_routes'] ?? [];
            foreach ($routes as $r) {
                if ($r['namespace'] === $ns && $r['route'] === $route) {
                    $m = $r['args']['methods'] ?? '';
                    return \is_string($m) ? \str_contains($m, $method) : false;
                }
            }

            return false;
        }
    }
}
