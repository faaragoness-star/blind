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
        self::assertTrue($route['args']['args']['snapshot_id']['required']);
        self::assertArrayHasKey('locale', $route['args']['args']);
        self::assertTrue($route['args']['args']['locale']['required']);
    }

    public function testHandleReturnsDeterministicRulesStub(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
        $request->set_param('producto_id', 'prod:rx-classic');
        $request->set_param('snapshot_id', 'snap:2025-09-01');
        $request->set_param('locale', 'es_ES');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertArrayHasKey('ok', $data);
        self::assertTrue($data['ok']);
        self::assertArrayHasKey('rules', $data);
        self::assertIsArray($data['rules']);
        self::assertSame([], $data['rules']);
        self::assertArrayHasKey('snapshot_id', $data);
        self::assertSame('snap:2025-09-27T18:45:00Z', $data['snapshot_id']);
        self::assertArrayHasKey('version', $data);
        self::assertSame('ver:2025-09-27T18:45:00Z', $data['version']);
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
        self::assertSame('E_MISSING_PARAMS', $data['code']);
        self::assertSame('missing_params', $data['reason_key']);
    }

    public function testHandleReturnsErrorWhenProductoIdHasInvalidType(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
        $request->set_param('producto_id', ['prod:base']);
        $request->set_param('snapshot_id', 'snap:2025-09-01');
        $request->set_param('locale', 'es_ES');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(400, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertFalse($data['ok']);
        self::assertSame('E_INVALID_PARAMS', $data['code']);
        self::assertSame('invalid_params', $data['reason_key']);
    }

    public function testHandleReturnsErrorWhenLocaleHasInvalidType(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
        $request->set_param('producto_id', 'prod:base');
        $request->set_param('locale', ['es-ES']);
        $request->set_param('snapshot_id', 'snap:2025-09-01');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(400, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertFalse($data['ok']);
        self::assertSame('E_INVALID_PARAMS', $data['code']);
        self::assertSame('invalid_params', $data['reason_key']);
    }
}
