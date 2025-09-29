<?php

declare(strict_types=1);

namespace G3D\AdminOps\Tests\Api;

use G3D\AdminOps\Api\AuditReadController;
use G3D\AdminOps\Audit\InMemoryEditorialActionLogger;
use PHPUnit\Framework\TestCase;
use Test_Env\Perms;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class AuditReadRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Perms::denyAll();
    }

    public function testForbiddenWhenNoCapability(): void
    {
        $controller = new AuditReadController(new InMemoryEditorialActionLogger());
        $response = $controller->handle(new WP_REST_Request('GET', '/g3d/v1/admin-ops/audit'));

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame(403, $response->get_error_data()['status'] ?? null);
    }

    public function testOkReturnsEventsList(): void
    {
        Perms::allowAll();
        $controller = new AuditReadController(new InMemoryEditorialActionLogger());
        $response = $controller->handle(new WP_REST_Request('GET', '/g3d/v1/admin-ops/audit'));

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertArrayHasKey('events', $data);
        self::assertIsArray($data['events']);
    }
}
