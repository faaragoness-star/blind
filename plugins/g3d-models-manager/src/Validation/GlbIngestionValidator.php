<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Validation;

final class GlbIngestionValidator
{
    public const PIECE_TYPE_FRAME = 'FRAME';
    public const PIECE_TYPE_TEMPLE = 'TEMPLE';
    public const PIECE_TYPE_RIMLESS = 'RIMLESS';

    public const DEFAULT_MAX_FILE_SIZE_BYTES = 12582912; // 12 MB.

    private int $maxFileSizeBytes;

    public function __construct(int $maxFileSizeBytes = self::DEFAULT_MAX_FILE_SIZE_BYTES)
    {
        $this->maxFileSizeBytes = $maxFileSizeBytes;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return GlbValidationError[]
     */
    public function validate(array $metadata): array
    {
        $errors = [];

        $this->validateAssetPresence($metadata, $errors);
        $this->validateFileSize($metadata, $errors);
        $this->validateScale($metadata, $errors);
        $this->validateAxesAndPivot($metadata, $errors);
        $this->validateProps($metadata, $errors);
        $this->validateAnchors($metadata, $errors);
        $this->validateSlots($metadata, $errors);

        return $errors;
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<int, GlbValidationError> $errors
     */
    private function validateAssetPresence(array $metadata, array &$errors): void
    {
        $hasFileUrl = isset($metadata['file_url']) && is_string($metadata['file_url']) && $metadata['file_url'] !== '';
        $hasFileHash = (
            isset($metadata['file_hash'])
            && is_string($metadata['file_hash'])
            && $metadata['file_hash'] !== ''
        );
        $hasFileContents = isset($metadata['file_contents']) && $metadata['file_contents'] !== null;

        if ($hasFileUrl || $hasFileHash || $hasFileContents) {
            return;
        }

        $errors[] = new GlbValidationError('E_ASSET_MISSING', 'Asset missing.');
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<int, GlbValidationError> $errors
     */
    private function validateFileSize(array $metadata, array &$errors): void
    {
        if (!isset($metadata['filesize_bytes'])) {
            return;
        }

        $size = (int) $metadata['filesize_bytes'];
        if ($size <= $this->maxFileSizeBytes) {
            return;
        }

        $errors[] = new GlbValidationError('E_FILE_TOO_LARGE', 'File too large.');
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<int, GlbValidationError> $errors
     */
    private function validateScale(array $metadata, array &$errors): void
    {
        $scaleUnit = $metadata['scale_unit'] ?? ($metadata['scale']['unit'] ?? null);
        $metersPerUnit = $metadata['scale_meters_per_unit'] ?? ($metadata['scale']['meters_per_unit'] ?? null);

        $isMillimeterUnit = false;
        if (is_string($scaleUnit)) {
            $normalized = strtolower(trim($scaleUnit));
            $isMillimeterUnit = in_array($normalized, ['mm', 'millimeter', 'millimeters'], true);
        }

        if (!$isMillimeterUnit && $metersPerUnit !== null) {
            $isMillimeterUnit = abs((float) $metersPerUnit - 0.001) < 1.0e-6;
        }

        if ($isMillimeterUnit) {
            return;
        }

        $errors[] = new GlbValidationError('E_SCALE_INVALID', 'Scale invalid.');
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<int, GlbValidationError> $errors
     */
    private function validateAxesAndPivot(array $metadata, array &$errors): void
    {
        $upAxis = $metadata['up_axis'] ?? ($metadata['axes']['up'] ?? null);
        $pivotAligned = $metadata['pivot_at_origin'] ?? ($metadata['pivot']['at_origin'] ?? null);

        $isUpAxisValid = is_string($upAxis) && strtoupper(trim($upAxis)) === 'Z';
        if (!$isUpAxisValid) {
            $errors[] = new GlbValidationError('E_AXES_INVALID', 'Axes invalid.');
        }

        $isPivotAligned = is_bool($pivotAligned) ? $pivotAligned : ($pivotAligned === 1 || $pivotAligned === '1');
        if (!$isPivotAligned) {
            $errors[] = new GlbValidationError('E_AXES_INVALID', 'Pivot invalid.');
        }
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<int, GlbValidationError> $errors
     */
    private function validateProps(array $metadata, array &$errors): void
    {
        $pieceType = isset($metadata['piece_type']) && is_string($metadata['piece_type'])
            ? strtoupper(trim($metadata['piece_type']))
            : null;

        if ($pieceType === null) {
            return;
        }

        $props = [];
        if (isset($metadata['props']) && is_array($metadata['props'])) {
            $props = $metadata['props'];
        }

        $requiredProps = [];
        if ($pieceType === self::PIECE_TYPE_FRAME) {
            $requiredProps = ['socket_width_mm', 'socket_height_mm', 'variant', 'mount_type'];
        } elseif ($pieceType === self::PIECE_TYPE_TEMPLE) {
            $requiredProps = ['lug_width_mm', 'lug_height_mm', 'side'];
        } elseif ($pieceType === self::PIECE_TYPE_RIMLESS) {
            $requiredProps = ['mount_type'];
        }

        foreach ($requiredProps as $prop) {
            if (!$this->isFilled($props, $prop)) {
                $errors[] = new GlbValidationError('E_PROP_MISSING', sprintf('Prop missing: %s.', $prop));
            }
        }
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<int, GlbValidationError> $errors
     */
    private function validateAnchors(array $metadata, array &$errors): void
    {
        $anchors = [];
        if (isset($metadata['anchors_present']) && is_array($metadata['anchors_present'])) {
            $anchors = array_map('strval', $metadata['anchors_present']);
        }

        $requiredAnchors = ['Frame_Anchor', 'Temple_L_Anchor', 'Temple_R_Anchor'];

        $props = isset($metadata['props']) && is_array($metadata['props']) ? $metadata['props'] : [];
        $pieceType = isset($metadata['piece_type']) && is_string($metadata['piece_type'])
            ? strtoupper(trim($metadata['piece_type']))
            : null;

        if ($this->shouldRequireSocketCage($pieceType, $props)) {
            $requiredAnchors[] = 'Socket_Cage';
        }

        foreach ($requiredAnchors as $anchor) {
            if (!in_array($anchor, $anchors, true)) {
                $errors[] = new GlbValidationError('E_ANCHOR_MISSING', sprintf('Anchor missing: %s.', $anchor));
            }
        }
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<int, GlbValidationError> $errors
     */
    private function validateSlots(array $metadata, array &$errors): void
    {
        if (!isset($metadata['slots_detectados']) || !is_array($metadata['slots_detectados'])) {
            $errors[] = new GlbValidationError('E_SLOTS_EMPTY', 'Slots empty.');
            return;
        }

        $hasSlot = false;
        foreach ($metadata['slots_detectados'] as $slot) {
            if (is_string($slot) && trim($slot) !== '') {
                $hasSlot = true;
                break;
            }
        }

        if ($hasSlot) {
            return;
        }

        $errors[] = new GlbValidationError('E_SLOTS_EMPTY', 'Slots empty.');
    }

    /**
     * @param array<string, mixed> $props
     */
    private function isFilled(array $props, string $key): bool
    {
        if (!array_key_exists($key, $props)) {
            return false;
        }

        $value = $props[$key];
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return !empty($value);
        }

        return true;
    }

    /**
     * @param array<string, mixed> $props
     */
    private function shouldRequireSocketCage(?string $pieceType, array $props): bool
    {
        if ($pieceType === self::PIECE_TYPE_TEMPLE) {
            return false;
        }

        if (array_key_exists('socket_width_mm', $props) || array_key_exists('socket_height_mm', $props)) {
            return true;
        }

        if (array_key_exists('mount_type', $props)) {
            $mountType = strtolower((string) $props['mount_type']);
            return $mountType === 'socket' || $mountType === 'frame';
        }

        return $pieceType === self::PIECE_TYPE_FRAME;
    }
}
