<?php

declare(strict_types=1);

namespace G3D\CatalogRules\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @phpstan-type RuleEntry array{key: string, value: mixed}
 * @phpstan-type RulesPayload array{
 *     rules: list<RuleEntry>,
 *     snapshot_id?: string,
 *     version?: string,
 *     generated_at?: string
 * }
 */
final class RulesReadController
{
    public function registerRoutes(): void
    {
        register_rest_route(
            'g3d/v1',
            '/catalog/rules',
            [
                'methods'  => 'GET',
                'callback' => [$this, 'handle'],
                // público según docs/plugin-2-g3d-catalog-rules.md §2 Visibilidad.
                'permission_callback' => '__return_true',
                'args' => [
                    'producto_id' => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                    'snapshot_id' => [
                        'required' => false,
                        'type'     => 'string',
                    ],
                    'locale' => [
                        'required' => false,
                        'type'     => 'string',
                    ],
                ],
            ]
        );
    }

    public function handle(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $productoIdParam = $request->get_param('producto_id');
        $productoId      = is_string($productoIdParam) ? trim($productoIdParam) : '';

        if ($productoId === '') {
            return new WP_Error(
                'rest_missing_required_params',
                'Missing required parameter(s): producto_id.',
                [
                    'status' => 400,
                    'params' => ['producto_id'],
                ]
            );
        }

        $snapshotParam = $request->get_param('snapshot_id');
        $snapshotId    = is_string($snapshotParam) && $snapshotParam !== '' ? $snapshotParam : null;

        $localeParam = $request->get_param('locale');
        $locale      = is_string($localeParam) && $localeParam !== '' ? $localeParam : null;

        return new WP_REST_Response(
            $this->buildPayload($productoId, $snapshotId, $locale),
            200
        );
    }

    /**
     * @return RulesPayload
     */
    private function buildPayload(string $productoId, ?string $snapshotId, ?string $locale): array
    {
        $rules = [
            [
                'key'   => 'material_to_modelos',
                'value' => [
                    'pieza:frame' => [
                        'mat:acetato' => ['modelo:FR_A_R'],
                    ],
                ],
            ],
            [
                'key'   => 'material_to_colores',
                'value' => [
                    'mat:acetato' => ['col:black', 'col:white'],
                ],
            ],
            [
                'key'   => 'material_to_texturas',
                'value' => [
                    'mat:acetato' => ['tex:acetato_base'],
                ],
            ],
            [
                'key'   => 'defaults',
                'value' => [
                    'mat:acetato' => [
                        'color'   => 'col:black',
                        'textura' => 'tex:acetato_base',
                    ],
                ],
            ],
            [
                'key'   => 'encaje',
                'value' => [
                    'clearance_por_material_mm' => [
                        'mat:acetato' => 0.10,
                    ],
                    // TODO(docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md §Encaje y morphs)
                ],
            ],
            [
                'key'   => 'slot_mapping_editorial',
                'value' => [
                    'pieza:frame' => [
                        'MAT_BASE' => [
                            'controles' => [
                                ['type' => 'material', 'affects_sku' => true],
                                ['type' => 'color', 'affects_sku' => true],
                                ['type' => 'textura', 'affects_sku' => true],
                                ['type' => 'acabado', 'affects_sku' => false],
                            ],
                            'defaults'  => [
                                'material' => 'mat:acetato',
                                'color'    => 'col:black',
                                'textura'  => 'tex:acetato_base',
                                // TODO(docs/plugin-2-g3d-catalog-rules.md §4.4 Slots (mapeo editorial))
                            ],
                            'visible' => true,
                            'order'   => 1,
                        ],
                    ],
                ],
            ],
        ];

        $payload = [
            'rules'       => $rules,
            'snapshot_id' => $snapshotId ?? 'snap:2025-09-27T18:45:00Z',
            'version'     => 'ver:2025-09-27T18:45:00Z',
            'producto_id' => $productoId,
        ];

        if ($locale !== null) {
            // TODO(docs/plugin-2-g3d-catalog-rules.md §4.5 i18n)
        }

        return $payload;
    }
}
