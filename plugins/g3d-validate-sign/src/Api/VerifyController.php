<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Api;

use G3D\ValidateSign\Validation\RequestValidator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class VerifyController
{
    private RequestValidator $validator;

    public function __construct(RequestValidator $validator)
    {
        $this->validator = $validator;
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'g3d/v1',
            '/verify',
            [
                'methods' => 'POST',
                'callback' => [$this, 'handle'],
                'permission_callback' => '__return_true', // TODO: Permisos (ver plugin-3-g3d-validate-sign.md §7).
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

        $response = [
            'ok' => true,
            'request_id' => 'TODO: request_id (ver Capa 3 — Validación, Firma Y Caducidad — V2, Observabilidad).',
            'todo' => 'TODO: Respuesta detallada /verify (ver plugin-3-g3d-validate-sign.md §6.2).',
        ];

        return new WP_REST_Response($response, 200);
    }
}
