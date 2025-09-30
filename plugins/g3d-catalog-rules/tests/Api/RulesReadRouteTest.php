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
        self::assertArrayHasKey('ok', $data);
        self::assertTrue($data['ok']);
        self::assertArrayHasKey('id', $data);
        self::assertSame('snap:2025-09-27T18:45:00Z', $data['id']);
        self::assertArrayHasKey('schema_version', $data);
        self::assertSame('2.0.0', $data['schema_version']);
        self::assertArrayHasKey('producto_id', $data);
        self::assertSame('prod:base', $data['producto_id']);
        self::assertArrayHasKey('entities', $data);
        self::assertIsArray($data['entities']);
        self::assertArrayHasKey('rules', $data);
        self::assertIsArray($data['rules']);
        self::assertArrayHasKey('material_to_modelos', $data['rules']);
        self::assertArrayHasKey('material_to_colores', $data['rules']);
        self::assertArrayHasKey('material_to_texturas', $data['rules']);
        self::assertArrayHasKey('defaults', $data['rules']);
        self::assertArrayHasKey('encaje', $data['rules']);
        self::assertArrayHasKey('slot_mapping_editorial', $data['rules']);
        self::assertArrayHasKey('ver', $data);
        self::assertSame('ver:2025-09-27T18:45:00Z', $data['ver']);
        self::assertArrayHasKey('published_at', $data);
        self::assertSame('2025-09-27T18:45:00Z', $data['published_at']);
        self::assertArrayHasKey('published_by', $data);
        self::assertSame('user:admin', $data['published_by']);
        self::assertArrayHasKey('locales', $data);
        self::assertIsArray($data['locales']);
        self::assertContains('es-ES', $data['locales']);
        self::assertArrayHasKey('sku_policy', $data);
        self::assertIsArray($data['sku_policy']);
        self::assertArrayHasKey('include_morphs_in_sku', $data['sku_policy']);
    }

    public function testHandleReturnsErrorWhenProductoIdMissing(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('rest_missing_required_params', $response->get_error_code());

        $data = $response->get_error_data();
        self::assertIsArray($data);
        self::assertSame(400, $data['status']);
        self::assertSame(['producto_id'], $data['missing_fields']);
    }

    public function testHandleReturnsErrorWhenProductoIdHasInvalidType(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
        $request->set_param('producto_id', ['prod:base']);

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('rest_invalid_param', $response->get_error_code());

        $data = $response->get_error_data();
        self::assertIsArray($data);
        self::assertSame(400, $data['status']);
        self::assertSame(['producto_id'], $data['type_errors']);
    }

    public function testHandleReturnsErrorWhenLocaleHasInvalidType(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
        $request->set_param('producto_id', 'prod:base');
        $request->set_param('locale', ['es-ES']);

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('rest_invalid_param', $response->get_error_code());

        $data = $response->get_error_data();
        self::assertIsArray($data);
        self::assertSame(400, $data['status']);
        self::assertSame(['locale'], $data['type_errors']);
    }
}
