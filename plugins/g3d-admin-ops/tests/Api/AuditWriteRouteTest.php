<?php

declare(strict_types=1);

namespace G3D\AdminOps\Tests\Api;

use G3D\AdminOps\Api\AuditWriteController;
use G3D\AdminOps\Audit\InMemoryEditorialActionLogger;
use PHPUnit\Framework\TestCase;
use Test_Env\Perms;
use WP_REST_Request;
use WP_REST_Response;

final class AuditWriteRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Perms::denyAll();
    }

    public function testForbiddenWhenNoCapability(): void
    {
        $controller = new AuditWriteController(new InMemoryEditorialActionLogger());
        $response = $controller->handle($this->makeRequest([
            'actor_id' => 'user:1',
            'action' => 'publish',
            'context' => ['what' => 'modelo:rx-classic'],
        ]));

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(403, $response->get_status());
        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertFalse($data['ok']);
        self::assertSame('rest_forbidden', $data['code']);
    }

    public function testBadRequestWhenMissingRequired(): void
    {
        Perms::allowAll();
        $controller = new AuditWriteController(new InMemoryEditorialActionLogger());
        $response = $controller->handle($this->makeRequest([
            'actor_id' => 'user:1',
            'action' => 'publish',
            'context' => [],
        ]));

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(400, $response->get_status());
        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertFalse($data['ok']);
        self::assertSame('rest_missing_required_params', $data['code']);
        $missing = $data['missing_fields'] ?? [];
        self::assertContains('context.what', $missing);
    }

    public function testOkWhenValidPayload(): void
    {
        Perms::allowAll();
        $logger = new InMemoryEditorialActionLogger();
        $controller = new AuditWriteController($logger);
        $response = $controller->handle($this->makeRequest([
            'actor_id' => 'user:42',
            'action' => 'publish',
            'context' => [
                'what' => 'modelo:rx-classic',
                'snapshot_id' => 'snap-01',
                'resultado' => 'ok',
                'latency_ms' => 123,
            ],
        ]));

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());
        $data = $response->get_data();
        self::assertTrue($data['ok'] ?? false);

        $events = $logger->getEvents();
        self::assertCount(1, $events);
        self::assertSame('user:42', $events[0]['actor_id']);
        self::assertSame('modelo:rx-classic', $events[0]['what']);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function makeRequest(array $payload): WP_REST_Request
    {
        $request = new WP_REST_Request('POST', '/g3d/v1/admin-ops/audit/log');
        $request->set_header('Content-Type', 'application/json');
        $json = json_encode($payload);
        self::assertIsString($json);
        $request->set_body($json);

        return $request;
    }
}
