<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';

    if (!function_exists('esc_html__')) {
        function esc_html__(string $text, string $domain = 'default'): string
        {
            return $text;
        }
    }

    if (!function_exists('esc_html')) {
        function esc_html(string $text): string
        {
            return $text;
        }
    }

    if (!function_exists('__')) {
        function __(string $text, string $domain = 'default'): string
        {
            return $text;
        }
    }
}

namespace G3D\AdminOps\Tests\Integration {

    use G3D\AdminOps\Admin\Menu;
    use G3D\AdminOps\Api\AuditReadController;
    use G3D\AdminOps\Api\AuditWriteController;
    use G3D\AdminOps\Audit\InMemoryEditorialActionLogger;
    use G3D\AdminOps\Rbac\CapabilityGuard;
    use G3D\AdminOps\Services\Registry;
    use PHPUnit\Framework\TestCase;
    use Test_Env\Perms;
    use WP_REST_Request;
    use WP_REST_Response;

    final class AuditSharedLoggerTest extends TestCase
    {
        protected function tearDown(): void
        {
            Perms::denyAll();
            parent::tearDown();
        }

        public function testPostCreatesEventVisibleOnGetAndUi(): void
        {
            Perms::allowAll();

            $logger = new InMemoryEditorialActionLogger();
            $registry = Registry::instance();
            $registry->set(Registry::S_AUDIT_LOGGER, $logger);

            $service = $registry->get(Registry::S_AUDIT_LOGGER);
            self::assertInstanceOf(InMemoryEditorialActionLogger::class, $service);
            self::assertSame($logger, $service);

            $writeController = new AuditWriteController($service);
            $readController = new AuditReadController($service);

            $payload = [
                'actor_id' => 'user:editor',
                'action' => 'publish',
                'context' => [
                    'what' => 'prod:rx-1',
                    'occurred_at' => '2025-09-29T00:00:00+00:00',
                ],
            ];

            $postRequest = new WP_REST_Request('POST', '/g3d/v1/audit');
            $postRequest->set_header('Content-Type', 'application/json');
            $json = json_encode($payload);
            self::assertIsString($json);
            $postRequest->set_body($json);

            $writeResponse = $writeController->handle($postRequest);
            self::assertInstanceOf(WP_REST_Response::class, $writeResponse);
            self::assertSame(201, $writeResponse->get_status());
            self::assertSame([
                'ok'    => true,
                'saved' => true,
            ], $writeResponse->get_data());

            $getRequest = new WP_REST_Request('GET', '/g3d/v1/audit');
            $readResponse = $readController->handle($getRequest);
            self::assertInstanceOf(WP_REST_Response::class, $readResponse);
            self::assertSame(200, $readResponse->get_status());

            $data = $readResponse->get_data();
            self::assertIsArray($data);
            self::assertTrue($data['ok']);

            $items = $data['items'] ?? [];
            self::assertIsArray($items);
            self::assertNotEmpty($items);

            /**
             * @var array{actor_id:string,action:string,what:string,occurred_at:string} $event
             */
            $event = $items[0];
            self::assertSame('user:editor', $event['actor_id']);
            self::assertSame('publish', $event['action']);
            self::assertSame('prod:rx-1', $event['what']);

            $menu = new Menu(new CapabilityGuard(), $service);
            ob_start();
            $menu->renderAuditTrail();
            $html = (string) ob_get_clean();

            self::assertStringContainsString('Versiones & Auditoría', $html);
            self::assertStringContainsString('prod:rx-1', $html);
        }
    }
}
