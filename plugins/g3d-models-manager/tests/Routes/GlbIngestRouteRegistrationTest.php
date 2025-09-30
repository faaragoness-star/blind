<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace G3D\ModelsManager\Tests\Routes {

    use PHPUnit\Framework\TestCase;

    final class GlbIngestRouteRegistrationTest extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();

            $GLOBALS['g3d_tests_registered_rest_routes'] = [];
            $GLOBALS['g3d_tests_wp_actions'] = [];

            require __DIR__ . '/../../plugin.php';
        }

        public function testRegistersGlbIngestRouteOnRestApiInit(): void
        {
            \do_action('rest_api_init');

            /** @var list<array{namespace:string,route:string,args:array<string,mixed>}> $routes */
            $routes = $GLOBALS['g3d_tests_registered_rest_routes'];
            self::assertIsArray($routes);
            self::assertTrue(self::routeExists('g3d/v1', '/glb-ingest', 'POST'), 'Debe registrar POST /g3d/v1/glb-ingest.');
        }

        private static function routeExists(string $ns, string $route, string $method): bool
        {
            /** @var list<array{namespace:string,route:string,args:array<string,mixed>}> $routes */
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
}
