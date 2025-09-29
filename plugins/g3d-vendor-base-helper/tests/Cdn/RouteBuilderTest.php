<?php

declare(strict_types=1);

namespace G3D\VendorBase\Tests\Cdn;

use G3D\VendorBase\Cdn\RouteBuilder;
use PHPUnit\Framework\TestCase;

final class RouteBuilderTest extends TestCase
{
    public function testBuildNormalizesBaseAndPath(): void
    {
        $url = RouteBuilder::build('https://cdn.example', 'assets/img/logo.png');

        self::assertSame('https://cdn.example/assets/img/logo.png', $url);
    }

    public function testBuildTrimsBaseTrailingSlash(): void
    {
        $url = RouteBuilder::build('https://cdn.example/', 'assets/img/logo.png');

        self::assertSame('https://cdn.example/assets/img/logo.png', $url);
    }

    public function testBuildEnsuresLeadingSlashOnPath(): void
    {
        $url = RouteBuilder::build('https://cdn.example', '/assets/img/logo.png');

        self::assertSame('https://cdn.example/assets/img/logo.png', $url);
    }

    public function testBuildHandlesBothBaseTrailingAndPathLeadingSlash(): void
    {
        $url = RouteBuilder::build('https://cdn.example/', '/assets/img/logo.png');

        self::assertSame('https://cdn.example/assets/img/logo.png', $url);
    }
}
