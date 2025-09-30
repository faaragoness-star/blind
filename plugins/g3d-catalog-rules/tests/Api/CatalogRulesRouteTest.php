<?php

declare(strict_types=1);

namespace G3dCatalogRules\Tests\Api;

use G3dCatalogRules\Api\CatalogRulesController;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Response;

final class CatalogRulesRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['g3d_tests_registered_rest_routes'] = [];
    }

    public function testRegisterRoutesRegistersCatalogRulesEndpoint(): void
    {
        $controller = new CatalogRulesController();
        $controller->registerRoutes();

        self::assertNotEmpty($GLOBALS['g3d_tests_registered_rest_routes']);
        $route = $GLOBALS['g3d_tests_registered_rest_routes'][0];

        self::assertSame('g3d/v1', $route['namespace']);
        self::assertSame('/catalog-rules', $route['route']);
        self::assertSame('GET', $route['args']['methods']);
        self::assertSame([$controller, 'getCatalogRules'], $route['args']['callback']);
        self::assertSame('__return_true', $route['args']['permission_callback']);
    }

    public function testGetCatalogRulesReturnsStubPayload(): void
    {
        $controller = new CatalogRulesController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog-rules');

        $response = $controller->getCatalogRules($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertArrayHasKey('ok', $data);
        self::assertTrue($data['ok']);
        self::assertArrayHasKey('rules', $data);
        self::assertIsArray($data['rules']);
        self::assertArrayHasKey('meta', $data);
        self::assertSame(
            'Definir metadatos públicos (docs/plugin-2-g3d-catalog-rules.md §5 Modelo de datos).',
            $data['meta']['todo'] ?? null
        );
    }
}
