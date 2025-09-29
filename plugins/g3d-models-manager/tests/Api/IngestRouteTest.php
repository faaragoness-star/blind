<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Tests\Api;

use G3D\ModelsManager\Api\IngestController;
use G3D\ModelsManager\Validation\GlbIngestionValidator;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class IngestRouteTest extends TestCase
{
    public function testValido(): void
    {
        $controller = $this->createController();

        $payload = [
            'piece_type' => 'FRAME',
            'file_hash' => 'abc123',
            'filesize_bytes' => 2048,
            'draco_enabled' => true,
            'object_name' => 'FRA_12-6_R',
            'model_code' => 'A',
            'props' => [
                'socket_width_mm' => 12.5,
                'socket_height_mm' => 6.1,
                'variant' => 'R',
                'mount_type' => 'FRAMED',
            ],
            'anchors_present' => [
                'Frame_Anchor',
                'Temple_L_Anchor',
                'Temple_R_Anchor',
                'Socket_Cage',
            ],
            'slots_detectados' => ['MAT_BASE'],
        ];

        $request = new WP_REST_Request('POST', '/g3d/v1/glb/ingest');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode($payload, JSON_THROW_ON_ERROR));

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertArrayHasKey('ok', $data);
        self::assertTrue($data['ok']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $data['request_id']);
    }

    public function testFaltanCampos(): void
    {
        $controller = $this->createController();

        $payload = [
            'piece_type' => 'FRAME',
            'filesize_bytes' => 2048,
            'draco_enabled' => true,
            'object_name' => 'FRA_12-6_R',
            'model_code' => 'A',
            'props' => [
                'socket_width_mm' => 12.5,
                'socket_height_mm' => 6.1,
                'variant' => 'R',
                'mount_type' => 'FRAMED',
            ],
            'anchors_present' => [
                'Frame_Anchor',
                'Temple_L_Anchor',
                'Temple_R_Anchor',
                'Socket_Cage',
            ],
            'slots_detectados' => ['MAT_BASE'],
        ];

        $request = new WP_REST_Request('POST', '/g3d/v1/glb/ingest');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode($payload, JSON_THROW_ON_ERROR));

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('rest_missing_required_params', $response->get_error_code());
    }

    private function createController(): IngestController
    {
        $schemaPath = __DIR__ . '/../../schemas/glb-ingest.request.schema.json';
        $validator = new GlbIngestionValidator($schemaPath);

        return new IngestController($validator);
    }
}
