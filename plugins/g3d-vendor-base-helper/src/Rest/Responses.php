<?php

declare(strict_types=1);

namespace G3D\VendorBase\Rest;

final class Responses
{
    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public static function ok(array $data = []): array
    {
        return ['ok' => true] + $data;
    }

    /**
     * @return array{ok:false,code:string,reason_key:string,detail:string}
     */
    public static function error(string $code, string $reasonKey, string $detail): array
    {
        return [
            'ok'         => false,
            'code'       => $code,
            'reason_key' => $reasonKey,
            'detail'     => $detail,
        ];
    }
}
