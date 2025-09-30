<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Api;

use DateTimeImmutable;
use DateTimeZone;
use G3D\ValidateSign\Crypto\Signer;
use G3D\ValidateSign\Domain\Expiry;
use G3D\ValidateSign\Validation\RequestValidator;
use G3D\VendorBase\Auth\RestPerms;
use G3D\VendorBase\Rest\Responses;
use G3D\VendorBase\Rest\Security;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST controller to sign SKU payloads.
 *
 * Requires the CAP_USE_API capability to access the REST endpoint.
 */
class ValidateSignController
{
    /**
     * Capability required to consume the Validate & Sign REST API.
     */
    public const CAP_USE_API = 'g3d_validate_use_api';

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
                'permission_callback' => static function (WP_REST_Request $request): bool {
                    return RestPerms::canUse(self::CAP_USE_API, $request);
                },
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
        $payload = $request->get_json_params();

        if (!is_array($payload)) {
            $payload = [];
        }

        $validation = $this->validator->validate($payload);

        if (!empty($validation['missing'])) {
            $error = Responses::error(
                'rest_missing_required_params',
                'rest_missing_required_params',
                'Faltan campos requeridos.'
            );
            $error['status'] = 400;
            $error['missing_fields'] = $validation['missing'];
            $error['request_id'] = $requestId;

            return new WP_REST_Response($error, 400);
        }

        if (!empty($validation['type'])) {
            $error = Responses::error(
                'rest_invalid_param',
                'rest_invalid_param',
                'Tipos inválidos detectados.'
            );
            $error['status'] = 400;
            $error['type_errors'] = $validation['type'];
            $error['request_id'] = $requestId;

            return new WP_REST_Response($error, 400);
        }

        // TODO: Validar snapshot, IDs y reglas de catálogo según docs/plugin-3-g3d-validate-sign.md §6.1.

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $expiresAt = $this->expiry->calculate(null, $now);
        $signing = $this->signer->sign($payload, $this->privateKey, $expiresAt);

        $snapshotId = isset($payload['snapshot_id']) ? (string) $payload['snapshot_id'] : '';

        $summary = $payload['summary'] ?? '{{pieza}} · {{material}} — {{color}} · {{textura}} · {{acabado}}';
// TODO: Calcular summary real (docs/Capa 1 Identificadores Y Naming —
// Actualizada (slots Abiertos).md, plantilla resumen).

        $response = Responses::ok([
            'sku_hash' => $signing['sku_hash'],
            'sku_signature' => $signing['signature'],
            'expires_at' => $this->expiry->format($expiresAt),
            'snapshot_id' => $snapshotId,
            'summary' => $summary,
            'request_id' => $requestId,
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
}
