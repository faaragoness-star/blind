<?php

declare(strict_types=1);

namespace G3D\VendorBase\Tests\Api;

use G3D\VendorBase\Api\HealthController;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Response;

final class HealthControllerTest extends TestCase
{
    public function testHealthReturnsOkAndVersionsShape(): void
    {
        $controller = new HealthController();
        $response = $controller->handle(new WP_REST_Request());

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        /**
         * @var array{
         *     ok: bool,
         *     php_version: string,
         *     wp_available: bool,
         *     plugins: list<array{slug: string, version: string|null}>
         * } $data
         */
        $data = $response->get_data();

        self::assertTrue($data['ok']);
        self::assertIsString($data['php_version']);
        self::assertIsBool($data['wp_available']);
        self::assertIsArray($data['plugins']);

        foreach ($data['plugins'] as $plugin) {
            self::assertArrayHasKey('slug', $plugin);
            self::assertArrayHasKey('version', $plugin);
            self::assertIsString($plugin['slug']);
            $version = $plugin['version'];

            if ($version !== null) {
                self::assertIsString($version);
            } else {
                self::assertNull($version);
            }
        }
    }
}
