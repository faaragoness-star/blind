<?php

declare(strict_types=1);

namespace G3dCatalogRules\Contracts;

/**
 * Contrato editorial para el borrador del catálogo.
 *
 * Referencias:
 * - docs/Plugin 2 — G3d Catalog Rules — Informe.md
 * - docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md
 * - docs/Capa 1 Identificadores Y Naming — Actualizada (slots Abiertos).md
 */
final class CatalogRule
{
    public const JSON_SCHEMA = __DIR__ . '/../../schemas/catalog-rules.schema.json';

    public const SEMVER_PATTERN = '^\\d+\\.\\d+\\.\\d+$';

    public const EDITORIAL_ID_PATTERN = '^(prod|pieza|modelo|mat|col|tex|fin|morph|sku):[a-z0-9-]{3,48}$';

    public const SLOT_CONTROL_TYPES = [
        'material',
        'color',
        'textura',
        'acabado',
        'shader_params',
    ];

    public const COLOR_HEX_PATTERN = '^#[A-F0-9]{6}$';

    public const SNAPSHOT_PUBLISH_TARGETS = ['lug', 'socket'];

    /**
     * Matriz base de reglas editoriales:
     *
     * @var list<string> MATERIAL_RULE_KEYS
     */
    public const MATERIAL_RULE_KEYS = [
        'material_to_modelos',
        'material_to_colores',
        'material_to_texturas',
        'defaults',
        'encaje',
        'slot_mapping_editorial',
    ];
}
