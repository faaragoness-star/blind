<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Domain;

use JsonException;
use RuntimeException;

final class Canonicalizer
{
    /**
     * Canonicaliza datos según docs (orden, normalización).
     * Devuelve string listo para hashing.
     *
     * @param array<string, mixed> $payload
     */
    public static function canonicalize(array $payload): string
    {
        $normalized = self::normalize($payload);

        try {
            return json_encode(
                $normalized,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'No se pudo canonicalizar payload (ver docs/Capa 3 — Validación, Firma '
                . 'Y Caducidad — Actualizada (slots Abiertos) — V2 (urls).md §SKU, firma y caducidad).',
                0,
                $exception
            );
        }
    }

    private static function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $isAssoc = self::isAssoc($value);

            if ($isAssoc) {
                ksort($value);
            }

            $normalized = [];

            foreach ($value as $key => $item) {
                if ($item === null) {
                    continue;
                }

                $normalized[$isAssoc ? $key : count($normalized)] = self::normalize($item);
            }

            if ($isAssoc) {
                return $normalized;
            }

            return array_values($normalized);
        }

        if (is_string($value)) {
            // TODO(docs Capa 3 §canonicalización): normalizar strings/locales si aplica.
            return $value;
        }

        return $value;
    }

    /**
     * @param array<mixed> $value
     */
    private static function isAssoc(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }
}
