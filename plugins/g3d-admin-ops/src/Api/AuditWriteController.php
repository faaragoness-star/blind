<?php

declare(strict_types=1);

namespace G3D\AdminOps\Api;

use G3D\AdminOps\Audit\EditorialActionLogger;
use G3D\AdminOps\Rbac\Capabilities;
use G3D\VendorBase\Rest\Responses;
use G3D\VendorBase\Rest\Security;
use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class AuditWriteController
{
    public function __construct(private EditorialActionLogger $logger)
    {
    }

    public function registerRoutes(): void
    {
        \register_rest_route(
            'g3d/v1',
            '/audit',
            [
                'methods'             => 'POST',
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
        }

        $payload = $req->get_json_params();
        if (!\is_array($payload)) {
            $payload = [];
        }

        /**
         * @var array{actor_id?:string,action?:string,context?:array<string,mixed>} $payload
         */

        $actor  = (string)($payload['actor_id'] ?? '');
        $action = (string)($payload['action'] ?? '');
        $ctx    = \is_array($payload['context'] ?? null) ? (array) $payload['context'] : [];

        if ($actor === '' || $action === '') {
            $err = Responses::error(
                'E_INVALID_INPUT',
                'invalid_input',
                'actor_id y action son requeridos.'
            );

            return new WP_REST_Response($err, 400);
        }

        try {
            $this->logger->logAction($actor, $action, $ctx);
        } catch (InvalidArgumentException $e) {
            $err = Responses::error('E_INVALID_CONTEXT', 'invalid_context', $e->getMessage());

            return new WP_REST_Response($err, 400);
        }

        return new WP_REST_Response(Responses::ok(['saved' => true]), 201);
    }
}
