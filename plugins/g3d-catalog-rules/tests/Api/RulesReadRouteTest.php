<?php

declare(strict_types=1);

namespace G3D\CatalogRules\Tests\Api;

use G3D\CatalogRules\Api\RulesReadController;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class RulesReadRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['g3d_tests_registered_rest_routes'] = [];
    }

    public function testRegisterRoutesRegistersCatalogRulesReadEndpoint(): void
    {
        $controller = new RulesReadController();
        $controller->registerRoutes();

        self::assertNotEmpty($GLOBALS['g3d_tests_registered_rest_routes']);

        $route = $GLOBALS['g3d_tests_registered_rest_routes'][0];
        self::assertSame('g3d/v1', $route['namespace']);
        self::assertSame('/catalog/rules', $route['route']);
        self::assertSame('GET', $route['args']['methods']);
        self::assertSame([$controller, 'handle'], $route['args']['callback']);
        self::assertSame('__return_true', $route['args']['permission_callback']);
        self::assertArrayHasKey('producto_id', $route['args']['args']);
        self::assertTrue($route['args']['args']['producto_id']['required']);
        self::assertArrayHasKey('snapshot_id', $route['args']['args']);
        self::assertFalse($route['args']['args']['snapshot_id']['required']);
        self::assertArrayHasKey('locale', $route['args']['args']);
        self::assertFalse($route['args']['args']['locale']['required']);
    }

    public function testHandleReturnsCatalogRulesPayload(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
        $request->set_param('producto_id', 'prod:base');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertArrayHasKey('rules', $data);
        self::assertIsArray($data['rules']);
        self::assertArrayHasKey('snapshot_id', $data);
        self::assertArrayHasKey('version', $data);
        self::assertArrayHasKey('producto_id', $data);
        self::assertSame('snap:2025-09-27T18:45:00Z', $data['snapshot_id']);
        self::assertSame('ver:2025-09-27T18:45:00Z', $data['version']);
        self::assertSame('prod:base', $data['producto_id']);

        $firstRule = $data['rules'][0] ?? null;
        self::assertIsArray($firstRule);
        self::assertArrayHasKey('key', $firstRule);
        self::assertArrayHasKey('value', $firstRule);
        self::assertSame('material_to_modelos', $firstRule['key']);
    }

    public function testHandleReturnsErrorWhenProductoIdMissing(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('rest_missing_required_params', $response->get_error_code());
        self::assertSame(
            ['status' => 400, 'params' => ['producto_id']],
            $response->get_error_data()
        );
    }
}
