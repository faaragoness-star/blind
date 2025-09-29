<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Tests\Api;

use DateTimeImmutable;
use DateTimeInterface;
use G3D\ValidateSign\Api\VerifyController;
use G3D\ValidateSign\Crypto\Signer;
use G3D\ValidateSign\Crypto\Verifier;
use G3D\ValidateSign\Domain\Expiry;
use G3D\ValidateSign\Validation\RequestValidator;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Response;

final class VerifyRouteTest extends TestCase
{
    public function testHandleReturnsOkResponseWhenSignatureValidAndFresh(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/verify.request.schema.json';
        $validator = new RequestValidator($schemaPath);
        $verifier = new Verifier(['sig.v1']);
        $expiry = $this->createExpiry(new DateTimeImmutable('2025-09-29T00:00:00+00:00'), 30, false);
        $signer = new Signer('sig.v1');
        $keyPair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);

        $signingPayload = [
            'schema_version' => '1.0.0',
            'snapshot_id' => 'snap:2025-09-01',
            'locale' => 'es-ES',
            'state' => [],
        ];

        $expiresAt = new DateTimeImmutable('2025-10-29T00:00:00+00:00');
        $signed = $signer->sign($signingPayload, $privateKey, $expiresAt);

        $controller = new VerifyController($validator, $verifier, $expiry, $publicKey);

        $requestPayload = [
            'sku_hash' => $signed['sku_hash'],
            'sku_signature' => $signed['signature'],
            'snapshot_id' => 'snap:2025-09-01',
        ];

        $request = new WP_REST_Request('POST', '/g3d/v1/verify');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode($requestPayload));
        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());
        $data = $response->get_data();
        self::assertIsArray($data);
        self::assertTrue($data['ok']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $data['request_id']);
    }

    public function testHandleReturnsErrorWhenSignatureExpired(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/verify.request.schema.json';
        $validator = new RequestValidator($schemaPath);
        $verifier = new Verifier(['sig.v1']);
        $expiry = $this->createExpiry(new DateTimeImmutable('2025-09-29T00:00:00+00:00'), 30, true);
        $signer = new Signer('sig.v1');
        $keyPair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);

        $signingPayload = [
            'snapshot_id' => 'snap:2025-09-01',
            'state' => [],
        ];

        $expiresAt = new DateTimeImmutable('2025-09-30T00:00:00+00:00');
        $signed = $signer->sign($signingPayload, $privateKey, $expiresAt);

        $controller = new VerifyController($validator, $verifier, $expiry, $publicKey);
        $request = new WP_REST_Request('POST', '/g3d/v1/verify');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode([
            'sku_hash' => $signed['sku_hash'],
            'sku_signature' => $signed['signature'],
            'snapshot_id' => 'snap:2025-09-01',
        ]));

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(400, $response->get_status());
        $data = $response->get_data();
        self::assertSame('E_SIGN_EXPIRED', $data['code']);
        self::assertSame('sign_expired', $data['reason_key']);
    }

    public function testHandleReturnsSnapshotMismatchErrorFromVerifier(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/verify.request.schema.json';
        $validator = new RequestValidator($schemaPath);
        $verifier = new Verifier(['sig.v1']);
        $expiry = $this->createExpiry(new DateTimeImmutable('2025-09-29T00:00:00+00:00'), 30, false);
        $signer = new Signer('sig.v1');
        $keyPair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);

        $signingPayload = [
            'snapshot_id' => 'snap:2025-09-01',
            'state' => [],
        ];

        $expiresAt = new DateTimeImmutable('2025-10-29T00:00:00+00:00');
        $signed = $signer->sign($signingPayload, $privateKey, $expiresAt);

        $controller = new VerifyController($validator, $verifier, $expiry, $publicKey);
        $request = new WP_REST_Request('POST', '/g3d/v1/verify');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode([
            'sku_hash' => $signed['sku_hash'],
            'sku_signature' => $signed['signature'],
            'snapshot_id' => 'snap:2025-08-01',
        ]));

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(400, $response->get_status());
        $data = $response->get_data();
        self::assertSame('E_SIGN_SNAPSHOT_MISMATCH', $data['code']);
        self::assertSame('sign_snapshot_mismatch', $data['reason_key']);
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
