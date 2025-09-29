<?php

declare(strict_types=1);

namespace G3D\CatalogRules\Tests\Api;

use G3D\CatalogRules\Api\RulesReadController;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Response;

final class RulesReadRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['g3d_catalog_rules_registered_routes'] = [];
    }

    public function testRegisterRoutesRegistersCatalogRulesReadEndpoint(): void
    {
        $controller = new RulesReadController();
        $controller->registerRoutes();

        self::assertNotEmpty($GLOBALS['g3d_catalog_rules_registered_routes']);

        $route = $GLOBALS['g3d_catalog_rules_registered_routes'][0];
        self::assertSame('g3d/v1', $route['namespace']);
        self::assertSame('/catalog/rules', $route['route']);
        self::assertSame('GET', $route['args']['methods']);
        self::assertSame([$controller, 'handle'], $route['args']['callback']);
        self::assertSame('__return_true', $route['args']['permission_callback']);
        self::assertArrayHasKey('producto_id', $route['args']['args']);
        self::assertTrue($route['args']['args']['producto_id']['required']);
    }

    public function testHandleReturnsCatalogRulesSnapshotPayload(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode([
            'producto_id' => 'prod:base',
            'locale' => 'es-ES',
        ]));

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertSame('prod:base', $data['producto_id'] ?? null);
        self::assertSame(['es-ES'], $data['locales'] ?? []);
        self::assertArrayHasKey('rules', $data);
        self::assertIsArray($data['rules']);
        self::assertArrayHasKey('material_to_modelos', $data['rules']);
        self::assertArrayHasKey('slot_mapping_editorial', $data['rules']);
        self::assertArrayHasKey('entities', $data);
        self::assertIsArray($data['entities']['piezas'] ?? null);
    }

    public function testHandleReturnsErrorWhenProductoIdMissing(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(400, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertFalse($data['ok']);
        self::assertSame('g3d_catalog_rules_missing_producto_id', $data['code']);
        self::assertSame(400, $data['status'] ?? null);
    }
}
