<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Crypto;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use G3D\ValidateSign\Domain\Canonicalizer;
use G3D\VendorBase\Time\Clock;
use G3D\VendorBase\Time\SystemClock;
use RuntimeException;

/**
 * @phpstan-type CanonicalSkuPayload array{
 *     schema_version?: string,
 *     snapshot_id?: string,
 *     producto_id?: string,
 *     locale?: string,
 *     state?: array<string, mixed>,
 *     flags?: array<string, mixed>
 * }
 */
class Signer
{
    /**
     * Prefijos permitidos según
     * docs/Capa 3 — Validación, Firma y Caducidad — Actualizada
     * (slots Abiertos) — V2 (urls).md, sección "Firma con prefijo sig.vN".
     *
     * @var string[]
     */
    /** @todo Plugin 3 §Firmas/prefijos: soportar convivencia N/N-1. */
    public const ALLOWED_SIGNATURE_PREFIXES = ['sig.v1'];

    /**
     * TTL exacto de 30 días según docs/Capa 3 — Validación, Firma Y Caducidad —
     * Actualizada (slots Abiertos) — V2 (urls).md, sección "Caducidad: 30 días".
     */
    private const TTL_INTERVAL_SPEC = 'P30D';

    private string $signaturePrefix;
    private Clock $clock;

    public function __construct(?string $signaturePrefix = null, ?Clock $clock = null)
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
        $this->clock           = $clock ?? new SystemClock();
    }

    /**
     * @param array<string, mixed> $payload
     * @param string $privateKey Raw o Base64 Ed25519 (64 bytes)
     *                           según bóveda (ver docs/plugin-3-g3d-validate-sign.md §4.1).
     *
     * @return array{sku_hash: string, signature: string, message: string, expires_at: string}
     */
    public function sign(array $payload, string $privateKey): array
    {
        $normalizedPrivateKey = $this->normalizePrivateKey($privateKey);

        $skuPayload = $this->extractCanonicalSkuPayload($payload);
        $skuHash    = $this->computeSkuHash($skuPayload);
        $snapshotId = isset($payload['snapshot_id']) ? (string) $payload['snapshot_id'] : '';
        $locale     = isset($payload['locale']) ? (string) $payload['locale'] : '';
        $abVariant  = '';

        if (isset($payload['flags']) && is_array($payload['flags']) && isset($payload['flags']['ab_variant'])) {
            $abVariant = (string) $payload['flags']['ab_variant'];
        }

        $now          = $this->clock->now();
        $expiresAt    = $now->add(new DateInterval(self::TTL_INTERVAL_SPEC));
        $expiresAtUtc = $expiresAt->setTimezone(new DateTimeZone('UTC'));

        $messagePayload = [
            'sku_hash'    => $skuHash,
            'snapshot_id' => $snapshotId,
            'expires_at'  => $expiresAtUtc->format(DateTimeInterface::ATOM),
            'locale'      => $locale,
            'ab_variant'  => $abVariant,
        ];

        $message   = Canonicalizer::canonicalize($messagePayload);
        $signature = sodium_crypto_sign_detached($message, $normalizedPrivateKey);

        return [
            'sku_hash'   => $skuHash,
            'signature'  => $this->encodeSignature($message, $signature),
            'message'    => $message,
            'expires_at' => $messagePayload['expires_at'],
        ];
    }

    /**
     * @param CanonicalSkuPayload $payload
     */
    private function computeSkuHash(array $payload): string
    {
        $canonical = Canonicalizer::canonicalize($payload);

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

    /**
     * @param array<string, mixed> $payload
     *
     * @return CanonicalSkuPayload
     */
    private function extractCanonicalSkuPayload(array $payload): array
    {
        $canonical = [];

        if (isset($payload['schema_version']) && is_string($payload['schema_version'])) {
            $canonical['schema_version'] = $payload['schema_version'];
        }

        if (isset($payload['snapshot_id']) && is_string($payload['snapshot_id'])) {
            $canonical['snapshot_id'] = $payload['snapshot_id'];
        }

        if (isset($payload['producto_id']) && is_string($payload['producto_id'])) {
            $canonical['producto_id'] = $payload['producto_id'];
        }

        if (isset($payload['locale']) && is_string($payload['locale'])) {
            $canonical['locale'] = $payload['locale'];
        }

        if (isset($payload['state']) && is_array($payload['state'])) {
            $canonical['state'] = $payload['state'];
        }

        if (isset($payload['flags']) && is_array($payload['flags'])) {
            $canonical['flags'] = $payload['flags'];
        }

        // TODO(docs Capa 3 §canonicalización): confirmar si price/stock deben entrar en sku_hash.
        return $canonical;
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
