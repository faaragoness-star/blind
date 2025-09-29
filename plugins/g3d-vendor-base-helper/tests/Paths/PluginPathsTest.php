<?php

declare(strict_types=1);

namespace G3D\VendorBase\Tests\Paths;

use G3D\VendorBase\Paths\PluginPaths;
use PHPUnit\Framework\TestCase;

final class PluginPathsTest extends TestCase
{
    public function testResolveJoinsBaseAndRelativePath(): void
    {
        $pluginFile = '/var/www/html/wp-content/plugins/g3d-vendor-base-helper/plugin.php';
        $resolved = PluginPaths::resolve($pluginFile, 'assets/js/app.js');

        self::assertSame(
            '/var/www/html/wp-content/plugins/g3d-vendor-base-helper/assets/js/app.js',
            $resolved
        );
    }

    public function testResolveNormalizesDirectoryTraversal(): void
    {
        $pluginFile = '/var/www/html/wp-content/plugins/g3d-vendor-base-helper/plugin.php';
        $resolved = PluginPaths::resolve($pluginFile, '../assets/../css/../js/app.js');

        self::assertSame(
            '/var/www/html/wp-content/plugins/g3d-vendor-base-helper/assets/css/js/app.js',
            $resolved
        );
    }

    public function testResolveReplacesBackslashes(): void
    {
        $pluginFile = 'C:\\wordpress\\wp-content\\plugins\\g3d-vendor-base-helper\\plugin.php';
        $resolved = PluginPaths::resolve($pluginFile, 'assets\\img\\logo.png');

        self::assertSame(
            'C:/wordpress/wp-content/plugins/g3d-vendor-base-helper/assets/img/logo.png',
            $resolved
        );
    }
}
