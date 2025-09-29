<?php
// phpcs:ignoreFile

declare(strict_types=1);

require_once __DIR__ . '/../../g3d-vendor-base-helper/tests/bootstrap.php';

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'G3D\\CatalogRules\\',
        'G3dCatalogRules\\',
    ];

    foreach ($prefixes as $prefix) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $relativePath = str_replace('\\', '/', $relative);
        $file = __DIR__ . '/../src/' . $relativePath . '.php';

        if (is_file($file)) {
            require_once $file;
        }

        return;
    }
});
