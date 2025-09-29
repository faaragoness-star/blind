<?php

declare(strict_types=1);

namespace G3D\VendorBase\Cdn;

final class RouteBuilder
{
    /**
     * Construye una URL CDN base + path normalizado.
     *
     * @param string $cdnBase e.g. https://cdn.example
     * @param string $path    e.g. /assets/img/foo.png o assets/img/foo.png
     */
    public static function build(string $cdnBase, string $path): string
    {
        $base = rtrim($cdnBase, '/');
        $normalizedPath = '/' . ltrim($path, '/');

        return $base . $normalizedPath;
    }
}
