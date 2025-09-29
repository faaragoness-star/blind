<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Tests\Api;

use G3D\ModelsManager\Api\IngestionController;
use G3D\ModelsManager\Service\GlbIngestionService;
use PHPUnit\Framework\TestCase;
use Test_Env\Perms;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class IngestionRouteTest extends TestCase
{
    protected function setUp(): void
    {
        Perms::denyAll();
    }

    public function testHandleReturns403WhenUserLacksCapability(): void
    {
        Perms::denyAll();

        $service = new GlbIngestionService();
        $controller = new IngestionController($service);

        $request = new WP_REST_Request('POST', '/g3d/v1/ingest-glb');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame(403, $response->get_error_data()['status'] ?? null);
    }

    public function testHandleReturns200WhenAdmin(): void
    {
        Perms::allowAll();

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
    }

    public function testHandleReturnsBindingAndValidation200(): void
    {
        Perms::allowAll();

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
