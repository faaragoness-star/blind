<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace G3D\CatalogRules\Tests\Routes {

    use PHPUnit\Framework\TestCase;

    final class RouteRegistrationTest extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();
            $GLOBALS['g3d_tests_registered_rest_routes'] = [];
            $GLOBALS['g3d_tests_wp_actions'] = [];

            require __DIR__ . '/../../plugin.php';
        }

        public function testRegistersCatalogRulesReadRoute(): void
        {
            do_action('rest_api_init');

            $routes = $GLOBALS['g3d_tests_registered_rest_routes'] ?? [];
            $matches = array_values(array_filter(
                $routes,
                static fn (array $route): bool =>
                    $route['namespace'] === 'g3d/v1'
                    && $route['route'] === '/catalog/rules'
            ));

            self::assertCount(1, $matches, 'Debe registrarse solo /g3d/v1/catalog/rules.');

            $definition = $matches[0];
            self::assertSame('GET', $definition['args']['methods']);
            self::assertSame('__return_true', $definition['args']['permission_callback']);
            self::assertArrayHasKey('args', $definition['args']);
            self::assertArrayHasKey('producto_id', $definition['args']['args']);
        }
    }
}
