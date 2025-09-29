<?php

declare(strict_types=1);

namespace G3D\VendorBase\Rest;

final class Responses
{
    /**
     * Normaliza payload de error para consistencia entre controladores.
     *
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    public static function error(string $code, string $reasonKey, string $detail, array $extra = []): array
    {
        return \array_merge([
            'ok'         => false,
            'code'       => $code,
            'reason_key' => $reasonKey,
            'detail'     => $detail,
        ], $extra);
    }
}
