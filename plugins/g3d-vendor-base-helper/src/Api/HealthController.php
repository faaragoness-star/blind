<?php

declare(strict_types=1);

namespace G3D\VendorBase\Api;

use WP_REST_Request;
use WP_REST_Response;

final class HealthController
{
    public function registerRoutes(): void
    {
        \register_rest_route(
            'g3d/v1',
            '/health',
            [
                'methods' => 'GET',
                'callback' => [$this, 'handle'],
                // Health es público (no contiene secretos). Si los docs piden restricción, cámbialo.
                'permission_callback' => '__return_true',
            ]
        );
    }

    public function handle(WP_REST_Request $req): WP_REST_Response
    {
        unset($req);

        $phpVersion = \PHP_VERSION;
        $wpAvailable = \function_exists('get_option') && \function_exists('register_rest_route');
        $plugins = self::collectLocalPlugins();

        return new WP_REST_Response(
            [
                'ok' => true,
                'php_version' => $phpVersion,
                'wp_available' => $wpAvailable,
                'plugins' => $plugins,
            ],
            200
        );
    }

    /**
     * @return list<array{slug: string, version: string|null}>
     */
    private static function collectLocalPlugins(): array
    {
        $slugs = [
            'g3d-vendor-base-helper',
            'g3d-catalog-rules',
            'g3d-models-manager',
            'g3d-validate-sign',
            'gafas3d-wizard-modal',
            'g3d-admin-ops',
        ];

        /** @var list<array{slug: string, version: string|null}> $plugins */
        $plugins = [];

        foreach ($slugs as $slug) {
            $version = self::tryReadLocalVersion($slug);
            $plugins[] = [
                'slug' => $slug,
                'version' => $version,
            ];
        }

        return $plugins;
    }

    private static function tryReadLocalVersion(string $slug): ?string
    {
        $pluginsRoot = \dirname(__DIR__, 3);
        $pluginPhp = $pluginsRoot . '/' . $slug . '/plugin.php';

        if (!\is_file($pluginPhp) || !\is_readable($pluginPhp)) {
            // TODO(doc): documentar detección de versión cuando no hay plugin.php legible.
            return null;
        }

        $fh = @\fopen($pluginPhp, 'r');
        if ($fh === false) {
            // TODO(doc): documentar detección de versión cuando no se puede abrir plugin.php.
            return null;
        }

        $version = null;
        $maxLines = 30;

        while (!\feof($fh) && $maxLines-- > 0) {
            $line = (string) \fgets($fh);
            if (\stripos($line, 'Version:') === false) {
                continue;
            }

            $parts = \explode(':', $line, 2);
            if (!isset($parts[1])) {
                continue;
            }

            $candidate = \trim($parts[1]);
            if ($candidate === '') {
                continue;
            }

            $version = $candidate;
            break;
        }

        \fclose($fh);

        // TODO(doc): si en futuro hay constantes VERSION por plugin, intentar leerlas sin eval.
        return $version;
    }
}
