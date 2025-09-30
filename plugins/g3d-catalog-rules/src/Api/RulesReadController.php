<?php

declare(strict_types=1);

namespace G3D\CatalogRules\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @phpstan-type SlotControl array{type: string, affects_sku: bool}
 * @phpstan-type SlotDefinition array{
 *     controles: list<SlotControl>,
 *     defaults: array<string, string>,
 *     visible: bool,
 *     order: int
 * }
 * @phpstan-type RulesSections array{
 *     material_to_modelos: array<string, array<string, list<string>>>,
 *     material_to_colores: array<string, list<string>>,
 *     material_to_texturas: array<string, list<string>>,
 *     defaults: array<string, array<string, string>>,
 *     encaje: array<string, mixed>,
 *     slot_mapping_editorial: array<string, array<string, SlotDefinition>>
 * }
 * @phpstan-type SnapshotEntities array{
 *     piezas: list<array{id: string, order: int}>,
 *     modelos: list<array{id: string, g3d_model_id: string, slots_detectados: list<string>}>,
 *     materiales: list<array{id: string, defaults: array<string, string>}>,
 *     colores: list<array{id: string, hex: string}>,
 *     texturas: list<array{id: string, slot: string, defines_color: bool, source: string}>,
 *     acabados: list<array{id: string}>
 * }
 * @phpstan-type RulesPayload array{
 *     id: string,
 *     schema_version: string,
 *     producto_id: string,
 *     entities: SnapshotEntities,
 *     rules: RulesSections,
 *     published_at: string,
 *     published_by: string,
 *     notes: string,
 *     ver: string,
 *     locales: list<string>,
 *     sku_policy: array{include_morphs_in_sku: bool}
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

        $localeParam = $request->get_param('locale');
        $locale      = is_string($localeParam) && $localeParam !== '' ? $localeParam : null;

        return new WP_REST_Response(
            $this->buildPayload($productoId, $locale),
            200
        );
    }

    /**
     * @return RulesPayload
     */
    private function buildPayload(string $productoId, ?string $locale): array
    {
        $localeList = ['es-ES'];

        if ($locale !== null && $locale !== '') {
            $localeList = [$locale];
        }

        return [
            'id' => 'snap:2025-09-27T18:45:00Z',
            // TODO(docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md §Snapshot publicado)
            'schema_version' => '2.0.0',
            'producto_id' => $productoId,
            'entities' => [
                'piezas' => [
                    ['id' => 'pieza:frame', 'order' => 1],
                    ['id' => 'pieza:temple', 'order' => 2],
                ],
                'modelos' => [
                    [
                        'id' => 'modelo:FR_A_R',
                        'g3d_model_id' => 'g3d:FR_A_R',
                        'slots_detectados' => ['MAT_BASE', 'MAT_TIP'],
                    ],
                ],
                'materiales' => [
                    [
                        'id' => 'mat:acetato',
                        'defaults' => [
                            'color' => 'col:black',
                            'textura' => 'tex:acetato_base',
                            // TODO(docs/plugin-2-g3d-catalog-rules.md §4.3 Reglas)
                        ],
                    ],
                ],
                'colores' => [
                    [
                        'id' => 'col:black',
                        'hex' => '#000000',
                    ],
                ],
                'texturas' => [
                    [
                        'id' => 'tex:acetato_base',
                        'slot' => 'MAT_BASE',
                        'defines_color' => true,
                        'source' => 'embedded',
                    ],
                ],
                'acabados' => [
                    ['id' => 'fin:clearcoat_high'],
                ],
                // TODO(docs/plugin-2-g3d-catalog-rules.md §4.2 Entidades)
            ],
            'rules' => [
                'material_to_modelos' => [
                    'pieza:frame' => [
                        'mat:acetato' => ['modelo:FR_A_R'],
                    ],
                ],
                'material_to_colores' => [
                    'mat:acetato' => ['col:black', 'col:white'],
                ],
                'material_to_texturas' => [
                    'mat:acetato' => ['tex:acetato_base'],
                ],
                'defaults' => [
                    'mat:acetato' => [
                        'color' => 'col:black',
                        'textura' => 'tex:acetato_base',
                    ],
                ],
                'encaje' => [
                    'clearance_por_material_mm' => [
                        'mat:acetato' => 0.10,
                    ],
                    // TODO(docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md §Encaje y morphs)
                ],
                'slot_mapping_editorial' => [
                    'pieza:frame' => [
                        'MAT_BASE' => [
                            'controles' => [
                                ['type' => 'material', 'affects_sku' => true],
                                ['type' => 'color', 'affects_sku' => true],
                                ['type' => 'textura', 'affects_sku' => true],
                                ['type' => 'acabado', 'affects_sku' => false],
                                // TODO(docs/plugin-2-g3d-catalog-rules.md §4.4 Slots (mapeo editorial))
                            ],
                            'defaults' => [
                                'material' => 'mat:acetato',
                                'color' => 'col:black',
                                'textura' => 'tex:acetato_base',
                            ],
                            'visible' => true,
                            'order' => 1,
                        ],
                    ],
                ],
            ],
            'published_at' => '2025-09-27T18:45:00Z',
            'published_by' => 'user:admin',
            'notes' => 'v2 — slots abiertos',
            'ver' => 'ver:2025-09-27T18:45:00Z',
            'locales' => $localeList,
            'sku_policy' => [
                'include_morphs_in_sku' => false,
            ],
        ];
    }
}
