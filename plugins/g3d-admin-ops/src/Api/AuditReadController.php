<?php

declare(strict_types=1);

namespace G3D\AdminOps\Api;

use G3D\AdminOps\Audit\AuditLogReader;
use G3D\AdminOps\Rbac\Capabilities;
use G3D\VendorBase\Rest\Security;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class AuditReadController
{
    public function __construct(private AuditLogReader $reader)
    {
    }

    public function registerRoutes(): void
    {
        \register_rest_route(
            'g3d/v1',
            '/audit',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'handle'],
                'permission_callback' => static fn (): bool =>
                    \function_exists('current_user_can')
                        ? \current_user_can(Capabilities::CAP_MANAGE_PUBLICATION)
                        : true,
            ]
        );
    }

    /** @return WP_REST_Response|WP_Error */
    public function handle(WP_REST_Request $req)
    {
        $nonce = Security::checkOptionalNonce($req);
        if ($nonce instanceof WP_Error) {
            // TODO(doc §auth): si el doc exige nonce, return $nonce;
            // si no, seguimos (best effort).
        }

        $events = $this->reader->getEvents();

        return new WP_REST_Response([
            'ok'     => true,
            'events' => $events,
        ], 200);
    }
}
