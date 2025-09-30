<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace G3D\AdminOps\Tests\Api {

    use G3D\AdminOps\Api\AuditWriteController;
    use G3D\AdminOps\Audit\InMemoryEditorialActionLogger;
    use PHPUnit\Framework\TestCase;
    use Test_Env\Perms;
    use WP_REST_Request;
    use WP_REST_Response;

    final class AuditWriteControllerTest extends TestCase
    {
        protected function tearDown(): void
        {
            Perms::denyAll();
            parent::tearDown();
        }

        public function testHandlePersistsEventWhenPayloadIsValid(): void
        {
            Perms::allowAll();
            $logger = new InMemoryEditorialActionLogger();
            $controller = new AuditWriteController($logger);

            $request = $this->makeRequest([
                'actor_id' => 'user:42',
                'action'   => 'publish',
                'context'  => ['what' => 'modelo:rx-classic'],
            ]);

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_REST_Response::class, $response);
            self::assertSame(201, $response->get_status());
            self::assertSame(['ok' => true], $response->get_data());

            $events = $logger->getEvents();
            self::assertCount(1, $events);
            self::assertSame('user:42', $events[0]['actor_id']);
            self::assertSame('publish', $events[0]['action']);
        }

        public function testHandleReturnsErrorWhenActorOrActionMissing(): void
        {
            Perms::allowAll();
            $controller = new AuditWriteController(new InMemoryEditorialActionLogger());

            $request = $this->makeRequest([
                'actor_id' => '',
                'action'   => 'publish',
            ]);

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_REST_Response::class, $response);
            self::assertSame(400, $response->get_status());

            $data = $response->get_data();
            self::assertIsArray($data);
            self::assertFalse($data['ok']);
            self::assertSame('E_INVALID_INPUT', $data['code']);
        }

        public function testHandleReturnsErrorWhenLoggerRejectsContext(): void
        {
            Perms::allowAll();
            $controller = new AuditWriteController(new InMemoryEditorialActionLogger());

            $request = $this->makeRequest([
                'actor_id' => 'user:7',
                'action'   => 'publish',
                'context'  => ['invalid' => 'value'],
            ]);

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_REST_Response::class, $response);
            self::assertSame(400, $response->get_status());

            $data = $response->get_data();
            self::assertIsArray($data);
            self::assertFalse($data['ok']);
            self::assertSame('E_INVALID_CONTEXT', $data['code']);
        }

        /**
         * @param array<string,mixed> $payload
         */
        private function makeRequest(array $payload): WP_REST_Request
        {
            $request = new WP_REST_Request('POST', '/g3d/v1/audit');
            $request->set_header('Content-Type', 'application/json');
            $json = json_encode($payload);
            self::assertIsString($json);
            $request->set_body($json);

            return $request;
        }
    }
}
