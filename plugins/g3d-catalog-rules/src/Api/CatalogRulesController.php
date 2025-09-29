<?php

declare(strict_types=1);

namespace G3dCatalogRules\Api;

use WP_REST_Request;
use WP_REST_Response;

final class CatalogRulesController
{
    public function registerRoutes(): void
    {
        register_rest_route(
            'g3d/v1',
            '/catalog-rules',
            [
                'methods' => 'GET',
                'callback' => [$this, 'getCatalogRules'],
                'permission_callback' => '__return_true',
            ]
        );
    }

    public function getCatalogRules(WP_REST_Request $request): WP_REST_Response
    {
        $payload = [
            'ok' => true,
            'rules' => [],
            'meta' => [
                'todo' => 'Definir metadatos públicos (docs/plugin-2-g3d-catalog-rules.md §5 Modelo de datos).',
            ],
        ];

        return new WP_REST_Response($payload, 200);
    }
}
