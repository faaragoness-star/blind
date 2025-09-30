<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace G3D\AdminOps\Tests\Api {

use G3D\AdminOps\Api\AuditReadController;
use G3D\AdminOps\Audit\InMemoryEditorialActionLogger;
use PHPUnit\Framework\TestCase;
use Test_Env\Perms;
use WP_REST_Request;
use WP_REST_Response;

final class AuditReadControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Perms::denyAll();
        parent::tearDown();
    }

    public function testHandleReturnsEventsList(): void
    {
        Perms::allowAll();
        $logger = new InMemoryEditorialActionLogger();
        $logger->logAction('user:99', 'view', ['what' => 'modelo:demo']);

        $controller = new AuditReadController($logger);
        $request    = new WP_REST_Request('GET', '/g3d/v1/audit');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertTrue($data['ok']);
        self::assertArrayHasKey('events', $data);
        self::assertIsArray($data['events']);
    }
}

}
