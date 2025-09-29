<?php

declare(strict_types=1);

namespace G3D\AdminOps\Api;

use G3D\AdminOps\Audit\AuditLogReader;
use G3D\AdminOps\Rbac\Capabilities;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @phpstan-type AuditEvent array{
 *   actor_id: string,
 *   action: string,
 *   what: string,
 *   occurred_at: string,
 *   context: array<string,mixed>
 * }
 * @phpstan-type AuditList array{ events: list<AuditEvent> }
 */
final class AuditReadController
{
    public function __construct(private AuditLogReader $reader)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'g3d/v1',
            '/admin-ops/audit',
            [
                'methods' => 'GET',
                'callback' => [$this, 'handle'],
                'permission_callback' => static fn (): bool => current_user_can(
                    Capabilities::CAP_MANAGE_PUBLICATION
                ),
            ]
        );
    }

    public function handle(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (!current_user_can(Capabilities::CAP_MANAGE_PUBLICATION)) {
            return new WP_Error('rest_forbidden', 'Forbidden', ['status' => 403]);
        }

        $events = $this->reader->getEvents();

        // TODO(doc §paginación): exponer paginación si se define en la guía.

        return new WP_REST_Response(['events' => $events], 200);
    }
}
