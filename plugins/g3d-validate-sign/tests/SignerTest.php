<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Tests;

use G3D\ValidateSign\Domain\Canonicalizer;
use G3D\ValidateSign\Crypto\Signer;
use G3D\VendorBase\Time\FixedClock;
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
        $clock      = new FixedClock(new \DateTimeImmutable('2025-09-29T00:00:00+00:00'));
        $signer     = new Signer('sig.v1', $clock);
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

        $result    = $signer->sign($payload, $privateKey);

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
        $expectedExpiry = $clock->now()->add(new \DateInterval('P30D'))->format(DATE_ATOM);
        self::assertSame($expectedExpiry, $messageData['expires_at']);
        self::assertSame($expectedExpiry, $result['expires_at']);

        $canonicalPayload = $this->buildCanonicalSkuPayload($payload);
        $expectedSkuHash = hash('sha256', Canonicalizer::canonicalize($canonicalPayload));
        self::assertSame($expectedSkuHash, $result['sku_hash']);
    }

    public function testSkuHashRemainsStableAcrossMapOrder(): void
    {
        $clock      = new FixedClock(new \DateTimeImmutable('2025-09-29T00:00:00+00:00'));
        $signer     = new Signer('sig.v1', $clock);
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);

        $baseState = [
            'pieza:moldura' => [
                'mat'     => 'mat:acetato',
                'acabado' => 'fin:clearcoat-high',
                'modelos' => [
                    [
                        'modelo_id' => 'modelo:fr-m1',
                        'colores'   => ['col:negro', 'col:azul'],
                        'texturas'  => ['tex:acetato-base'],
                    ],
                ],
            ],
            'pieza:patilla' => [
                'modelos' => [
                    [
                        'modelo_id' => 'modelo:tp-p2-l',
                        'colores'   => ['col:negro'],
                    ],
                ],
                'acabado' => 'fin:clearcoat-high',
                'mat'     => 'mat:acetato',
            ],
        ];

        $payloadA = [
            'schema_version' => '1.0.0',
            'snapshot_id'    => 'snap:2025-09-01',
            'producto_id'    => 'prod:rx-classic',
            'locale'         => 'es-ES',
            'flags'          => [
                'ab_variant' => 'checkout-a',
                'beta'       => true,
            ],
            'state' => $baseState,
        ];

        $payloadB = [
            'flags'          => [
                'beta'       => true,
                'ab_variant' => 'checkout-a',
            ],
            'locale'         => 'es-ES',
            'producto_id'    => 'prod:rx-classic',
            'schema_version' => '1.0.0',
            'snapshot_id'    => 'snap:2025-09-01',
            'state'          => [
                'pieza:patilla' => [
                    'mat'     => 'mat:acetato',
                    'acabado' => 'fin:clearcoat-high',
                    'modelos' => [
                        [
                            'colores'   => ['col:negro'],
                            'modelo_id' => 'modelo:tp-p2-l',
                        ],
                    ],
                ],
                'pieza:moldura' => [
                    'modelos' => [
                        [
                            'texturas'  => ['tex:acetato-base'],
                            'colores'   => ['col:negro', 'col:azul'],
                            'modelo_id' => 'modelo:fr-m1',
                        ],
                    ],
                    'acabado' => 'fin:clearcoat-high',
                    'mat'     => 'mat:acetato',
                ],
            ],
        ];

        $resultA   = $signer->sign($payloadA, $privateKey);
        $resultB   = $signer->sign($payloadB, $privateKey);

        self::assertSame($resultA['sku_hash'], $resultB['sku_hash']);
        self::assertSame($resultA['message'], $resultB['message']);
    }

    public function testSequentialArrayOrderImpactsSkuHash(): void
    {
        $clock      = new FixedClock(new \DateTimeImmutable('2025-09-29T00:00:00+00:00'));
        $signer     = new Signer('sig.v1', $clock);
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);

        $payload = [
            'snapshot_id' => 'snap:2025-09-01',
            'state'       => [
                'pieza:moldura' => [
                    'modelos' => [
                        [
                            'modelo_id' => 'modelo:fr-m1',
                            'colores'   => ['col:negro', 'col:azul'],
                        ],
                    ],
                ],
            ],
        ];

        $signedOriginal = $signer->sign($payload, $privateKey);

        $tampered = $payload;
        $tampered['state']['pieza:moldura']['modelos'][0]['colores'] = ['col:azul', 'col:negro'];

        $tamperedCanonical = $this->buildCanonicalSkuPayload($tampered);
        $tamperedHash      = hash('sha256', Canonicalizer::canonicalize($tamperedCanonical));

        self::assertNotSame($signedOriginal['sku_hash'], $tamperedHash);
    }

    public function testSignHandlesMissingOptionalFields(): void
    {
        $clock      = new FixedClock(new \DateTimeImmutable('2025-09-29T00:00:00+00:00'));
        $signer     = new Signer('sig.v1', $clock);
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);

        $payload = [
            'snapshot_id' => 'snap:2025-09-01',
            'state'       => [
                'pieza:moldura' => [
                    'modelos' => [],
                ],
            ],
        ];

        $signed    = $signer->sign($payload, $privateKey);

        $canonicalPayload = $this->buildCanonicalSkuPayload($payload);
        $expectedSkuHash  = hash('sha256', Canonicalizer::canonicalize($canonicalPayload));

        self::assertSame($expectedSkuHash, $signed['sku_hash']);
        self::assertNotEmpty($signed['signature']);
    }

    public function testSignRejectsInvalidPrivateKeyLength(): void
    {
        $signer   = new Signer();
        $payload  = ['state' => []];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Clave privada inválida');
        $signer->sign($payload, 'invalid-key');
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function buildCanonicalSkuPayload(array $payload): array
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

        return $canonical;
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
