<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Tests\Service;

use G3D\ModelsManager\Service\GlbIngestionService;
use PHPUnit\Framework\TestCase;

final class GlbIngestionServiceTest extends TestCase
{
    public function testIngestOkReturnsHashAndSize(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'glb');
        self::assertIsString($tmp);
        file_put_contents($tmp, str_repeat('A', 123));

        $svc = new GlbIngestionService();
        $res = $svc->ingest([
            'tmp_name' => $tmp,
            'name'     => 'modelo.glb',
            'size'     => 123,
            'error'    => 0,
        ]);

        self::assertTrue($res['validation']['ok']);
        self::assertArrayHasKey('file_hash', $res['binding']);
        self::assertArrayHasKey('filesize_bytes', $res['binding']);
        self::assertSame(123, $res['binding']['filesize_bytes']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $res['binding']['file_hash']);
    }

    public function testIngestTypeErrorWhenSizeIsNotInt(): void
    {
        $svc = new GlbIngestionService();
        $res = $svc->ingest([
            'tmp_name' => '/tmp/does-not-matter',
            'name'     => 'x.glb',
            'size'     => '123',
            'error'    => 0,
        ]);

        self::assertFalse($res['validation']['ok']);
        self::assertNotEmpty($res['validation']['type']);
    }

    public function testIngestMissingWhenTmpNameAbsent(): void
    {
        $svc = new GlbIngestionService();
        $res = $svc->ingest([
            'name'  => 'x.glb',
            'size'  => 10,
            'error' => 0,
        ]);

        self::assertFalse($res['validation']['ok']);
        self::assertContains('tmp_name', $res['validation']['missing']);
    }
}
