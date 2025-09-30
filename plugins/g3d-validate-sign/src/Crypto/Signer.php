<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Crypto;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use RuntimeException;

class Signer
{
    /**
     * Prefijos permitidos según docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada (slots Abiertos) — V2 (urls).md
     * sección "Firma con prefijo sig.vN".
     *
     * @var string[]
     */
    public const ALLOWED_SIGNATURE_PREFIXES = ['sig.v1'];

    private string $signaturePrefix;

    public function __construct(?string $signaturePrefix = null)
    {
        if (!function_exists('sodium_crypto_sign_detached')) {
            throw new RuntimeException(
                'ext-sodium requerida (ver docs/plugin-3-g3d-validate-sign.md §4.1 y docs/Capa 3 — Validación, Firma '
                . 'Y Caducidad — Actualizada (slots Abiertos) — V2 (urls).md).'
            );
        }

        $selectedPrefix = $signaturePrefix ?? self::ALLOWED_SIGNATURE_PREFIXES[0];

        if (!in_array($selectedPrefix, self::ALLOWED_SIGNATURE_PREFIXES, true)) {
            throw new RuntimeException(
                'Prefijo de firma no permitido; ver docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md.'
            );
        }

        $this->signaturePrefix = $selectedPrefix;
    }

    /**
     * @param array<string, mixed> $payload
     * @param string               $privateKey Raw o Base64 Ed25519 (64 bytes)
     *                                         según bóveda (ver docs/plugin-3-g3d-validate-sign.md §4.1).
     *
     * @return array{sku_hash: string, signature: string, message: string, expires_at: string}
     */
    public function sign(array $payload, string $privateKey, DateTimeImmutable $expiresAt): array
    {
        $normalizedPrivateKey = $this->normalizePrivateKey($privateKey);

        $state = [];

        if (isset($payload['state']) && is_array($payload['state'])) {
            $state = $payload['state'];
        }

        $skuHash = $this->computeSkuHash($state);
        $snapshotId = isset($payload['snapshot_id']) ? (string) $payload['snapshot_id'] : '';
        $locale = isset($payload['locale']) ? (string) $payload['locale'] : '';
        $abVariant = '';

        if (isset($payload['flags']) && is_array($payload['flags']) && isset($payload['flags']['ab_variant'])) {
            $abVariant = (string) $payload['flags']['ab_variant'];
        }

        $expiresAtUtc = $expiresAt->setTimezone(new DateTimeZone('UTC'));

        $messagePayload = [
            'sku_hash' => $skuHash,
            'snapshot_id' => $snapshotId,
            'expires_at' => $expiresAtUtc->format(DateTimeInterface::ATOM),
            'locale' => $locale,
            'ab_variant' => $abVariant,
        ];

        $message = $this->canonicalize($messagePayload);
        $signature = sodium_crypto_sign_detached($message, $normalizedPrivateKey);

        return [
            'sku_hash' => $skuHash,
            'signature' => $this->encodeSignature($message, $signature),
            'message' => $message,
            'expires_at' => $messagePayload['expires_at'],
        ];
    }

    /**
     * @param array<mixed> $state
     */
    private function computeSkuHash(array $state): string
    {
        $canonical = $this->canonicalize($state);

        return hash('sha256', $canonical);
    }

    private function encodeSignature(string $message, string $signature): string
    {
        return sprintf(
            '%s.%s.%s',
            $this->signaturePrefix,
            $this->base64UrlEncode($message),
            $this->base64UrlEncode($signature)
        );
    }

    private function canonicalize(array $data): string
    {
        $normalized = $this->normalizeValue($data);
        $encoded = json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            throw new RuntimeException(
                'No se pudo serializar JSON canónico (ver docs/plugin-3-g3d-validate-sign.md §6.1 y docs/Capa 1 '
                . 'Identificadores Y Naming — Actualizada (slots Abiertos).md).'
            );
        }

        return $encoded;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $isAssoc = $this->isAssoc($value);
            $normalized = [];

            if ($isAssoc) {
                ksort($value);
            }

            foreach ($value as $key => $item) {
                if ($item === null) {
                    continue;
                }

                $normalized[$key] = $this->normalizeValue($item);
            }

            return $isAssoc ? $normalized : array_values($normalized);
        }

        return $value;
    }

    /**
     * @param array<mixed> $value
     */
    private function isAssoc(array $value): bool
    {
        $expected = range(0, count($value) - 1);

        return array_keys($value) !== $expected;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function normalizePrivateKey(string $privateKey): string
    {
        $decoded = base64_decode($privateKey, true);

        if ($decoded !== false && strlen($decoded) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            return $decoded;
        }

        if (strlen($privateKey) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            return $privateKey;
        }

        throw new RuntimeException(
            'Clave privada inválida; debe seguir Ed25519 (64 bytes) según docs/plugin-3-g3d-validate-sign.md §4.1.'
        );
    }
}
