<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Api;

use G3D\ModelsManager\Service\GlbIngestionService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @phpstan-import-type IngestionResult from \G3D\ModelsManager\Service\GlbIngestionService
 */
final class IngestionController
{
    public function __construct(private GlbIngestionService $service)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'g3d/v1',
            '/ingest-glb',
            [
                'methods' => 'POST',
                'callback' => [$this, 'handle'],
                // TODO: RBAC real (docs §4). Por ahora, admins.
                'permission_callback' => static fn() => current_user_can('manage_options'),
            ]
        );
    }

    public function handle(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $tmp = tempnam(sys_get_temp_dir(), 'g3d_glb_');
        if ($tmp === false) {
            return new WP_Error(
                'glb_ingestion_tempfile_error',
                'No fue posible preparar el archivo temporal para la ingesta.',
                ['status' => 500]
            );
        }

        $bytes = str_repeat('G', 1536);
        file_put_contents($tmp, $bytes);

        $dummyFile = [
            'name' => 'sample.glb',
            'tmp_name' => $tmp,
            'size' => strlen($bytes),
            'type' => 'model/gltf-binary',
            'error' => 0,
        ];

        try {
            /** @var IngestionResult $result */
            $result = $this->service->ingest($dummyFile);
        } finally {
            if (is_file($tmp)) {
                unlink($tmp);
            }
        }

        return new WP_REST_Response(
            [
                'binding' => $result['binding'],
                'validation' => $result['validation'],
            ],
            200
        );
    }
}
