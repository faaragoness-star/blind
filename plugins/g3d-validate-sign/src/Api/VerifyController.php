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
        $this->verifier = $verifier;
        $this->expiry = $expiry;
        $this->publicKey = $publicKey;
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'g3d/v1',
            '/verify',
            [
                'methods' => 'POST',
                'callback' => [$this, 'handle'],
                'permission_callback' => '__return_true', // público según docs/plugin-3-g3d-validate-sign.md §2.
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
            return new WP_REST_Response(
                Responses::error(
                    'rest_missing_required_params',
                    'rest_missing_required_params',
                    'Faltan campos requeridos.',
                    [
                        'status' => 400,
                        'missing_fields' => $validation['missing'],
                        'request_id' => $requestId,
                    ]
                ),
                400
            );
        }

        if (!empty($validation['type'])) {
            return new WP_REST_Response(
                Responses::error(
                    'rest_invalid_param',
                    'rest_invalid_param',
                    'Tipos inválidos detectados.',
                    [
                        'status' => 400,
                        'type_errors' => $validation['type'],
                        'request_id' => $requestId,
                    ]
                ),
                400
            );
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $signature = (string) ($payload['sku_signature'] ?? '');
        $verification = $this->verifier->verify($payload, $signature, $this->publicKey);

        if (!$verification['ok']) {
            $errorResponse = Responses::error(
                $verification['code'],
                $verification['reason_key'],
                $verification['detail'],
                ['request_id' => $requestId]
            );

            return new WP_REST_Response($errorResponse, 400);
        }

        $expiresAt = $verification['expires_at'];

        if ($this->expiry->isExpired($expiresAt, $now)) {
            $errorResponse = Responses::error(
                'E_SIGN_EXPIRED',
                'sign_expired',
                'Caducidad agotada (ver docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                    . '(slots Abiertos) — V2 (urls).md).',
                ['request_id' => $requestId]
            );

            return new WP_REST_Response($errorResponse, 400);
        }

        $response = [
            'ok' => true,
            'request_id' => $requestId,
        ];

        return new WP_REST_Response($response, 200);
    }

    private function generateRequestId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
