<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace G3D\CatalogRules\Tests\Api {

    use G3D\CatalogRules\Api\RulesReadController;
    use PHPUnit\Framework\TestCase;
    use WP_Error;
    use WP_REST_Request;
    use WP_REST_Response;

    /**
     * @covers \G3D\CatalogRules\Api\RulesReadController::handle
     */
    final class RulesReadParamsTest extends TestCase
    {
        public function testHandleSucceedsWithValidParams(): void
        {
            $controller = new RulesReadController();
            $request    = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
            $request->set_param('producto_id', 'prod:rx-classic');
            $request->set_param('snapshot_id', 'snap:2025-09-01');
            $request->set_param('locale', 'es_ES');

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_REST_Response::class, $response);
            self::assertSame(200, $response->get_status());

            $data = $response->get_data();
            self::assertIsArray($data);
            self::assertArrayHasKey('rules', $data);
            self::assertIsArray($data['rules']);
            self::assertSame('snap:2025-09-27T18:45:00Z', $data['id']);
        }

        public function testHandleReturnsErrorWhenProductoIdMissing(): void
        {
            $controller = new RulesReadController();
            $request    = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
            $request->set_param('snapshot_id', 'snap:2025-09-01');
            $request->set_param('locale', 'es_ES');

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_Error::class, $response);
            self::assertSame('rest_missing_required_params', $response->get_error_code());

            $errorData = $response->get_error_data();
            self::assertIsArray($errorData);
            self::assertSame(400, $errorData['status']);
            self::assertContains('producto_id', $errorData['missing_fields']);
        }

        public function testHandleReturnsErrorWhenSnapshotIdHasInvalidType(): void
        {
            $controller = new RulesReadController();
            $request    = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
            $request->set_param('producto_id', 'prod:rx-classic');
            $request->set_param('snapshot_id', ['snap:2025-09-01']);

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_Error::class, $response);
            self::assertSame('rest_invalid_param', $response->get_error_code());

            $errorData = $response->get_error_data();
            self::assertIsArray($errorData);
            self::assertSame(400, $errorData['status']);
            self::assertContains('snapshot_id', $errorData['type_errors']);
        }
    }
}
