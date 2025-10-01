<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace G3D\CatalogRules\Tests\Api {

    use G3D\CatalogRules\Api\RulesReadController;
    use PHPUnit\Framework\TestCase;
    use WP_REST_Request;
    use WP_REST_Response;

    final class RulesReadRouteTest extends TestCase
    {
        public function testSuccessfulResponseReturnsStubbedSnapshot(): void
        {
            $controller = new RulesReadController();
            $request    = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
            $request->set_param('producto_id', 'prod:base');
            $request->set_param('locale', 'es-ES');

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_REST_Response::class, $response);
            self::assertSame(200, $response->get_status());

            $data = $response->get_data();
            self::assertIsArray($data);
            self::assertArrayHasKey('rules', $data);
            self::assertIsArray($data['rules']);
        }

        public function testMissingRequiredParamsReturnsUnifiedError(): void
        {
            $controller = new RulesReadController();
            $request    = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_REST_Response::class, $response);
            self::assertSame(400, $response->get_status());

            $data = $response->get_data();
            self::assertIsArray($data);
            self::assertFalse($data['ok']);
            self::assertSame('E_MISSING_PARAMS', $data['code']);
            self::assertSame('missing_params', $data['reason_key']);
        }

        public function testInvalidParamTypeReturnsUnifiedError(): void
        {
            $controller = new RulesReadController();
            $request    = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
            $request->set_param('producto_id', 123);

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
}
