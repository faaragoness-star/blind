<?php

declare(strict_types=1);

namespace G3D\AdminOps\Api;

use G3D\AdminOps\Audit\AuditLogReader;
use G3D\AdminOps\Rbac\Capabilities;
use G3D\VendorBase\Rest\Responses;
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

        $pageParam = $req->get_param('page');
        $page = 1;
        if (\is_scalar($pageParam)) {
            $page = (int) $pageParam;
            if ($page < 1) {
                $page = 1;
            }
        }

        $perPageParam = $req->get_param('per_page');
        $perPage = 20;
        if (\is_scalar($perPageParam)) {
            $candidate = (int) $perPageParam;
            if ($candidate < 1) {
                $candidate = 1;
            }

            if ($candidate > 100) {
                $candidate = 100;
            }

            $perPage = $candidate;
        }

        /**
         * @var list<array{
         *   actor_id:string,
         *   action:string,
         *   what:string,
         *   occurred_at:string,
         *   context:array<string,mixed>
         * }> $events
         */
        $events = $this->reader->getEvents();

        $offset = ($page - 1) * $perPage;
        $items = \array_slice($events, $offset, $perPage);

        return new WP_REST_Response(
            Responses::ok([
                'items'    => $items,
                'page'     => $page,
                'per_page' => $perPage,
                'total'    => \count($events),
            ]),
            200
        );
    }
}
