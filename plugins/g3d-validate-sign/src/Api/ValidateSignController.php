<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Api;

use DateTimeImmutable;
use DateTimeZone;
use G3D\ValidateSign\Crypto\Signer;
use G3D\ValidateSign\Domain\Expiry;
use G3D\ValidateSign\Validation\RequestValidator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class ValidateSignController
{
    private RequestValidator $validator;
    private Signer $signer;
    private Expiry $expiry;
    private string $privateKey;

    public function __construct(
        RequestValidator $validator,
        Signer $signer,
        Expiry $expiry,
        string $privateKey
    ) {
        $this->validator = $validator;
        $this->signer = $signer;
        $this->expiry = $expiry;
        $this->privateKey = $privateKey;
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
        $requestId = $this->generateRequestId();
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
                    'request_id' => $requestId,
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
                    'request_id' => $requestId,
                ]
            );
        }

        // TODO: Validar snapshot, IDs y reglas de catálogo según docs/plugin-3-g3d-validate-sign.md §6.1.

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $expiresAt = $this->expiry->calculate(null, $now);
        $signing = $this->signer->sign($payload, $this->privateKey, $expiresAt);

        $snapshotId = isset($payload['snapshot_id']) ? (string) $payload['snapshot_id'] : '';

        $summary = $payload['summary'] ?? '{{pieza}} · {{material}} — {{color}} · {{textura}} · {{acabado}}';
        // TODO: Calcular summary real 
        (docs/Capa 1 Identificadores Y Naming — Actualizada (slots Abiertos).md, plantilla resumen).

        $response = [
            'ok' => true,
            'sku_hash' => $signing['sku_hash'],
            'sku_signature' => $signing['signature'],
            'expires_at' => $this->expiry->format($expiresAt),
            'snapshot_id' => $snapshotId,
            'summary' => $summary,
            'request_id' => $requestId,
        ];

        if (array_key_exists('price', $payload)) {
            $response['price'] = is_numeric($payload['price']) ? (float) $payload['price'] : $payload['price'];
        }

        if (array_key_exists('stock', $payload)) {
            $response['stock'] = $payload['stock'];
        }

        if (array_key_exists('photo_url', $payload)) {
            $response['photo_url'] = $payload['photo_url'];
        }

        return new WP_REST_Response($response, 200);
    }

    private function generateRequestId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
