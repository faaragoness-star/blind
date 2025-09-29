<?php

declare(strict_types=1);

namespace G3dCatalogRules\Contracts;

/**
 * Contrato público de snapshot publicado.
 *
 * Referencias:
 * - docs/Plugin 2 — G3d Catalog Rules — Informe.md
 * - docs/Capa 2 Schemas Snapshot — Actualizada (slots Abiertos).md
 * - docs/Capa 1 Identificadores Y Naming — Actualizada (slots Abiertos).md
 */
final class SnapshotSchema
{
    public const JSON_SCHEMA = __DIR__ . '/../../schemas/snapshot.schema.json';

    public const SNAPSHOT_ID_PATTERN = '^snap:\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$';

    public const VERSION_ID_PATTERN = '^ver:\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}Z$';

    public const LOCALE_PATTERN = '^[a-z]{2}-[A-Z]{2}$';

    public const SKU_FLAG_DEFAULT = false;
}
