<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace G3D\ModelsManager\Tests\Routes {

    use PHPUnit\Framework\TestCase;
    use Test_Env\Perms;

    final class RouteRegistrationTest extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();
            $GLOBALS['g3d_tests_registered_rest_routes'] = [];
            $GLOBALS['g3d_tests_wp_actions'] = [];
            Perms::denyAll();

            require __DIR__ . '/../../plugin.php';
        }

        public function testRegistersCanonicalGlbIngestRoute(): void
        {
            do_action('rest_api_init');

            $routes = $GLOBALS['g3d_tests_registered_rest_routes'] ?? [];

            $matches = array_values(array_filter(
                $routes,
                static fn (array $route): bool =>
                    $route['namespace'] === 'g3d/v1'
                    && $route['route'] === '/glb-ingest'
            ));

            self::assertCount(1, $matches, 'Debe registrarse solo /g3d/v1/glb-ingest.');

            $definition = $matches[0];
            self::assertSame('POST', $definition['args']['methods']);
            self::assertIsCallable($definition['args']['callback']);
            self::assertIsCallable($definition['args']['permission_callback']);

            $permission = $definition['args']['permission_callback'];
            self::assertFalse($permission());

            Perms::allowAll();
            self::assertTrue($permission());

            $alternative = array_values(array_filter(
                $routes,
                static fn (array $route): bool =>
                    $route['namespace'] === 'g3d/v1'
                    && $route['route'] !== '/glb-ingest'
                    && str_contains($route['route'], 'ingest')
            ));

            self::assertSame([], $alternative, 'No debe haber rutas alternativas de ingesta.');
        }
    }
}
