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
            $logger->logAction('user:21', 'publish', [
                'what'         => 'modelo:demo',
                'occurred_at'  => '2025-09-29T00:00:00+00:00',
                'resultado'    => 'ok',
                'snapshot_id'  => 'snap-1',
                'latency_ms'   => 150,
            ]);

            $controller = new AuditReadController($logger);
            $request    = new WP_REST_Request('GET', '/g3d/v1/audit');

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_REST_Response::class, $response);
            self::assertSame(200, $response->get_status());

            $data = $response->get_data();
            self::assertIsArray($data);
            self::assertTrue($data['ok']);
            self::assertSame(1, $data['page']);
            self::assertSame(20, $data['per_page']);
            self::assertSame(2, $data['total']);
            self::assertArrayHasKey('items', $data);
            self::assertIsArray($data['items']);
            self::assertCount(2, $data['items']);
            self::assertSame('view', $data['items'][0]['action']);
            self::assertSame('publish', $data['items'][1]['action']);
        }

        public function testHandleSupportsPagination(): void
        {
            Perms::allowAll();
            $logger = new InMemoryEditorialActionLogger();
            $logger->logAction('user:1', 'view', ['what' => 'modelo:a']);
            $logger->logAction('user:2', 'view', ['what' => 'modelo:b']);
            $logger->logAction('user:3', 'view', ['what' => 'modelo:c']);

            $controller = new AuditReadController($logger);

            $request = new WP_REST_Request('GET', '/g3d/v1/audit');
            $request->set_param('page', 2);
            $request->set_param('per_page', 2);

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_REST_Response::class, $response);
            self::assertSame(200, $response->get_status());

            $data = $response->get_data();
            self::assertIsArray($data);
            self::assertTrue($data['ok']);
            self::assertSame(2, $data['page']);
            self::assertSame(2, $data['per_page']);
            self::assertSame(3, $data['total']);
            self::assertCount(1, $data['items']);
            self::assertSame('modelo:c', $data['items'][0]['what']);
        }
    }
}
