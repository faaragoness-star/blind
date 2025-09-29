<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Service;

use G3D\ModelsManager\Validation\GlbIngestionValidator;

final class GlbIngestionService
{
    private GlbIngestionValidator $validator;

    public function __construct(?GlbIngestionValidator $validator = null)
    {
        $this->validator = $validator ?? new GlbIngestionValidator();
    }

    /**
     * @param array<string, mixed> $file
     * @param array<string, mixed> $options
     * @return array{
     *     binding: array<string, mixed>,
     *     validation: array{
     *         missing: string[],
     *         type: array<int, array{field: string, expected: string}>,
     *         ok: bool
     *     }
     * }
     */
    public function ingest(array $file, array $options = []): array
    {
        $metadata = $this->receiveFile($file);
        $metadata = array_merge($metadata, $this->extractBasicMetadata($file));

        if (isset($options['piece_type'])) {
            $metadata['piece_type'] = $options['piece_type'];
        }

        // TODO: docs/Plugin 1 — G3d Models Manager (ingesta Glb Y Binding Técnico)
        //       — Informe.md §4.2 — extraer slots_detectados.
        // TODO: docs/Plugin 1 — G3d Models Manager (ingesta Glb Y Binding Técnico)
        //       — Informe.md §4.2 — extraer anchors_present.
        // TODO: docs/Capa T — 3d Assets & Export — Actualizada V2 (revisada Con Controles Por Slot).md
        //       — mapear props completos.

        $validation = $this->validator->validate($metadata);

        return [
            'binding' => $metadata,
            'validation' => $validation,
        ];
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    private function receiveFile(array $file): array
    {
        $metadata = [];

        $fileName = $file['name'] ?? null;
        if (is_string($fileName) && $fileName !== '') {
            $metadata['file_name'] = $fileName;
        }

        $temporaryPath = $file['tmp_name'] ?? null;
        if (is_string($temporaryPath) && $temporaryPath !== '' && is_readable($temporaryPath)) {
            $metadata['file_contents'] = $temporaryPath;
        }

        return $metadata;
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    private function extractBasicMetadata(array $file): array
    {
        $metadata = [];

        if (isset($file['size'])) {
            $metadata['filesize_bytes'] = (int) $file['size'];
        }

        $hash = $this->computeChecksum($file);
        if ($hash !== null) {
            $metadata['file_hash'] = $hash;
        }

        // TODO: docs/Plugin 1 — G3d Models Manager (ingesta Glb Y Binding Técnico) — Informe.md §4.1.
        $metadata['draco_enabled'] = null;
        // TODO: docs/Plugin 1 — G3d Models Manager (ingesta Glb Y Binding Técnico) — Informe.md §4.1.
        $metadata['bounding_box'] = null;

        return $metadata;
    }

    /**
     * @param array<string, mixed> $file
     */
    private function computeChecksum(array $file): ?string
    {
        $tmpName = $file['tmp_name'] ?? null;
        if (!is_string($tmpName) || $tmpName === '' || !is_readable($tmpName)) {
            return null;
        }

        // TODO: docs/Plugin 1 — G3d Models Manager (ingesta Glb Y Binding Técnico)
        //       — Informe.md §4.1 — confirmar algoritmo de checksum.
        return hash_file('sha256', $tmpName);
    }
}
