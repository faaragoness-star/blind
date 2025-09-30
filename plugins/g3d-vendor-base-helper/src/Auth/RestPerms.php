<?php

declare(strict_types=1);

namespace G3D\VendorBase\Auth;

use WP_REST_Request;

final class RestPerms
{
    public static function canUse(string $cap, ?WP_REST_Request $req = null): bool
    {
        $nonce = null;

        if ($req !== null) {
            $nonce = $req->get_header('X-WP-Nonce');

            if ($nonce === null || $nonce === '') {
                $nonce = $req->get_header('x-wp-nonce');
            }
        }

        if (!is_string($nonce) || $nonce === '') {
            return false;
        }

        if (!\wp_verify_nonce($nonce, 'wp_rest')) {
            return false;
        }

        return \current_user_can($cap);
    }
}
