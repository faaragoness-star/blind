<?php

declare(strict_types=1);

namespace G3D\VendorBase\Security;

use RuntimeException;

final class VendorGuard
{
    /**
     * Verifica precondiciones mínimas del runtime.
     * Lanza RuntimeException con mensaje claro si falla.
     *
     * TODO(doc §requisitos): ampliar checks (extensiones, versiones).
     */
    public static function assertReady(): void
    {
        if (!\function_exists('json_encode')) {
            throw new RuntimeException('json extension requerida');
        }

        if (\PHP_VERSION_ID < 80200) {
            throw new RuntimeException('PHP >= 8.2 requerido');
        }
    }
}
