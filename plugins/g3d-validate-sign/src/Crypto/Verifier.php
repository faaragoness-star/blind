<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Crypto;

use DateTimeImmutable;
use DateTimeInterface;
use G3D\ValidateSign\Domain\Canonicalizer;
use RuntimeException;

class Verifier
{
    /**
     * @var string[]
     */
    private array $allowedPrefixes;

    public function __construct(array $allowedPrefixes = Signer::ALLOWED_SIGNATURE_PREFIXES)
    {
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            throw new RuntimeException(
                'ext-sodium requerida (ver docs/plugin-3-g3d-validate-sign.md §4.1 y '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md).'
            );
        }

        $this->allowedPrefixes = $allowedPrefixes;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{
     *     ok: bool,
     *     code?: string,
     *     reason_key?: string,
     *     detail?: string,
     *     http_status?: int,
     *     expires_at?: DateTimeImmutable,
     *     snapshot_id?: string
     * }
     */
    public function verify(array $payload, string $signature, string $publicKey): array
    {
        $parts = explode('.', $signature);

        if (count($parts) !== 4) {
            return $this->error(
                'E_SIGN_INVALID',
                'sign_invalid',
                'Formato de firma inválido (ver '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md).'
            );
        }

        $prefix           = $parts[0] . '.' . $parts[1];
        $messageEncoded   = $parts[2];
        $signatureEncoded = $parts[3];

        if (!in_array($prefix, $this->allowedPrefixes, true)) {
            return $this->error(
                'E_SIGN_INVALID',
                'sign_invalid_prefix',
                'Prefijo de firma no permitido según '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md).'
            );
        }

        $message      = $this->base64UrlDecode($messageEncoded);
        $rawSignature = $this->base64UrlDecode($signatureEncoded);

        if ($message === null || $rawSignature === null) {
            return $this->error(
                'E_SIGN_INVALID',
                'sign_invalid',
                'Firma corrupta (ver '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md).'
            );
        }

        $normalizedPublicKey = $this->normalizePublicKey($publicKey);

        if (!sodium_crypto_sign_verify_detached($rawSignature, $message, $normalizedPublicKey)) {
            return $this->error(
                'E_SIGN_INVALID',
                'sign_invalid',
                'Firma Ed25519 inválida (ver '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md).'
            );
        }

        $decoded = json_decode($message, true);

        if (!is_array($decoded)) {
            return $this->error(
                'E_SIGN_INVALID',
                'sign_invalid',
                'Payload de firma inválido (ver '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md).'
            );
        }

        if (!array_key_exists('sku_hash', $decoded) || !array_key_exists('snapshot_id', $decoded)) {
            return $this->error(
                'E_SIGN_INVALID',
                'sign_invalid',
                'Campos obligatorios ausentes en firma (ver '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md, sección SKU, firma y caducidad).'
            );
        }

        if (!array_key_exists('locale', $decoded) || !array_key_exists('ab_variant', $decoded)) {
            return $this->error(
                'E_SIGN_INVALID',
                'sign_invalid',
                'Campos obligatorios ausentes en firma (ver '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md, sección SKU, firma y caducidad).'
            );
        }

        if (
            !is_string($decoded['sku_hash'])
            || !is_string($decoded['snapshot_id'])
            || !is_string($decoded['locale'])
            || !is_string($decoded['ab_variant'])
        ) {
            return $this->error(
                'E_SIGN_INVALID',
                'sign_invalid',
                'Tipos inválidos en firma (ver '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md, sección SKU, firma y caducidad).'
            );
        }

        $expiresAt = null;

        if (isset($decoded['expires_at']) && is_string($decoded['expires_at'])) {
            $expiresAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $decoded['expires_at']);
        }

        if (!$expiresAt instanceof DateTimeImmutable) {
            return $this->error(
                'E_SIGN_INVALID',
                'sign_invalid',
                'Expiración ausente en firma (ver '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md).'
            );
        }

        /** @var string $expiresAtString */
        $expiresAtString = $decoded['expires_at'];

        $expectedMessage = Canonicalizer::canonicalize([
            'sku_hash'    => $decoded['sku_hash'],
            'snapshot_id' => $decoded['snapshot_id'],
            'expires_at'  => $expiresAtString,
            'locale'      => $decoded['locale'],
            'ab_variant'  => $decoded['ab_variant'],
        ]);

        if ($expectedMessage !== $message) {
            return $this->error(
                'E_SIGN_INVALID',
                'sign_invalid',
                'Payload de firma no canónico (ver '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md, sección SKU, firma y caducidad).'
            );
        }

        $signatureSkuHash     = $decoded['sku_hash'];
        $signatureSnapshotId  = $decoded['snapshot_id'];
        $requestedSkuHash     = isset($payload['sku_hash']) ? (string) $payload['sku_hash'] : '';
        $requestedSnapshotId  = isset($payload['snapshot_id']) ? (string) $payload['snapshot_id'] : '';

        if ($signatureSkuHash !== $requestedSkuHash) {
            return $this->error(
                'E_SIGN_INVALID',
                'sign_hash_mismatch',
                'sku_hash no coincide con firma (ver '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md).'
            );
        }

        if ($signatureSnapshotId !== $requestedSnapshotId) {
            return $this->error(
                'E_SIGN_SNAPSHOT_MISMATCH',
                'sign_snapshot_mismatch',
                'snapshot_id no coincide con firma (ver '
                . 'docs/Capa 3 — Validación, Firma Y Caducidad — Actualizada '
                . '(slots Abiertos) — V2 (urls).md).'
            );
        }

        return [
            'ok'          => true,
            'expires_at'  => $expiresAt,
            'snapshot_id' => $signatureSnapshotId,
        ];
    }

    /**
     * @return array{ok: false, code: string, reason_key: string, detail: string, http_status: int}
     */
    private function error(string $code, string $reasonKey, string $detail, int $httpStatus = 400): array
    {
        return [
            'ok'          => false,
            'code'        => $code,
            'reason_key'  => $reasonKey,
            'detail'      => $detail,
            'http_status' => $httpStatus,
        ];
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }

    private function normalizePublicKey(string $publicKey): string
    {
        $decoded = base64_decode($publicKey, true);

        if ($decoded !== false && strlen($decoded) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return $decoded;
        }

        if (strlen($publicKey) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return $publicKey;
        }

        throw new RuntimeException(
            'Clave pública inválida; debe seguir Ed25519 (32 bytes) según docs/plugin-3-g3d-validate-sign.md §4.1.'
        );
    }
}
