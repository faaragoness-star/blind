<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Tests\Api;

use G3D\ModelsManager\Api\IngestionController;
use G3D\ModelsManager\Service\GlbIngestionService;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Response;

final class IngestionRouteTest extends TestCase
{
    public function testHandleReturnsBindingAndValidation200(): void
    {
        $service = new GlbIngestionService();
        $controller = new IngestionController($service);

        $request = new WP_REST_Request('POST', '/g3d/v1/ingest-glb');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertArrayHasKey('binding', $data);
        self::assertArrayHasKey('validation', $data);

        self::assertIsArray($data['binding']);
        self::assertIsArray($data['validation']);
        self::assertArrayHasKey('ok', $data['validation']);
    }
}
