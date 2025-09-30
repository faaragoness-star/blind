<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Api;

use DateTimeImmutable;
use DateTimeZone;
use G3D\ValidateSign\Crypto\Verifier;
use G3D\ValidateSign\Domain\Expiry;
use G3D\ValidateSign\Validation\RequestValidator;
use G3D\VendorBase\Rest\Responses;
use G3D\VendorBase\Rest\Security;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @phpstan-type VerifyPayload array{
 *   sku_hash?: string,
 *   sku_signature?: string,
 *   snapshot_id?: string
 * }
 * @phpstan-type VerifyResponse array{
 *   ok: bool,
 *   request_id?: string
 * }
 *
 * REST controller to verify SKU signatures.
 */
class VerifyController
{
    private RequestValidator $validator;
    private Verifier $verifier;
    private Expiry $expiry;
    private string $publicKey;

    public function __construct(
        RequestValidator $validator,
        Verifier $verifier,
        Expiry $expiry,
        string $publicKey
    ) {
        $this->validator = $validator;
        $this->verifier  = $verifier;
        $this->expiry    = $expiry;
        $this->publicKey = $publicKey;
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'g3d/v1',
            '/verify',
            [
                'methods'  => 'POST',
                'callback' => [$this, 'handle'],
                // público según docs/Capa 3 — Validación, Firma y Caducidad — Actualizada
                // (slots Abiertos) — V2 (urls).md §2.2.
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
            $error = Responses::error(
                'E_MISSING_REQUIRED',
                'missing_required',
                'Faltan campos requeridos.'
            );
            // TODO(doc §errores): documentar missing_fields en errores REST.
            $error['missing_fields'] = $validation['missing'];
            $error['request_id']     = $requestId;

            return new WP_REST_Response($error, 400);
        }

        if (!empty($validation['type'])) {
            $error = Responses::error(
                'E_INVALID_PARAM',
                'invalid_param',
                'Tipos inválidos detectados.'
            );
            // TODO(doc §errores): documentar type_errors en errores REST.
            $error['type_errors'] = $validation['type'];
            $error['request_id']  = $requestId;

            return new WP_REST_Response($error, 400);
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        /** @var VerifyPayload $sanitized */
        $sanitized = $this->sanitizePayload($payload);

        $signature    = (string) ($sanitized['sku_signature'] ?? '');
        $verification = $this->verifier->verify($sanitized, $signature, $this->publicKey);

        if (!$verification['ok']) {
            $status      = $verification['http_status'] ?? 400;
            $errorDetail = $verification['detail'] ?? '';
            $errorResponse = Responses::error(
                $verification['code'],
                $verification['reason_key'],
                $errorDetail
            );
            $errorResponse['request_id'] = $requestId;

            return new WP_REST_Response($errorResponse, $status);
        }

        $expiresAt = $verification['expires_at'];

        if ($this->expiry->isExpired($expiresAt, $now)) {
            $errorResponse = Responses::error(
                'E_EXPIRED',
                'expired',
                'Caducado.'
            );
            $errorResponse['request_id'] = $requestId;

            return new WP_REST_Response($errorResponse, 409);
        }

        /** @var VerifyResponse $response */
        $response = Responses::ok([
            'request_id' => $requestId,
        ]);

        return new WP_REST_Response($response, 200);
    }

    private function generateRequestId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return VerifyPayload
     */
    private function sanitizePayload(array $payload): array
    {
        $sanitized = [];

        if (isset($payload['sku_hash']) && is_string($payload['sku_hash'])) {
            $sanitized['sku_hash'] = $payload['sku_hash'];
        }

        if (isset($payload['sku_signature']) && is_string($payload['sku_signature'])) {
            $sanitized['sku_signature'] = $payload['sku_signature'];
        }

        if (isset($payload['snapshot_id']) && is_string($payload['snapshot_id'])) {
            $sanitized['snapshot_id'] = $payload['snapshot_id'];
        }

        return $sanitized;
    }
}
