<?php

declare(strict_types=1);

namespace G3D\CatalogRules\Api;

use G3D\VendorBase\Rest\Responses;
use G3D\VendorBase\Rest\Security;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @phpstan-type SlotControl array{
 *     type: 'material'|'color'|'textura'|'acabado',
 *     affects_sku: bool
 * }
 * @phpstan-type SlotDefaults array<string, string>
 * @phpstan-type SlotConfig array{
 *     controles: list<SlotControl>,
 *     defaults: SlotDefaults,
 *     visible: bool,
 *     order: int
 * }
 * @phpstan-type SlotMapping array<string, array<string, SlotConfig>>
 * @phpstan-type RulesSection array{
 *     material_to_modelos: array<string, array<string, list<string>>>,
 *     material_to_colores: array<string, list<string>>,
 *     material_to_texturas: array<string, list<string>>,
 *     defaults: array<string, array<string, string>>,
 *     encaje: array<string, array<string, float>>,
 *     slot_mapping_editorial: SlotMapping
 * }
 * @phpstan-type EntitiesSection array{
 *     piezas: list<array{id: string, order: int}>,
 *     modelos: list<array{id: string, g3d_model_id: string, slots_detectados: list<string>}>,
 *     materiales: list<array{id: string, defaults: array<string, string>}>,
 *     colores: list<array{id: string, hex: string}>,
 *     texturas: list<array{
 *         id: string,
 *         slot: string,
 *         defines_color: bool,
 *         source: string
 *     }>,
 *     acabados: list<array{id: string}>
 * }
 * @phpstan-type RulesPayload array{
 *     id: string,
 *     schema_version: string,
 *     producto_id: string,
 *     published_at: string,
 *     published_by: string,
 *     notes: string,
 *     ver: string,
 *     locales: list<string>,
 *     sku_policy: array{include_morphs_in_sku: bool},
 *     entities: EntitiesSection,
 *     rules: RulesSection
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
                'methods' => 'GET',
                'callback' => [$this, 'handle'],
                'permission_callback' => '__return_true', // público según docs/plugin-2-g3d-catalog-rules.md §2.
                'args' => [
                    'producto_id' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'locale' => [
                        'required' => false,
                        'type' => 'string',
                    ],
                ],
            ]
        );
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function handle(WP_REST_Request $request)
    {
        $nonceCheck = Security::checkOptionalNonce($request);
        if ($nonceCheck instanceof WP_Error) {
            // TODO(doc §auth): si el doc requiere nonce, return $nonceCheck;
        }

        $params = $this->getRequestParams($request);
        $productoId = isset($params['producto_id']) ? (string) $params['producto_id'] : '';

        if ($productoId === '') {
            $error = Responses::error(
                'g3d_catalog_rules_missing_producto_id',
                'g3d_catalog_rules_missing_producto_id',
                'Missing required query parameter: producto_id.'
            );
            $error['status'] = 400;

            return new WP_REST_Response($error, 400);
        }

        $locale = isset($params['locale']) ? (string) $params['locale'] : null;

        return new WP_REST_Response(
            $this->createPayload($productoId, $locale),
            200
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getRequestParams(WP_REST_Request $request): array
    {
        if (method_exists($request, 'get_params')) {
            /** @var array<string, mixed> $params */
            $params = $request->get_params();

            return $params;
        }

        /** @var array<string, mixed> $params */
        $params = $request->get_json_params();

        return $params;
    }

    /**
     * @return RulesPayload
     */
    private function createPayload(string $productoId, ?string $locale): array
    {
        $localeList = $locale !== null && $locale !== '' ? [$locale] : ['es-ES'];

        return [
            'id' => 'snap:2025-09-27T18:45:00Z',
            'schema_version' => '2.0.0',
            'producto_id' => $productoId,
            'published_at' => '2025-09-27T18:45:00Z',
            'published_by' => 'user:admin',
            'notes' => 'v2 — slots abiertos',
            'ver' => 'ver:2025-09-27T18:45:00Z',
            'locales' => $localeList,
            'sku_policy' => [
                'include_morphs_in_sku' => false,
            ],
            'entities' => [
                'piezas' => [
                    [
                        'id' => 'pieza:frame',
                        'order' => 1,
                        /*
                         * TODO(docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md §1.2 Pieza)
                         */
                    ],
                    [
                        'id' => 'pieza:temple',
                        'order' => 2,
                        /*
                         * TODO(docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md §1.2 Pieza)
                         */
                    ],
                ],
                'modelos' => [
                    [
                        'id' => 'modelo:FR_A_R',
                        'g3d_model_id' => 'modelo:FR_A_R',
                        'slots_detectados' => ['MAT_BASE', 'MAT_TIP'],
                        /*
                         * TODO(docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md §1.3 Modelo)
                         */
                    ],
                ],
                'materiales' => [
                    [
                        'id' => 'mat:acetato',
                        'defaults' => [
                            'color' => 'col:black',
                            'textura' => 'tex:acetato_base',
                        ],
                        /*
                         * TODO(docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md §1.4 Material)
                         */
                    ],
                ],
                'colores' => [
                    [
                        'id' => 'col:black',
                        'hex' => '#000000',
                        /*
                         * TODO(docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md §1.5 Color)
                         */
                    ],
                ],
                'texturas' => [
                    [
                        'id' => 'tex:acetato_base',
                        'slot' => 'MAT_BASE',
                        'defines_color' => true,
                        'source' => 'embedded',
                        /*
                         * TODO(docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md §1.6 Textura)
                         */
                    ],
                ],
                'acabados' => [
                    [
                        'id' => 'fin:clearcoat_high',
                        /*
                         * TODO(docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md §1.7 Acabado)
                         */
                    ],
                ],
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
                                [
                                    'type' => 'material',
                                    'affects_sku' => true,
                                ],
                                [
                                    'type' => 'color',
                                    'affects_sku' => true,
                                ],
                                [
                                    'type' => 'textura',
                                    'affects_sku' => true,
                                ],
                                [
                                    'type' => 'acabado',
                                    'affects_sku' => false,
                                ],
                            ],
                            'defaults' => [
                                'material' => 'mat:acetato',
                                'color' => 'col:black',
                                'textura' => 'tex:acetato_base',
                                // TODO(docs/Plugin 2 — G3D Catalog Rules — Informe.md §4.4 Slots (mapeo editorial))
                            ],
                            'visible' => true,
                            'order' => 1,
                        ],
                    ],
                ],
                // TODO(docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md §Rules: morph_rules)
            ],
        ];
    }
}
