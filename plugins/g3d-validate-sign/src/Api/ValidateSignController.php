<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Api;

use G3D\ValidateSign\Crypto\Signer;
use G3D\ValidateSign\Validation\RequestValidator;
use G3D\VendorBase\Rest\Responses;
use G3D\VendorBase\Rest\Security;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @phpstan-type ValidateSignPayload array{
 *   schema_version?: string,
 *   snapshot_id?: string,
 *   producto_id?: string,
 *   locale?: string,
 *   state?: array<string, mixed>,
 *   flags?: array<string, mixed>
 * }
 * @phpstan-type ValidateResponse array{
 *   ok: true,
 *   sku_hash: string,
 *   sku_signature: string,
 *   expires_at: string,
 *   snapshot_id?: string,
 *   summary?: string,
 *   price?: float|int|string,
 *   stock?: mixed,
 *   photo_url?: mixed,
 *   request_id: string
 * }
 *
 * REST controller to sign SKU payloads.
 */
class ValidateSignController
{
    private RequestValidator $validator;
    private Signer $signer;
    private string $privateKey;

    public function __construct(
        RequestValidator $validator,
        Signer $signer,
        string $privateKey
    ) {
        $this->validator  = $validator;
        $this->signer     = $signer;
        $this->privateKey = $privateKey;
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'g3d/v1',
            '/validate-sign',
            [
                'methods'  => 'POST',
                'callback' => [$this, 'handle'],
                // público según docs/Capa 3 — Validación, Firma y Caducidad — Actualizada
                // (slots Abiertos) — V2 (urls).md §2.1.
                'permission_callback' => static fn (): bool => true,
            ]
        );
    }

    public function handle(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $nonceCheck = Security::checkOptionalNonce($request);
        if ($nonceCheck instanceof WP_Error) {
            // TODO(doc §auth): si el doc requiere nonce, return $nonceCheck;
        }

        $requestId = $this->generateRequestId();
        $payload   = $request->get_json_params();

        if (!is_array($payload)) {
            $payload = [];
        }

        $validation = $this->validator->validate($payload);

        if (!empty($validation['missing'])) {
            // TODO(doc §errores): documentar missing_fields en errores REST.
            return new WP_Error(
                'rest_missing_required_params',
                'Faltan campos requeridos.',
                [
                    'status' => 400,
                    'request_id' => $requestId,
                    'missing_fields' => $validation['missing'],
                ]
            );
        }

        if (!empty($validation['type'])) {
            // TODO(doc §errores): documentar type_errors en errores REST.
            return new WP_Error(
                'rest_invalid_param',
                'Tipos inválidos detectados.',
                [
                    'status' => 400,
                    'request_id' => $requestId,
                    'type_errors' => $validation['type'],
                ]
            );
        }

        /** @var ValidateSignPayload $sanitized */
        $sanitized = $this->sanitizePayload($payload);

        // TODO(plugin-3-g3d-validate-sign.md §6.1): Validar snapshot, IDs y reglas de catálogo.

        $signing   = $this->signer->sign($sanitized, $this->privateKey);

        $snapshotId = isset($sanitized['snapshot_id']) ? (string) $sanitized['snapshot_id'] : '';

        $summary = $payload['summary'] ?? '{{pieza}} · {{material}} — {{color}} · {{textura}} · {{acabado}}';
        // TODO(Capa 1 Identificadores Y Naming — Actualizada (slots Abiertos).md §resumen): calcular summary real.

        /** @var ValidateResponse $response */
        $response = Responses::ok([
            'sku_hash'      => $signing['sku_hash'],
            'sku_signature' => $signing['signature'],
            'expires_at'    => $signing['expires_at'],
            'snapshot_id'   => $snapshotId,
            'summary'       => $summary,
            'request_id'    => $requestId,
        ]);

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

    /**
     * @param array<string, mixed> $payload
     *
     * @return ValidateSignPayload
     */
    private function sanitizePayload(array $payload): array
    {
        $sanitized = [];

        if (isset($payload['schema_version']) && is_string($payload['schema_version'])) {
            $sanitized['schema_version'] = $payload['schema_version'];
        }

        if (isset($payload['snapshot_id']) && is_string($payload['snapshot_id'])) {
            $sanitized['snapshot_id'] = $payload['snapshot_id'];
        }

        if (isset($payload['producto_id']) && is_string($payload['producto_id'])) {
            $sanitized['producto_id'] = $payload['producto_id'];
        }

        if (isset($payload['locale']) && is_string($payload['locale'])) {
            $sanitized['locale'] = $payload['locale'];
        }

        if (isset($payload['state']) && is_array($payload['state'])) {
            $sanitized['state'] = $payload['state'];
        }

        if (isset($payload['flags']) && is_array($payload['flags'])) {
            $sanitized['flags'] = $payload['flags'];
        }

        return $sanitized;
    }
}
