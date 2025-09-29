<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Tests;

use DateTimeImmutable;
use G3D\ValidateSign\Crypto\Signer;
use G3D\ValidateSign\Crypto\Verifier;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class VerifierTest extends TestCase
{
    public function testVerifyAcceptsSignatureAlignedWithDocs(): void
    {
        $signer = new Signer('sig.v1');
        $verifier = new Verifier(['sig.v1']);
        $keyPair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);

        $payload = [
            'schema_version' => '1.0.0',
            'snapshot_id' => 'snap:2025-09-01',
            'locale' => 'es-ES',
            'flags' => ['ab_variant' => 'checkout-a'],
            'state' => [],
        ];

        $expiresAt = new DateTimeImmutable('2025-10-29T00:00:00+00:00');
        $signed = $signer->sign($payload, $privateKey, $expiresAt);

        $verificationPayload = [
            'sku_hash' => $signed['sku_hash'],
            'sku_signature' => $signed['signature'],
            'snapshot_id' => 'snap:2025-09-01',
        ];

        $result = $verifier->verify($verificationPayload, $signed['signature'], $publicKey);

        self::assertTrue($result['ok']);
        self::assertSame('snap:2025-09-01', $result['snapshot_id']);
        self::assertInstanceOf(DateTimeImmutable::class, $result['expires_at']);
        self::assertSame($expiresAt->format(DATE_ATOM), $result['expires_at']->format(DATE_ATOM));
    }

    public function testVerifyRejectsUnsupportedPrefix(): void
    {
        $signer = new Signer('sig.v1');
        $verifier = new Verifier(['sig.v1']);
        $keyPair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);

        $payload = [
            'snapshot_id' => 'snap:2025-09-01',
            'state' => [],
        ];

        $expiresAt = new DateTimeImmutable('2025-10-29T00:00:00+00:00');
        $signed = $signer->sign($payload, $privateKey, $expiresAt);
        $manipulatedSignature = preg_replace('/^sig\.v1/', 'sig.v2', $signed['signature']);

        $result = $verifier->verify([
            'sku_hash' => $signed['sku_hash'],
            'snapshot_id' => 'snap:2025-09-01',
        ], $manipulatedSignature ?? '', $publicKey);

        self::assertFalse($result['ok']);
        self::assertSame('E_SIGN_INVALID', $result['code']);
        self::assertSame('sign_invalid', $result['reason_key']);
    }

    public function testVerifyDetectsSnapshotMismatch(): void
    {
        $signer = new Signer('sig.v1');
        $verifier = new Verifier(['sig.v1']);
        $keyPair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);

        $payload = [
            'snapshot_id' => 'snap:2025-09-01',
            'state' => [],
        ];

        $expiresAt = new DateTimeImmutable('2025-10-29T00:00:00+00:00');
        $signed = $signer->sign($payload, $privateKey, $expiresAt);

        $result = $verifier->verify([
            'sku_hash' => $signed['sku_hash'],
            'snapshot_id' => 'snap:2025-08-01',
        ], $signed['signature'], $publicKey);

        self::assertFalse($result['ok']);
        self::assertSame('E_SIGN_SNAPSHOT_MISMATCH', $result['code']);
        self::assertSame('sign_snapshot_mismatch', $result['reason_key']);
    }

    public function testVerifyRequiresExpirationInMessage(): void
    {
        $verifier = new Verifier(['sig.v1']);
        $keyPair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);

        $messagePayload = [
            'sku_hash' => hash('sha256', 'state'),
            'snapshot_id' => 'snap:2025-09-01',
            'locale' => 'es-ES',
            'ab_variant' => 'checkout-a',
        ];

        $message = json_encode($messagePayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (!is_string($message)) {
            throw new RuntimeException('No se pudo generar payload de prueba.');
        }

        $signature = sodium_crypto_sign_detached($message, $privateKey);
        $signaturePacked = sprintf(
            'sig.v1.%s.%s',
            $this->base64UrlEncode($message),
            $this->base64UrlEncode($signature)
        );

        $result = $verifier->verify([
            'sku_hash' => $messagePayload['sku_hash'],
            'snapshot_id' => 'snap:2025-09-01',
        ], $signaturePacked, $publicKey);

        self::assertFalse($result['ok']);
        self::assertSame('E_SIGN_INVALID', $result['code']);
        self::assertSame('sign_invalid', $result['reason_key']);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
