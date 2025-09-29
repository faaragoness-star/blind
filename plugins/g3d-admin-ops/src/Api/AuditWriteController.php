<?php

declare(strict_types=1);

namespace G3D\AdminOps\Api;

use DateTimeImmutable;
use G3D\AdminOps\Audit\EditorialActionLogger;
use G3D\AdminOps\Rbac\Capabilities;
use G3D\VendorBase\Rest\Responses;
use G3D\VendorBase\Rest\Security;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @phpstan-type AuditLogContext array{
 *   what: string,
 *   occurred_at?: string,
 *   snapshot_id?: string,
 *   resultado?: string,
 *   latency_ms?: int
 * }
 * @phpstan-type AuditLogPayload array{
 *   actor_id: string,
 *   action: string,
 *   context: AuditLogContext
 * }
 */
final class AuditWriteController
{
    public function __construct(private EditorialActionLogger $logger)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'g3d/v1',
            '/admin-ops/audit/log',
            [
                'methods' => 'POST',
                'callback' => [$this, 'handle'],
                'permission_callback' => static fn (): bool => current_user_can(
                    Capabilities::CAP_MANAGE_PUBLICATION
                ),
            ]
        );
    }

    public function handle(WP_REST_Request $req): WP_REST_Response|WP_Error
    {
        $nonceCheck = Security::checkOptionalNonce($req);
        if ($nonceCheck instanceof WP_Error) {
            // TODO(doc §auth): si el doc requiere nonce, return $nonceCheck;
        }

        if (!current_user_can(Capabilities::CAP_MANAGE_PUBLICATION)) {
            return new WP_REST_Response(
                Responses::error('rest_forbidden', 'rest_forbidden', 'Forbidden', ['status' => 403]),
                403
            );
        }

        /** @var array<string,mixed> $payload */
        $payload = $req->get_json_params();

        $missingFields = [];
        $typeErrors = [];

        $actorId = $payload['actor_id'] ?? null;
        if (!array_key_exists('actor_id', $payload)) {
            $missingFields[] = 'actor_id';
        } elseif (!is_string($actorId) || $actorId === '') {
            $typeErrors[] = 'actor_id';
        }

        $action = $payload['action'] ?? null;
        if (!array_key_exists('action', $payload)) {
            $missingFields[] = 'action';
        } elseif (!is_string($action) || $action === '') {
            $typeErrors[] = 'action';
        }

        $rawContext = null;
        if (!array_key_exists('context', $payload)) {
            $missingFields[] = 'context';
        } elseif (!is_array($payload['context'])) {
            $typeErrors[] = 'context';
        } else {
            /** @var array<string,mixed> $rawContextCandidate */
            $rawContextCandidate = $payload['context'];
            $rawContext = $rawContextCandidate;
        }

        if (is_array($rawContext)) {
            $what = $rawContext['what'] ?? null;
            if (!array_key_exists('what', $rawContext)) {
                $missingFields[] = 'context.what';
            } elseif (!is_string($what) || $what === '') {
                $typeErrors[] = 'context.what';
            }
        }

        if (is_array($rawContext) && isset($rawContext['occurred_at'])) {
            if (!is_string($rawContext['occurred_at']) || $rawContext['occurred_at'] === '') {
                $typeErrors[] = 'context.occurred_at';
            } elseif (!$this->isIso8601($rawContext['occurred_at'])) {
                $typeErrors[] = 'context.occurred_at';
            }
        }

        if (is_array($rawContext) && isset($rawContext['snapshot_id']) && !is_string($rawContext['snapshot_id'])) {
            $typeErrors[] = 'context.snapshot_id';
        }

        if (is_array($rawContext) && isset($rawContext['resultado']) && !is_string($rawContext['resultado'])) {
            $typeErrors[] = 'context.resultado';
        }

        if (is_array($rawContext) && isset($rawContext['latency_ms']) && !is_int($rawContext['latency_ms'])) {
            $typeErrors[] = 'context.latency_ms';
        }

        if ($missingFields !== []) {
            return new WP_REST_Response(
                Responses::error(
                    'rest_missing_required_params',
                    'rest_missing_required_params',
                    'Faltan campos requeridos.',
                    [
                        'status' => 400,
                        'missing_fields' => $missingFields,
                    ]
                ),
                400
            );
        }

        if ($typeErrors !== []) {
            return new WP_REST_Response(
                Responses::error(
                    'rest_invalid_param',
                    'rest_invalid_param',
                    'Tipos inválidos detectados.',
                    [
                        'status' => 400,
                        'type_errors' => $typeErrors,
                    ]
                ),
                400
            );
        }

        \assert(is_array($rawContext));

        /** @var AuditLogContext $context */
        $context = [
            'what' => $rawContext['what'],
        ];

        if (isset($rawContext['occurred_at'])) {
            $context['occurred_at'] = $rawContext['occurred_at'];
        }

        if (isset($rawContext['snapshot_id'])) {
            $context['snapshot_id'] = $rawContext['snapshot_id'];
        }

        if (isset($rawContext['resultado'])) {
            $context['resultado'] = $rawContext['resultado'];
        }

        if (isset($rawContext['latency_ms'])) {
            $context['latency_ms'] = $rawContext['latency_ms'];
        }

        /** @var string $actorId */
        /** @var string $action */
        $this->logger->logAction($actorId, $action, $context);

        return new WP_REST_Response(['ok' => true], 200);
    }

    private function isIso8601(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat(DATE_ATOM, $value);
        if ($date === false) {
            return false;
        }

        $errors = DateTimeImmutable::getLastErrors();
        if ($errors === false) {
            return true;
        }

        return $errors['warning_count'] === 0 && $errors['error_count'] === 0;
    }
}
