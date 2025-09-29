<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Tests\Service;

use G3D\ModelsManager\Service\GlbIngestionService;
use PHPUnit\Framework\TestCase;

final class GlbIngestionServiceTest extends TestCase
{
    public function testIngestReturnsDeterministicBinding(): void
    {
        $bytes = str_repeat('G', 1536);
        $tmp = tempnam(sys_get_temp_dir(), 'glb');
        if ($tmp === false) {
            self::fail('Unable to create temporary file for testing.');
        }

        file_put_contents($tmp, $bytes);

        $uploaded = [
            'name' => 'dummy.glb',
            'tmp_name' => $tmp,
            'size' => strlen($bytes),
            'type' => 'model/gltf-binary',
            'error' => 0,
        ];

        $service = new GlbIngestionService();

        try {
            $result = $service->ingest($uploaded);

            $this->assertIsArray($result['binding']);
            $this->assertSame(hash('sha256', $bytes), $result['binding']['file_hash']);
            $this->assertSame(strlen($bytes), $result['binding']['filesize_bytes']);
            $this->assertTrue($result['validation']['ok']);
            $this->assertSame([], $result['validation']['missing']);
            $this->assertSame([], $result['validation']['type']);
            $this->assertSame('FRAME', $result['binding']['piece_type']);
            $this->assertIsArray($result['binding']['anchors_present']);
            $this->assertIsArray($result['binding']['slots_detectados']);
            $this->assertNotEmpty($result['binding']['slots_detectados']);
            $this->assertIsArray($result['binding']['props']);
            $this->assertArrayHasKey('socket_width_mm', $result['binding']['props']);
            $this->assertArrayHasKey('socket_height_mm', $result['binding']['props']);
        } finally {
            unlink($tmp);
        }
    }
}
