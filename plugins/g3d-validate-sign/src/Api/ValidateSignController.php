<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Api;

use G3D\ValidateSign\Validation\RequestValidator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class ValidateSignController
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
            '/validate-sign',
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
            'sku_hash' => 'TODO: SHA-256 canónico (ver plugin-3-g3d-validate-sign.md §6.1).',
            'sku_signature' => 'TODO: Firma sig.vN Ed25519 (ver plugin-3-g3d-validate-sign.md §6.1).',
            'expires_at' => 'TODO: ISO8601 + TTL (ver Capa 3 — Validación, Firma Y Caducidad — V2, API 2.1).',
            'snapshot_id' => $payload['snapshot_id']
                ?? 'TODO: snapshot_id eco (ver Capa 3 — Validación, Firma Y Caducidad — V2).',
            'summary' => 'TODO: Plantilla resumen (ver Capa 1 Identificadores Y Naming).',
            'price' => null, // TODO: Precio opcional (ver Capa 3 — Validación, Firma Y Caducidad — V2).
            'stock' => null, // TODO: Stock opcional (ver Capa 3 — Validación, Firma Y Caducidad — V2).
            'photo_url' => null, // TODO: URL TTL 90 días (ver Capa 3 — Validación, Firma Y Caducidad — V2).
            'request_id' => 'TODO: request_id (ver Capa 3 — Validación, Firma Y Caducidad — V2, Observabilidad).',
        ];

        return new WP_REST_Response($response, 200);
    }
}
