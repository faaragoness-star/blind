<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Tests;

use G3D\ModelsManager\Validation\GlbIngestionValidator;
use PHPUnit\Framework\TestCase;

final class GlbIngestionValidatorTest extends TestCase
{
    public function testRejectsWhenMissingRequiredAssetMetadata(): void
    {
        $validator = new GlbIngestionValidator();

        $errors = $validator->validate([]);

        $this->assertContains('E_ASSET_MISSING', $this->extractCodes($errors));
    }

    public function testRejectsWhenScaleNotInMillimeters(): void
    {
        $validator = new GlbIngestionValidator();

        $metadata = $this->createValidFrameMetadata();
        $metadata['scale_unit'] = 'meters';

        $errors = $validator->validate($metadata);

        $this->assertContains('E_SCALE_INVALID', $this->extractCodes($errors));
    }

    public function testRejectsWhenFramePropsMissing(): void
    {
        $validator = new GlbIngestionValidator();

        $metadata = $this->createValidFrameMetadata();
        unset($metadata['props']['socket_height_mm']);

        $errors = $validator->validate($metadata);

        $this->assertContains('E_PROP_MISSING', $this->extractCodes($errors));
    }

    public function testRejectsWhenSocketCageAnchorMissing(): void
    {
        $validator = new GlbIngestionValidator();

        $metadata = $this->createValidFrameMetadata();
        $metadata['anchors_present'] = [
            'Frame_Anchor',
            'Temple_L_Anchor',
            'Temple_R_Anchor',
        ];

        $errors = $validator->validate($metadata);

        $this->assertContains('E_ANCHOR_MISSING', $this->extractCodes($errors));
    }

    public function testRejectsWhenSlotsListEmpty(): void
    {
        $validator = new GlbIngestionValidator();

        $metadata = $this->createValidFrameMetadata();
        $metadata['slots_detectados'] = ['   '];

        $errors = $validator->validate($metadata);

        $this->assertContains('E_SLOTS_EMPTY', $this->extractCodes($errors));
    }

    public function testAcceptsValidFrameGlbMetadata(): void
    {
        $validator = new GlbIngestionValidator();

        $errors = $validator->validate($this->createValidFrameMetadata());

        $this->assertSame([], $errors);
    }

    /**
     * @param array<int, \G3D\ModelsManager\Validation\GlbValidationError> $errors
     * @return array<int, string>
     */
    private function extractCodes(array $errors): array
    {
        return array_map(static function ($error): string {
            return $error->getCode();
        }, $errors);
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidFrameMetadata(): array
    {
        return [
            'file_hash' => 'abc123',
            'filesize_bytes' => 1024,
            'scale_unit' => 'mm',
            'up_axis' => 'Z',
            'pivot_at_origin' => true,
            'piece_type' => GlbIngestionValidator::PIECE_TYPE_FRAME,
            'props' => [
                'socket_width_mm' => 12.5,
                'socket_height_mm' => 6.2,
                'variant' => 'R',
                'mount_type' => 'FRAMED',
            ],
            'anchors_present' => [
                'Frame_Anchor',
                'Temple_L_Anchor',
                'Temple_R_Anchor',
                'Socket_Cage',
            ],
            'slots_detectados' => ['MAT_BASE'],
        ];
    }
}
