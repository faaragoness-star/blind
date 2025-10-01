<?php

declare(strict_types=1);

namespace G3D\CatalogRules\Api;

use G3D\VendorBase\Rest\Security;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @phpstan-type SlotControl array{
 *   type: 'material'|'color'|'textura'|'acabado'|'shader_params',
 *   affects_sku: bool
 * }
 * @phpstan-type SlotDefaults array{
 *   material?: string,
 *   color?: string,
 *   textura?: string,
 *   acabado?: string
 * }
 * @phpstan-type SlotDefinition array{
 *   controles: list<SlotControl>,
 *   defaults?: SlotDefaults,
 *   visible: bool,
 *   order: int
 * }
 * @phpstan-type SlotMapping array<string, array<string, SlotDefinition>>
 * @phpstan-type MaterialDefaults array{
 *   color?: string,
 *   textura?: string
 * }
 * @phpstan-type SnapshotRules array{
 *   material_to_modelos: array<string, array<string, list<string>>>,
 *   material_to_colores: array<string, list<string>>,
 *   material_to_texturas: array<string, list<string>>,
 *   defaults: array<string, MaterialDefaults>,
 *   encaje: array{
 *     clearance_por_material_mm?: array<string, float>,
 *     encaje_policy?: array{
 *       driver: string,
 *       target: string,
 *       clearance_por_material_mm: array<string, float>,
 *       max_k?: float,
 *       safety?: array{
 *         espesor_min_mm?: float,
 *         radio_min_mm?: float
 *       }
 *     }
 *   },
 *   morph_rules: array<string, array<string, array{
 *     range_norm: array{float, float},
 *     maps_to: string
 *   }>>,
 *   slot_mapping_editorial: SlotMapping
 * }
 * @phpstan-type SnapshotEntities array{
 *   piezas: list<array{id:string, order:int}>,
 *   modelos: list<array{
 *     id: string,
 *     g3d_model_id: string,
 *     slots_detectados: list<string>
 *   }>,
 *   materiales: list<array{
 *     id: string,
 *     defaults?: MaterialDefaults
 *   }>,
 *   colores: list<array{id:string, hex:string}>,
 *   texturas: list<array{
 *     id: string,
 *     slot: string,
 *     defines_color?: bool,
 *     source?: string
 *   }>,
 *   acabados: list<array{id:string}>,
 *   morphs?: list<array{id:string, type?: string, analytics_key?: string}>
 * }
 * @phpstan-type SnapshotPayload array{
 *   id: string,
 *   schema_version: string,
 *   producto_id: string,
 *   entities: SnapshotEntities,
 *   rules: SnapshotRules,
 *   published_at: string,
 *   published_by: string,
 *   ver: string,
 *   notes?: string,
 *   locales?: list<string>,
 *   sku_policy?: array{include_morphs_in_sku?: bool}
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
                'methods'             => 'GET',
                'callback'            => [$this, 'handle'],
                // público según docs/plugin-2-g3d-catalog-rules.md §2 Visibilidad.
                'permission_callback' => '__return_true',
                'args'                => [
                    'producto_id' => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                    'locale'      => [
                        'required' => false,
                        'type'     => 'string',
                        // TODO(plugin-2-g3d-catalog-rules.md §6 APIs / Contratos):
                        // confirmar obligatoriedad de locale.
                    ],
                ],
            ]
        );
    }

    public function handle(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $nonceCheck = Security::checkOptionalNonce($request);
        if ($nonceCheck instanceof WP_Error) {
            // TODO(plugin-2-g3d-catalog-rules.md §12 Seguridad): definir si nonce obligatorio.
        }

        $missingParams = [];
        $invalidTypes  = [];

        $productoId = $this->readRequiredStringParam(
            $request->get_param('producto_id'),
            'producto_id',
            $missingParams,
            $invalidTypes
        );

        $locale = $this->readOptionalStringParam(
            $request->get_param('locale'),
            'locale',
            $invalidTypes
        );

        if ($missingParams !== []) {
            return new WP_Error(
                'rest_missing_required_params',
                'Faltan parámetros requeridos.',
                [
                    'status'          => 400,
                    'missing_params'  => $missingParams,
                ]
            );
        }

        if ($invalidTypes !== []) {
            return new WP_Error(
                'rest_invalid_param',
                'Parámetros inválidos.',
                [
                    'status'         => 400,
                    'invalid_params' => $invalidTypes,
                ]
            );
        }

        if ($locale !== null) {
            // TODO(plugin-2-g3d-catalog-rules.md §6 APIs / Contratos): aplicar filtro por locale.
        }

        $payload = $this->buildSnapshotPayload((string) $productoId);

        return new WP_REST_Response($payload, 200);
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

    /**
     * @param mixed $value
     * @param list<string> $invalid
     */
    private function readOptionalStringParam(
        mixed $value,
        string $name,
        array &$invalid
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            $invalid[] = $name;

            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            $invalid[] = $name;

            return null;
        }

        return $trimmed;
    }

    /**
     * @return SnapshotPayload
     */
    private function buildSnapshotPayload(string $productoId): array
    {
        return [
            'id'             => 'snap:2025-09-27T18:45:00Z',
            'schema_version' => '2.0.0',
            'producto_id'    => $productoId,
            'entities'       => [
                'piezas'    => [
                    ['id' => 'pieza:frame', 'order' => 1],
                    ['id' => 'pieza:temple', 'order' => 2],
                ],
                'modelos'   => [
                    [
                        'id'               => 'modelo:FR_A_R',
                        'g3d_model_id'     => 'g3d:FR_A_R',
                        'slots_detectados' => ['MAT_BASE', 'MAT_TIP'],
                    ],
                ],
                'materiales' => [
                    [
                        'id'       => 'mat:acetato',
                        'defaults' => [
                            'color'   => 'col:black',
                            'textura' => 'tex:acetato_base',
                        ],
                    ],
                ],
                'colores'   => [
                    ['id' => 'col:black', 'hex' => '#000000'],
                ],
                'texturas'  => [
                    [
                        'id'            => 'tex:acetato_base',
                        'slot'          => 'MAT_BASE',
                        'defines_color' => true,
                        'source'        => 'embedded',
                    ],
                ],
                'acabados'  => [
                    ['id' => 'fin:clearcoat_high'],
                ],
                'morphs'    => [],
            ],
            'rules'          => [
                'material_to_modelos'   => [
                    'pieza:frame' => [
                        'mat:acetato' => ['modelo:FR_A_R'],
                    ],
                ],
                'material_to_colores'   => [
                    'mat:acetato' => ['col:black', 'col:white'],
                ],
                'material_to_texturas'  => [
                    'mat:acetato' => ['tex:acetato_base'],
                ],
                'defaults'              => [
                    'mat:acetato' => [
                        'color'   => 'col:black',
                        'textura' => 'tex:acetato_base',
                    ],
                ],
                'encaje'                => [
                    'clearance_por_material_mm' => [
                        'mat:acetato' => 0.10,
                    ],
                ],
                'morph_rules'           => [],
                'slot_mapping_editorial' => [
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
                            ],
                            'visible'   => true,
                            'order'     => 1,
                        ],
                    ],
                ],
            ],
            'published_at'  => '2025-09-27T18:45:00Z',
            'published_by'  => 'user:admin',
            'notes'         => 'v2 — slots abiertos',
            'ver'           => 'ver:2025-09-27T18:45:00Z',
            'locales'       => ['es-ES'],
            'sku_policy'    => ['include_morphs_in_sku' => false],
        ];
    }
}
