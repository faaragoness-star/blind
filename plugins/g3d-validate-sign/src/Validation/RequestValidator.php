<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Validation;

use RuntimeException;

class RequestValidator
{
    private array $schema;

    public function __construct(string $schemaPath)
    {
        $schemaContents = file_get_contents($schemaPath);

        if ($schemaContents === false) {
            throw new RuntimeException('No se pudo leer el schema en ' . $schemaPath);
        }

        $decoded = json_decode($schemaContents, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Schema JSON inválido en ' . $schemaPath . ': ' . json_last_error_msg());
        }

        $this->schema = $decoded ?? [];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{missing: string[], type: array<int, array{field: string, expected: string}>}
     */
    public function validate(array $data): array
    {
        $errors = [
            'missing' => [],
            'type' => [],
        ];

        $required = $this->schema['required'] ?? [];
        $properties = $this->schema['properties'] ?? [];

        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                $errors['missing'][] = (string) $field;
            }
        }

        foreach ($properties as $field => $definition) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            if (!is_array($definition) || !isset($definition['type'])) {
                continue;
            }

            $expectedType = $definition['type'];

            if (!$this->isTypeValid($data[$field], $expectedType)) {
                $errors['type'][] = [
                    'field' => (string) $field,
                    'expected' => is_array($expectedType) ? implode('|', $expectedType) : (string) $expectedType,
                ];
            }
        }

        return $errors;
    }

    /**
     * @param mixed                 $value
     * @param string|string[]|mixed $expected
     */
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
                case 'object':
                    if (is_array($value)) {
                        return true;
                    }

                    break;
                case 'array':
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
