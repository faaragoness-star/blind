<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Tests;

use DateTimeImmutable;
use G3D\ValidateSign\Crypto\Signer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SignerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped(
                'ext-sodium requerida para las pruebas (ver docs/plugin-3-g3d-validate-sign.md §4.1).'
            );
        }
    }

    public function testSignProducesDeterministicSignatureWithDocsContract(): void
    {
        $signer     = new Signer('sig.v1');
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey  = sodium_crypto_sign_publickey($keyPair);

        $payload = [
            'schema_version' => '1.0.0',
            'snapshot_id'    => 'snap:2025-09-01',
            'producto_id'    => 'prod:rx-classic',
            'locale'         => 'es-ES',
            'flags'          => [
                'ab_variant' => 'checkout-a',
            ],
            'state' => [
                'pieza:moldura' => [
                    'mat'     => 'mat:acetato',
                    'modelos' => [
                        [
                            'modelo_id' => 'modelo:fr-m1',
                            'colores'   => [
                                'col:negro',
                                null,
                                'col:azul',
                            ],
                            'texturas'  => [
                                'tex:acetato-base',
                            ],
                        ],
                    ],
                    'acabado' => 'fin:clearcoat-high',
                    'morphs'  => null,
                ],
                'pieza:patilla' => [
                    'mat'     => 'mat:acetato',
                    'modelos' => [
                        [
                            'modelo_id' => 'modelo:tp-p2-l',
                            'colores'   => [
                                'col:negro',
                            ],
                        ],
                    ],
                    'acabado' => 'fin:clearcoat-high',
                ],
            ],
        ];

        $expiresAt = new DateTimeImmutable('2025-10-29T00:00:00+00:00');
        $result    = $signer->sign($payload, $privateKey, $expiresAt);

        $signatureParts = explode('.', $result['signature']);
        self::assertCount(4, $signatureParts);
        self::assertSame('sig', $signatureParts[0]);
        self::assertSame('v1', $signatureParts[1]);

        $message        = $result['message'];
        $decodedMessage = $this->base64UrlDecode($signatureParts[2]);
        self::assertSame($message, $decodedMessage);

        $decodedSignature = $this->base64UrlDecode($signatureParts[3]);
        self::assertNotFalse($decodedSignature);
        self::assertTrue(
            sodium_crypto_sign_verify_detached($decodedSignature, $message, $publicKey)
        );

        $messageData = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($result['sku_hash'], $messageData['sku_hash']);
        self::assertSame('snap:2025-09-01', $messageData['snapshot_id']);
        self::assertSame('es-ES', $messageData['locale']);
        self::assertSame('checkout-a', $messageData['ab_variant']);
        self::assertSame('2025-10-29T00:00:00+00:00', $messageData['expires_at']);
        self::assertSame('2025-10-29T00:00:00+00:00', $result['expires_at']);

        $expectedSkuHash = hash('sha256', $this->canonicalize($payload['state']));
        self::assertSame($expectedSkuHash, $result['sku_hash']);
    }

    public function testSignRejectsInvalidPrivateKeyLength(): void
    {
        $signer   = new Signer();
        $payload  = ['state' => []];
        $expiresAt = new DateTimeImmutable('now');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Clave privada inválida');
        $signer->sign($payload, 'invalid-key', $expiresAt);
    }

    private function canonicalize(array $data): string
    {
        $normalized = $this->normalizeValue($data);

        return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $isAssoc = array_keys($value) !== range(0, count($value) - 1);
            $working = $value;

            if ($isAssoc) {
                ksort($working);
            }

            $normalized = [];

            foreach ($working as $key => $item) {
                if ($item === null) {
                    continue;
                }

                $normalized[$key] = $this->normalizeValue($item);
            }

            return $isAssoc ? $normalized : array_values($normalized);
        }

        return $value;
    }

    private function base64UrlDecode(string $value): string|false
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
