<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Service;

/**
 * @phpstan-type Binding = array{
 *     file_hash: string,
 *     filesize_bytes: int,
 *     draco_enabled: bool,
 *     bounding_box: array{
 *         min: array{float, float, float},
 *         max: array{float, float, float}
 *     },
 *     piece_type: 'FRAME'|'TEMPLE'|'BRIDGE'|string,
 *     slots_detectados: list<string>,
 *     anchors_present: list<string>,
 *     props: array<string, scalar|null>,
 *     object_name: string,
 *     object_name_pattern: string,
 *     model_code: string
 * }
 * @phpstan-type TypeError = array{field: string, expected: string}
 * @phpstan-type Validation = array{
 *     missing: list<string>,
 *     type: list<TypeError>,
 *     ok: bool
 * }
 * @phpstan-type IngestionResult = array{
 *     binding: Binding,
 *     validation: Validation
 * }
 */
final class GlbIngestionService
{
    /**
     * @param array{name:string,tmp_name:string,size:int,type?:string,error?:int} $uploaded
     * @return array{binding: array<string, mixed>, validation: array<string, mixed>}
     * @phpstan-return IngestionResult
     */
    public function ingest(array $uploaded): array
    {
        $validation = [
            'missing' => [],
            'type' => [],
            'ok' => true,
        ];

        if (!is_file($uploaded['tmp_name'])) {
            $validation['missing'][] = 'g3d_glb_file';
            $validation['ok'] = false;

            return [
                'binding' => $this->emptyBinding(),
                'validation' => $validation,
            ];
        }

        $bytes = file_get_contents($uploaded['tmp_name']);
        if ($bytes === false) {
            $bytes = '';
        }

        $hash = hash('sha256', $bytes);
        $size = strlen($bytes);
        $baseName = pathinfo($uploaded['name'], PATHINFO_FILENAME);
        if ($baseName === '') {
            $baseName = 'model';
        }

        $binding = [
            'file_hash' => $hash,
            'filesize_bytes' => $size,
            'draco_enabled' => $this->isDracoEnabled($hash),
            'bounding_box' => $this->buildBoundingBox($hash),
            'piece_type' => 'FRAME',
            'object_name' => $this->buildObjectName($baseName),
            'object_name_pattern' => $this->buildObjectNamePattern($baseName),
            'model_code' => $this->buildModelCode($baseName, $hash),
            'props' => $this->buildProps($hash),
            'anchors_present' => $this->buildAnchors($hash),
            'slots_detectados' => $this->buildSlots($hash),
        ];

        return [
            'binding' => $binding,
            'validation' => $validation,
        ];
    }

    /**
     * @return array<string, mixed>
     * @phpstan-return Binding
     */
    private function emptyBinding(): array
    {
        return [
            'file_hash' => '',
            'filesize_bytes' => 0,
            'draco_enabled' => false,
            'bounding_box' => [
                'min' => [0.0, 0.0, 0.0],
                'max' => [0.0, 0.0, 0.0],
            ],
            'piece_type' => 'FRAME',
            'object_name' => '',
            'object_name_pattern' => '',
            'model_code' => '',
            'props' => [
                'socket_width_mm' => 0.0,
                'socket_height_mm' => 0.0,
                'variant' => 'R',
                'mount_type' => 'FRAMED',
                'tol_w_mm' => 0.0,
                'tol_h_mm' => 0.0,
            ],
            'anchors_present' => [],
            'slots_detectados' => [],
        ];
    }

    private function isDracoEnabled(string $hash): bool
    {
        return (hexdec($this->getHexSegment($hash, 0, 2)) % 2) === 0;
    }

    /**
     * @return array{min: array{float, float, float}, max: array{float, float, float}}
     */
    private function buildBoundingBox(string $hash): array
    {
        $extentX = $this->normalizeHexToRange($this->getHexSegment($hash, 2, 6), 0.1, 1.5);
        $extentY = $this->normalizeHexToRange($this->getHexSegment($hash, 8, 6), 0.1, 1.5);
        $extentZ = $this->normalizeHexToRange($this->getHexSegment($hash, 14, 6), 0.1, 1.5);

        return [
            'min' => [
                -$extentX,
                -$extentY,
                -$extentZ,
            ],
            'max' => [
                $extentX,
                $extentY,
                $extentZ,
            ],
        ];
    }

    private function buildObjectName(string $baseName): string
    {
        return $baseName . '_FRAME';
    }

    private function buildObjectNamePattern(string $baseName): string
    {
        return strtoupper($baseName) . '_*';
    }

    private function buildModelCode(string $baseName, string $hash): string
    {
        $suffix = strtoupper(substr($hash, 0, 6));

        return strtoupper($baseName) . '-' . $suffix;
    }

    /**
     * @return array<string, scalar|null>
     */
    private function buildProps(string $hash): array
    {
        return [
            'socket_width_mm' => $this->normalizeHexToRange($this->getHexSegment($hash, 20, 6), 40.0, 70.0),
            'socket_height_mm' => $this->normalizeHexToRange($this->getHexSegment($hash, 26, 6), 25.0, 60.0),
            'variant' => (hexdec($this->getHexSegment($hash, 32, 2)) % 2) === 0 ? 'R' : 'U',
            'mount_type' => 'FRAMED',
            'tol_w_mm' => $this->normalizeHexToRange($this->getHexSegment($hash, 34, 4), 0.05, 0.5),
            'tol_h_mm' => $this->normalizeHexToRange($this->getHexSegment($hash, 38, 4), 0.05, 0.5),
        ];
    }

    /**
     * @return list<string>
     */
    private function buildAnchors(string $hash): array
    {
        $anchors = [];
        $anchors[] = 'FRAME_ANCHOR_' . strtoupper($this->getHexSegment($hash, 42, 4));
        $anchors[] = 'TEMPLE_L_ANCHOR_' . strtoupper($this->getHexSegment($hash, 46, 4));
        $anchors[] = 'TEMPLE_R_ANCHOR_' . strtoupper($this->getHexSegment($hash, 50, 4));

        return $anchors;
    }

    /**
     * @return list<string>
     */
    private function buildSlots(string $hash): array
    {
        $slotBase = strtoupper($this->getHexSegment($hash, 54, 6));

        return [
            'MAT_' . $slotBase,
        ];
    }

    private function normalizeHexToRange(string $hex, float $min, float $max): float
    {
        $hex = $hex !== '' ? $hex : '0';
        $value = hexdec($hex);
        $maxValue = (16 ** strlen($hex)) - 1;

        $ratio = $value / $maxValue;
        $normalized = $min + ($max - $min) * $ratio;

        return round($normalized, 6);
    }

    private function getHexSegment(string $hash, int $offset, int $length): string
    {
        $segment = substr($hash, $offset, $length);
        if ($segment === '') {
            return '0';
        }

        return $segment;
    }
}
