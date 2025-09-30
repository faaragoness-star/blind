<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Tests\Api;

use G3D\ValidateSign\Api\VerifyController;
use G3D\ValidateSign\Crypto\Signer;
use G3D\ValidateSign\Crypto\Verifier;
use G3D\ValidateSign\Validation\RequestValidator;
use G3D\VendorBase\Time\FixedClock;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class VerifyRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped(
                'ext-sodium requerida para las pruebas (ver '
                . 'docs/plugin-3-g3d-validate-sign.md §4.1).'
            );
        }
    }

    public function testHandleReturnsOkResponseWhenSignatureValidAndFresh(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/verify.request.schema.json';
        $validator  = new RequestValidator($schemaPath);
        $clock      = new FixedClock(new \DateTimeImmutable('2025-09-29T00:00:00+00:00'));
        $verifier   = new Verifier(['sig.v1'], $clock);
        $signer     = new Signer('sig.v1', $clock);
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey  = sodium_crypto_sign_publickey($keyPair);

        $signingPayload = [
            'schema_version' => '1.0.0',
            'snapshot_id'    => 'snap:2025-09-01',
            'locale'         => 'es-ES',
            'state'          => [],
        ];

        $signed    = $signer->sign($signingPayload, $privateKey);

        $controller = new VerifyController($validator, $verifier, $publicKey);

        $requestPayload = [
            'sku_hash'      => $signed['sku_hash'],
            'sku_signature' => $signed['signature'],
            'snapshot_id'   => 'snap:2025-09-01',
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
        $validator  = new RequestValidator($schemaPath);
        $clock      = new FixedClock(new \DateTimeImmutable('2025-09-29T00:00:00+00:00'));
        $verifier   = new Verifier(['sig.v1'], $clock);
        $signer     = new Signer('sig.v1', $clock);
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey  = sodium_crypto_sign_publickey($keyPair);

        $signingPayload = [
            'snapshot_id' => 'snap:2025-09-01',
            'state'       => [],
        ];

        $signed    = $signer->sign($signingPayload, $privateKey);

        $clock->advance(new \DateInterval('P31D'));

        $controller = new VerifyController($validator, $verifier, $publicKey);
        $request = new WP_REST_Request('POST', '/g3d/v1/verify');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode([
            'sku_hash'      => $signed['sku_hash'],
            'sku_signature' => $signed['signature'],
            'snapshot_id'   => 'snap:2025-09-01',
        ]));

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(400, $response->get_status());
        $data = $response->get_data();
        self::assertFalse($data['ok']);
        self::assertSame('E_SIGN_EXPIRED', $data['code']);
        self::assertSame('sign_expired', $data['reason_key']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $data['request_id']);
    }

    public function testHandleReturnsSnapshotMismatchErrorFromVerifier(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/verify.request.schema.json';
        $validator  = new RequestValidator($schemaPath);
        $clock      = new FixedClock(new \DateTimeImmutable('2025-09-29T00:00:00+00:00'));
        $verifier   = new Verifier(['sig.v1'], $clock);
        $signer     = new Signer('sig.v1', $clock);
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey  = sodium_crypto_sign_publickey($keyPair);

        $signingPayload = [
            'snapshot_id' => 'snap:2025-09-01',
            'state'       => [],
        ];

        $signed    = $signer->sign($signingPayload, $privateKey);

        $controller = new VerifyController($validator, $verifier, $publicKey);
        $request = new WP_REST_Request('POST', '/g3d/v1/verify');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode([
            'sku_hash'      => $signed['sku_hash'],
            'sku_signature' => $signed['signature'],
            'snapshot_id'   => 'snap:2025-08-01',
        ]));

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(400, $response->get_status());
        $data = $response->get_data();
        self::assertFalse($data['ok']);
        self::assertSame('E_SIGN_SNAPSHOT_MISMATCH', $data['code']);
        self::assertSame('sign_snapshot_mismatch', $data['reason_key']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $data['request_id']);
    }

    public function testHandleReturnsErrorWhenSignaturePrefixUnsupported(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/verify.request.schema.json';
        $validator  = new RequestValidator($schemaPath);
        $clock      = new FixedClock(new \DateTimeImmutable('2025-09-29T00:00:00+00:00'));
        $verifier   = new Verifier(['sig.v1'], $clock);
        $signer     = new Signer('sig.v1', $clock);
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey  = sodium_crypto_sign_publickey($keyPair);

        $signingPayload = [
            'snapshot_id' => 'snap:2025-09-01',
            'state'       => [],
        ];

        $signed    = $signer->sign($signingPayload, $privateKey);
        $manipulatedSignature = (string) preg_replace('/^sig\\.v1/', 'sig.v2', $signed['signature']);

        $controller = new VerifyController($validator, $verifier, $publicKey);
        $request = new WP_REST_Request('POST', '/g3d/v1/verify');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode([
            'sku_hash'      => $signed['sku_hash'],
            'sku_signature' => $manipulatedSignature,
            'snapshot_id'   => 'snap:2025-09-01',
        ]));

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(400, $response->get_status());
        $data = $response->get_data();
        self::assertFalse($data['ok']);
        self::assertSame('E_SIGN_INVALID', $data['code']);
        self::assertSame('sign_invalid_prefix', $data['reason_key']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $data['request_id']);
    }

    public function testHandleReturnsErrorWhenSkuHashTampered(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/verify.request.schema.json';
        $validator  = new RequestValidator($schemaPath);
        $clock      = new FixedClock(new \DateTimeImmutable('2025-09-29T00:00:00+00:00'));
        $verifier   = new Verifier(['sig.v1'], $clock);
        $signer     = new Signer('sig.v1', $clock);
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey  = sodium_crypto_sign_publickey($keyPair);

        $signingPayload = [
            'snapshot_id' => 'snap:2025-09-01',
            'state'       => [],
        ];

        $signed    = $signer->sign($signingPayload, $privateKey);

        $controller = new VerifyController($validator, $verifier, $publicKey);
        $request = new WP_REST_Request('POST', '/g3d/v1/verify');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode([
            'sku_hash'      => 'sku:corrompido',
            'sku_signature' => $signed['signature'],
            'snapshot_id'   => 'snap:2025-09-01',
        ]));

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(400, $response->get_status());
        $data = $response->get_data();
        self::assertFalse($data['ok']);
        self::assertSame('E_SIGN_INVALID', $data['code']);
        self::assertSame('sign_hash_mismatch', $data['reason_key']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $data['request_id']);
    }

    public function testHandleReturnsWpErrorWhenSchemaFieldsMissing(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/verify.request.schema.json';
        $validator  = new RequestValidator($schemaPath);
        $verifier   = new Verifier(['sig.v1']);
        $controller = new VerifyController($validator, $verifier, 'public-key');

        $request = new WP_REST_Request('POST', '/g3d/v1/verify');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode([
            'sku_hash' => 'hash:missing-signature',
        ]));

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('rest_missing_required_params', $response->get_error_code());
        self::assertSame('Faltan campos requeridos.', $response->get_error_message());
        $data = $response->get_error_data();
        self::assertIsArray($data);
        self::assertSame(400, $data['status']);
        self::assertArrayHasKey('missing_fields', $data);
        self::assertArrayHasKey('request_id', $data);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $data['request_id']);
    }

    public function testHandleReturnsWpErrorWhenTypeInvalid(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/verify.request.schema.json';
        $validator  = new RequestValidator($schemaPath);
        $verifier   = new Verifier(['sig.v1']);
        $controller = new VerifyController($validator, $verifier, 'public-key');

        $request = new WP_REST_Request('POST', '/g3d/v1/verify');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode([
            'sku_hash'      => 123,
            'sku_signature' => 'sig.v1.payload.signature',
            'snapshot_id'   => 'snap:2025-09-01',
        ]));

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('rest_invalid_param', $response->get_error_code());
        self::assertSame('Tipos inválidos detectados.', $response->get_error_message());
        $data = $response->get_error_data();
        self::assertIsArray($data);
        self::assertSame(400, $data['status']);
        self::assertArrayHasKey('request_id', $data);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $data['request_id']);
    }

}
