<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Validation;

use RuntimeException;

final class GlbIngestionValidator
{
    private array $schema;

    public function __construct(?string $schemaPath = null)
    {
        $path = $schemaPath ?? dirname(__DIR__, 2) . '/schemas/glb-ingest.request.schema.json';
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el schema en ' . $path);
        }

        $decoded = json_decode($contents, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Schema JSON inválido en ' . $path . ': ' . json_last_error_msg());
        }

        $this->schema = is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *     missing: string[],
     *     type: array<int, array{field: string, expected: string}>,
     *     ok: bool
     * }
     */
    public function validate(array $payload): array
    {
        $result = [
            'missing' => [],
            'type' => [],
            'ok' => false,
        ];

        $required = $this->schema['required'] ?? [];
        foreach ($required as $field) {
            if (!array_key_exists((string) $field, $payload)) {
                $result['missing'][] = (string) $field;
            }
        }

        $properties = $this->schema['properties'] ?? [];
        foreach ($properties as $field => $definition) {
            if (!array_key_exists((string) $field, $payload)) {
                continue;
            }

            if (!is_array($definition) || !array_key_exists('type', $definition)) {
                continue;
            }

            $expected = $definition['type'];
            if (!$this->isTypeValid($payload[(string) $field], $expected)) {
                $result['type'][] = [
                    'field' => (string) $field,
                    'expected' => $this->stringifyExpectedType($expected),
                ];
            }
        }

        $result['ok'] = $result['missing'] === [] && $result['type'] === [];

        return $result;
    }

    private function stringifyExpectedType(mixed $expected): string
    {
        if (is_array($expected)) {
            return implode('|', array_map('strval', $expected));
        }

        return (string) $expected;
    }

    private function isTypeValid(mixed $value, mixed $expected): bool
    {
        $types = is_array($expected) ? $expected : [$expected];

        foreach ($types as $type) {
            switch ($type) {
                case 'string':
                    if (is_string($value)) {
                        return true;
                    }

                    break;
                case 'integer':
                    if (is_int($value)) {
                        return true;
                    }

                    break;
                case 'number':
                    if (is_int($value) || is_float($value)) {
                        return true;
                    }

                    break;
                case 'boolean':
                    if (is_bool($value)) {
                        return true;
                    }

                    break;
                case 'array':
                    if (is_array($value)) {
                        return true;
                    }

                    break;
                case 'object':
                    if (is_array($value)) {
                        return true;
                    }

                    break;
                case 'null':
                    if ($value === null) {
                        return true;
                    }

                    break;
            }
        }

        return false;
    }
}
