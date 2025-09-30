<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace G3D\CatalogRules\Tests\Routes {

    use PHPUnit\Framework\TestCase;

    final class RulesRouteRegistrationTest extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();

            $GLOBALS['g3d_tests_registered_rest_routes'] = [];
            $GLOBALS['g3d_tests_wp_actions']            = [];

            require __DIR__ . '/../../plugin.php';
        }

        public function testRegistersReadOnlyCatalogRulesRoute(): void
        {
            \do_action('rest_api_init');

            /**
             * @var list<array{namespace:string,route:string,args:array<string,mixed>}> $routes
             */
            $routes = $GLOBALS['g3d_tests_registered_rest_routes'] ?? [];

            self::assertTrue(
                self::routeExists('g3d/v1', '/catalog/rules', 'GET', $routes),
                'La ruta GET /g3d/v1/catalog/rules debe registrarse en rest_api_init.'
            );
        }

        /**
         * @param list<array{namespace:string,route:string,args:array<string,mixed>}> $routes
         */
        private static function routeExists(
            string $namespace,
            string $route,
            string $method,
            array $routes
        ): bool {
            foreach ($routes as $definition) {
                if ($definition['namespace'] !== $namespace || $definition['route'] !== $route) {
                    continue;
                }

                $methods = $definition['args']['methods'] ?? '';

                return \is_string($methods) && \str_contains($methods, $method);
            }

            return false;
        }
    }
}
