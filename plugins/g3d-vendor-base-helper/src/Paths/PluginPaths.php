<?php

declare(strict_types=1);

namespace G3D\VendorBase\Paths;

final class PluginPaths
{
    /**
     * Devuelve ruta absoluta segura a subruta del plugin.
     *
     * @param string $pluginFile plugin.php (__FILE__ del plugin)
     * @param string $relative   subruta relativa (sin ..)
     */
    public static function resolve(string $pluginFile, string $relative): string
    {
        $normalizedPluginFile = \str_replace('\\', '/', $pluginFile);
        $base = \dirname($normalizedPluginFile);
        $base = $base === '.' ? '' : $base;
        $base = \str_replace('\\', '/', $base);
        $normalized = \str_replace(['..', '\\'], ['', '/'], $relative);
        $normalized = (string) \preg_replace('#/+#', '/', $normalized);

        return $base . '/' . ltrim($normalized, '/');
    }
}
