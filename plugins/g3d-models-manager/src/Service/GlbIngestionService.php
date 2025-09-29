<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Service;

final class GlbIngestionService
{
    /**
     * @param array{tmp_name:string,name?:string,size?:int,type?:string,error?:int} $uploaded
     * @return array{
     *   binding: array<string,mixed>,
     *   validation: array{
     *     missing: string[],
     *     type: array<int, array{field:string, expected:string}>,
     *     ok: bool
     *   }
     * }
     */
    public function ingest(array $uploaded): array
    {
        $binding = [
            'file_hash' => '',
            'filesize_bytes' => 0,
            'draco_enabled' => false,
            'bounding_box' => null,
            'slots_detectados' => [],
            'anchors_present' => [],
            'props' => [],
            'object_name' => null,
            'object_name_pattern' => null,
            'model_code' => null,
        ];

        $validation = [
            'missing' => [],
            'type' => [],
            'ok' => true,
        ];

        // Validación mínima del "upload".
        if (!is_file($uploaded['tmp_name'])) {
            $validation['missing'][] = 'g3d_glb_file';
            $validation['ok'] = false;

            return [
                'binding' => $binding,
                'validation' => $validation,
            ];
        }

        // Metadatos básicos
        $binding['filesize_bytes'] = (int) (filesize($uploaded['tmp_name']) ?: 0);
        $hash = @hash_file('sha256', $uploaded['tmp_name']);
        $binding['file_hash'] = is_string($hash) ? $hash : '';

        return [
            'binding' => $binding,
            'validation' => $validation,
        ];
    }
}
