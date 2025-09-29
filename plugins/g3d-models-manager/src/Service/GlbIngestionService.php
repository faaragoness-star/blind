<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Service;

use RuntimeException;

/**
 * @phpstan-type TypeError array{field:string, expected:string}
 * @phpstan-type Validation array{
 *   missing: list<string>,
 *   type:    list<TypeError>,
 *   ok:      bool
 * }
 * @phpstan-type IngestionResult array{
 *   binding:    array<string,mixed>,
 *   validation: Validation
 * }
 */
final class GlbIngestionService
{
    /**
     * @param array<string,mixed> $file  $_FILES['...']-like: tmp_name, name, size, error
     * @return IngestionResult
     */
    public function ingest(array $file): array
    {
        $missing = [];
        $type    = [];

        if (!isset($file['tmp_name'])) {
            $missing[] = 'tmp_name';
        } elseif (!is_string($file['tmp_name'])) {
            $type[] = ['field' => 'tmp_name', 'expected' => 'string'];
        }

        if (!isset($file['name'])) {
            $missing[] = 'name';
        } elseif (!is_string($file['name'])) {
            $type[] = ['field' => 'name', 'expected' => 'string'];
        }

        if (!isset($file['size'])) {
            $missing[] = 'size';
        } elseif (!is_int($file['size'])) {
            $type[] = ['field' => 'size', 'expected' => 'int'];
        }

        if ($missing !== [] || $type !== []) {
            return [
                'binding' => [],
                'validation' => [
                    'missing' => $missing,
                    'type'    => $type,
                    'ok'      => false,
                ],
            ];
        }

        /** @var string $tmp */
        $tmp = $file['tmp_name'];
        if (!is_readable($tmp)) {
            return [
                'binding' => [],
                'validation' => [
                    'missing' => [],
                    'type'    => [['field' => 'tmp_name', 'expected' => 'readable path']],
                    'ok'      => false,
                ],
            ];
        }

        // Tamaño: si `size` viene fiable, úsalo; si no, intenta `filesize()`.
        /** @var int $declaredSize */
        $declaredSize = $file['size'];
        $sizeBytes = $declaredSize > 0 ? $declaredSize : (is_int(@filesize($tmp)) ? (int) @filesize($tmp) : 0);

        // Hash SHA-256 del contenido (determinista).
        $hash = @hash_file('sha256', $tmp);
        if ($hash === false) {
            throw new RuntimeException('No se pudo calcular SHA-256 del archivo.');
        }

        // Construye binding **solo** con datos ciertos.
        $binding = [
            'file_hash'      => $hash,
            'filesize_bytes' => $sizeBytes,
            // No inventar: draco_enabled, bounding_box, slots_detectados, anchors_present, props...
            // TODO(doc P1 §binding): rellenar cuando haya parsing GLB definitivo.
        ];

        return [
            'binding' => $binding,
            'validation' => [
                'missing' => [],
                'type'    => [],
                'ok'      => true,
            ],
        ];
    }
}
