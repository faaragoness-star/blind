<?php

declare(strict_types=1);

namespace G3D\VendorBase\Rest;

use WP_Error;
use WP_REST_Request;

final class Security
{
    /**
     * @return true|WP_Error
     */
    public static function checkOptionalNonce(WP_REST_Request $req)
    {
        $nonce = $req->get_header('X-WP-Nonce') ?? $req->get_header('x-wp-nonce');
        if ($nonce === null || $nonce === '') {
            return true;
        }

        if (\function_exists('wp_verify_nonce') && \wp_verify_nonce($nonce, 'wp_rest')) {
            return true;
        }

        return new WP_Error(
            'rest_invalid_nonce',
            'Nonce inválido.',
            ['status' => 401]
        );
    }
}
