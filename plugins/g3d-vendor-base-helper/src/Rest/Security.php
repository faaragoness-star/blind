<?php

declare(strict_types=1);

namespace G3D\VendorBase\Rest;

use WP_Error;
use WP_REST_Request;

final class Security
{
    /**
     * Verifica nonce REST si está presente. Si falta y los docs no lo requieren,
     * no bloquea (best effort). Si los docs lo exigen, cambiar a "required".
     *
     * @return true|WP_Error
     */
    public static function checkOptionalNonce(WP_REST_Request $req, string $action = 'wp_rest'): true|WP_Error
    {
        // Header estándar de WP o query/body.
        $provided = self::extractNonce($req);

        if ($provided === null || $provided === '') {
            // TODO(doc §auth): si el doc lo requiere, devolver WP_Error 401.
            return true;
        }

        if (\function_exists('wp_verify_nonce') && \wp_verify_nonce($provided, $action)) {
            return true;
        }

        return new WP_Error('rest_invalid_nonce', 'Nonce inválido.', ['status' => 401]);
    }

    private static function extractNonce(WP_REST_Request $req): ?string
    {
        $header = $req->get_header('X-WP-Nonce');
        if ($header !== null && $header !== '') {
            return $header;
        }

        if (method_exists($req, 'get_param')) {
            $param = $req->get_param('__nonce') ?? $req->get_param('_wpnonce');
            if (is_string($param)) {
                return $param;
            }
        }

        if (method_exists($req, 'get_params')) {
            $params = $req->get_params();
            if (is_array($params)) {
                $param = $params['__nonce'] ?? $params['_wpnonce'] ?? null;
                if (is_string($param)) {
                    return $param;
                }
            }
        }

        if (method_exists($req, 'get_body') && method_exists($req, 'get_json_params')) {
            $params = $req->get_json_params();
            if (is_array($params)) {
                $param = $params['__nonce'] ?? $params['_wpnonce'] ?? null;
                if (is_string($param)) {
                    return $param;
                }
            }
        }

        return null;
    }
}
