<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace G3D\ModelsManager\Tests\Api {

    use G3D\ModelsManager\Api\GlbIngestController;
    use PHPUnit\Framework\TestCase;
    use WP_REST_Request;
    use WP_REST_Response;

    final class GlbIngestControllerTest extends TestCase
    {
        public function testHandleReturnsOkWithBindingAndValidation(): void
        {
            $controller = new GlbIngestController();
            $request    = new WP_REST_Request('POST', '/g3d/v1/glb-ingest');
            $request->set_header('Content-Type', 'application/json');
            $request->set_body((string) json_encode(['fake' => true]));

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_REST_Response::class, $response);
            self::assertSame(200, $response->get_status());

            $data = $response->get_data();
            self::assertIsArray($data);
            self::assertArrayHasKey('ok', $data);
            self::assertTrue($data['ok']);
            self::assertArrayHasKey('binding', $data);
            self::assertIsArray($data['binding']);
            self::assertArrayHasKey('validation', $data);
            self::assertIsArray($data['validation']);

            $binding = $data['binding'];
            self::assertArrayHasKey('file_hash', $binding);
            self::assertArrayHasKey('filesize_bytes', $binding);
            self::assertArrayHasKey('draco_enabled', $binding);
            self::assertArrayHasKey('bounding_box', $binding);
            self::assertArrayHasKey('slots_detectados', $binding);
            self::assertArrayHasKey('anchors_present', $binding);
            self::assertArrayHasKey('props', $binding);

            $validation = $data['validation'];
            self::assertArrayHasKey('ok', $validation);
            self::assertIsBool($validation['ok']);
            self::assertArrayHasKey('missing', $validation);
            self::assertIsArray($validation['missing']);
            self::assertArrayHasKey('type', $validation);
            self::assertIsArray($validation['type']);
        }
    }
}
