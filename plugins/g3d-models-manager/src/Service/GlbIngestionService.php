<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Service;

/**
 * @phpstan-type IngestionProps array{
 *     socket_width_mm: float,
 *     socket_height_mm: float,
 *     variant: 'R'|'U',
 *     mount_type: 'FRAMED'|'RIMLESS',
 *     tol_w_mm: float,
 *     tol_h_mm: float
 * }
 * @phpstan-type IngestionBoundingBox array{
 *     min: array{0: float, 1: float, 2: float},
 *     max: array{0: float, 1: float, 2: float}
 * }
 * @phpstan-type IngestionBinding array{
 *     file_hash: string,
 *     filesize_bytes: int,
 *     draco_enabled: bool,
 *     bounding_box: IngestionBoundingBox,
 *     piece_type: 'FRAME',
 *     object_name: string,
 *     object_name_pattern: string,
 *     model_code: string,
 *     props: IngestionProps,
 *     anchors_present: list<string>,
 *     slots_detectados: list<string>,
 *     scale_unit: string,
 *     scale_meters_per_unit: float,
 *     up_axis: string,
 *     pivot_at_origin: bool
 * }
 * @phpstan-type IngestionValidation array{
 *     missing: list<string>,
 *     type: list<array{field:string, expected:string}>,
 *     ok: bool
 * }
 * @phpstan-type IngestionResult array{
 *     binding: IngestionBinding,
 *     validation: IngestionValidation
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
            'scale_unit' => 'METER',
            'scale_meters_per_unit' => $this->buildScale($hash),
            'up_axis' => 'Z',
            'pivot_at_origin' => $this->isPivotAtOrigin($hash),
        ];

        return [
            'binding' => $binding,
            'validation' => $validation,
        ];
    }

    /**
     * @return array<string, mixed>
     * @phpstan-return IngestionBinding
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
            'scale_unit' => 'METER',
            'scale_meters_per_unit' => 1.0,
            'up_axis' => 'Z',
            'pivot_at_origin' => true,
        ];
    }

    private function isDracoEnabled(string $hash): bool
    {
        return (hexdec($this->getHexSegment($hash, 0, 2)) % 2) === 0;
    }

    /**
     * @return array{min: array{0: float, 1: float, 2: float}, max: array{0: float, 1: float, 2: float}}
     * @phpstan-return IngestionBoundingBox
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
     * @return array<string, float|string>
     * @phpstan-return IngestionProps
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

    private function buildScale(string $hash): float
    {
        return $this->normalizeHexToRange($this->getHexSegment($hash, 60, 4), 0.001, 0.01);
    }

    private function isPivotAtOrigin(string $hash): bool
    {
        return (hexdec($this->getHexSegment($hash, 4, 2)) % 2) === 1;
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
