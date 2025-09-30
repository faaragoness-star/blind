<?php

declare(strict_types=1);

namespace G3D\CatalogRules\Api;

use G3D\VendorBase\Rest\Responses;
use G3D\VendorBase\Rest\Security;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @phpstan-type RuleEntry array{
 *   key: string,
 *   value: mixed
 * }
 * @phpstan-type RulesPayload array{
 *   rules: list<RuleEntry>,
 *   snapshot_id?: string,
 *   version?: string
 * }
 */
// TODO(Plugin 2 §payload): añadir metadatos cuando el doc lo fije.
final class RulesReadController
{
    public function registerRoutes(): void
    {
        register_rest_route(
            'g3d/v1',
            '/catalog/rules',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'handle'],
                // público según docs/plugin-2-g3d-catalog-rules.md §2 Visibilidad.
                'permission_callback' => '__return_true',
                'args'                => [
                    'producto_id' => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                    'snapshot_id' => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                    'locale'      => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                ],
            ]
        );
    }

    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $nonceCheck = Security::checkOptionalNonce($request);

        if ($nonceCheck instanceof WP_Error) {
            // TODO(docs/plugin-2-g3d-catalog-rules.md §12 Seguridad): confirmar bloqueo ante nonce inválido.
        }

        $missingParams = [];
        $invalidTypes  = [];

        $this->readRequiredStringParam(
            $request->get_param('producto_id'),
            'producto_id',
            $missingParams,
            $invalidTypes
        );

        $this->readRequiredStringParam(
            $request->get_param('snapshot_id'),
            'snapshot_id',
            $missingParams,
            $invalidTypes
        );

        $this->readRequiredStringParam(
            $request->get_param('locale'),
            'locale',
            $missingParams,
            $invalidTypes
        );

        if ($missingParams !== []) {
            return new WP_REST_Response(
                Responses::error(
                    'E_MISSING_PARAMS',
                    'missing_params',
                    'Faltan parámetros requeridos.'
                ),
                400
            );
        }

        if ($invalidTypes !== []) {
            return new WP_REST_Response(
                Responses::error(
                    'E_INVALID_PARAMS',
                    'invalid_params',
                    'Parámetros inválidos.'
                ),
                400
            );
        }

        /** @var RulesPayload $payload */
        $payload = [
            'rules'       => [],
            'snapshot_id' => 'snap:2025-09-27T18:45:00Z',
            'version'     => 'ver:2025-09-27T18:45:00Z',
            // TODO(Plugin 2 §payload): añadir metadatos cuando el doc lo fije.
        ];

        return new WP_REST_Response(
            Responses::ok($payload),
            200
        );
    }

    /**
     * @param mixed $value
     * @param list<string> $missing
     * @param list<string> $invalid
     */
    private function readRequiredStringParam(
        mixed $value,
        string $name,
        array &$missing,
        array &$invalid
    ): ?string {
        if ($value === null) {
            $missing[] = $name;

            return null;
        }

        if (!is_string($value)) {
            $invalid[] = $name;

            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            $missing[] = $name;

            return null;
        }

        return $trimmed;
    }
}
