<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Api;

use G3D\ModelsManager\Validation\GlbIngestionValidator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class IngestController
{
    public function __construct(private GlbIngestionValidator $validator)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'g3d/v1',
            '/glb/ingest',
            [
                'methods' => 'POST',
                'callback' => [$this, 'handle'],
                'permission_callback' => '__return_true', // TODO: docs/plugin-1-g3d-models-manager.md §7 RBAC.
            ]
        );
    }

    public function handle(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = [];
        }

        $validation = $this->validator->validate($payload);

        if (!empty($validation['missing'])) {
            return new WP_Error(
                'rest_missing_required_params',
                'Faltan campos requeridos.',
                [
                    'status' => 400,
                    'missing_fields' => $validation['missing'],
                ]
            );
        }

        if (!empty($validation['type'])) {
            return new WP_Error(
                'rest_invalid_param',
                'Tipos inválidos detectados.',
                [
                    'status' => 400,
                    'type_errors' => $validation['type'],
                ]
            );
        }

        return new WP_REST_Response(
            [
                'ok' => true,
                'request_id' => $this->generateRequestId(),
            ],
            200
        );
    }

    private function generateRequestId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
