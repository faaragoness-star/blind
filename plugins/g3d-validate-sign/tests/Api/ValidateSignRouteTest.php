<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Tests\Api;

use DateTimeImmutable;
use DateTimeInterface;
use G3D\ValidateSign\Api\ValidateSignController;
use G3D\ValidateSign\Crypto\Signer;
use G3D\ValidateSign\Domain\Expiry;
use G3D\ValidateSign\Validation\RequestValidator;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ValidateSignRouteTest extends TestCase
{
    public function testHandleReturnsSignaturePayloadAlignedWithDocs(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/validate-sign.request.schema.json';
        $validator = new RequestValidator($schemaPath);
        $fixedNow = new DateTimeImmutable('2025-09-29T00:00:00+00:00');
        $expiry = $this->createExpiry($fixedNow, 30, false);
        $signer = new Signer('sig.v1');
        $keyPair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);

        $controller = new ValidateSignController($validator, $signer, $expiry, $privateKey);

        $payload = [
            'schema_version' => '1.0.0',
            'snapshot_id' => 'snap:2025-09-01',
            'producto_id' => 'prod:rx-classic',
            'locale' => 'es-ES',
            'flags' => ['ab_variant' => 'checkout-a'],
            'state' => [
                'pieza:moldura' => [
                    'mat' => 'mat:acetato',
                    'modelos' => [],
                    'acabado' => 'fin:clearcoat-high',
                ],
            ],
            'price' => 199.0,
            'stock' => 5,
            'photo_url' => 'https://cdn.example/snap.png',
        ];

        $request = new WP_REST_Request($payload);
        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertTrue($data['ok']);
        self::assertSame('snap:2025-09-01', $data['snapshot_id']);
        self::assertIsString($data['sku_hash']);
        self::assertStringStartsWith('sig.v1.', $data['sku_signature']);
        self::assertSame('2025-10-29T00:00:00+00:00', $data['expires_at']);
        self::assertSame('{{pieza}} · {{material}} — {{color}} · {{textura}} · {{acabado}}', $data['summary']);
        self::assertSame(199.0, $data['price']);
        self::assertSame(5, $data['stock']);
        self::assertSame('https://cdn.example/snap.png', $data['photo_url']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $data['request_id']);
    }

    public function testHandleReturnsWpErrorWhenSchemaFieldsMissing(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/validate-sign.request.schema.json';
        $validator = new RequestValidator($schemaPath);
        $expiry = $this->createExpiry(new DateTimeImmutable('2025-09-29T00:00:00+00:00'), 30, false);
        $signer = new Signer('sig.v1');
        $keyPair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);

        $controller = new ValidateSignController($validator, $signer, $expiry, $privateKey);

        $payload = [
            'schema_version' => '1.0.0',
            'producto_id' => 'prod:rx-classic',
            'locale' => 'es-ES',
            'state' => [],
        ];

        $request = new WP_REST_Request($payload);
        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('rest_missing_required_params', $response->get_error_code());
        self::assertSame('Faltan campos requeridos.', $response->get_error_message());
    }

    private function createExpiry(DateTimeImmutable $now, int $ttlDays, bool $forceExpired): Expiry
    {
        return new class ($now, $ttlDays, $forceExpired) extends Expiry
        {
            private DateTimeImmutable $fixedNow;
            private int $fixedTtl;
            private bool $expired;

            public function __construct(DateTimeImmutable $fixedNow, int $fixedTtl, bool $expired)
            {
                parent::__construct($fixedTtl);
                $this->fixedNow = $fixedNow;
                $this->fixedTtl = $fixedTtl;
                $this->expired = $expired;
            }

            public function calculate(?int $ttlDays = null, ?DateTimeImmutable $now = null): DateTimeImmutable
            {
                $days = $ttlDays ?? $this->fixedTtl;

                return $this->fixedNow->modify(sprintf('+%d days', $days));
            }

            public function format(DateTimeImmutable $expiresAt): string
            {
                return $expiresAt->format(DateTimeInterface::ATOM);
            }

            public function isExpired(DateTimeImmutable $expiresAt, ?DateTimeImmutable $now = null): bool
            {
                return $this->expired;
            }
        };
    }
}
