<?php

declare(strict_types=1);

namespace G3D\CatalogRules\Tests\Api;

use G3D\CatalogRules\Api\RulesReadController;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Response;

final class RulesReadRouteParamsTest extends TestCase
{
    public function testHandleReturnsOkWhenRequiredParamsProvided(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
        $request->set_param('producto_id', 'prod:base');
        $request->set_param('snapshot_id', 'snap:2025-09-27T18:45:00Z');
        $request->set_param('locale', 'es-ES');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertTrue($data['ok'] ?? false);
        self::assertArrayHasKey('rules', $data);
        self::assertIsArray($data['rules']);
    }

    public function testHandleReturnsBadRequestWhenMissingParams(): void
    {
        $controller = new RulesReadController();
        $request = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(400, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertFalse($data['ok'] ?? true);
        self::assertSame('E_MISSING_PARAMS', $data['code'] ?? null);
        self::assertSame('missing_params', $data['reason_key'] ?? null);
    }
}
