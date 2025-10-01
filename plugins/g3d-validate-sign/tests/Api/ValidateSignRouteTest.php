<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Tests\Api;

use G3D\ValidateSign\Api\ValidateSignController;
use G3D\ValidateSign\Crypto\Signer;
use G3D\ValidateSign\Validation\RequestValidator;
use G3D\VendorBase\Time\FixedClock;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class ValidateSignRouteTest extends TestCase
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

    public function testHandleReturnsSignaturePayloadAlignedWithDocs(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/validate-sign.request.schema.json';
        $validator  = new RequestValidator($schemaPath);
        $fixedNow   = new \DateTimeImmutable('2025-09-29T00:00:00+00:00');
        $clock      = new FixedClock($fixedNow);
        $signer     = new Signer('sig.v1', $clock);
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);

        $controller = new ValidateSignController($validator, $signer, $privateKey);

        $payload = [
            'schema_version' => '1.0.0',
            'snapshot_id'    => 'snap:2025-09-01',
            'producto_id'    => 'prod:rx-classic',
            'locale'         => 'es-ES',
            'flags'          => ['ab_variant' => 'checkout-a'],
            'state'          => [
                'pieza:moldura' => [
                    'mat'     => 'mat:acetato',
                    'modelos' => [],
                    'acabado' => 'fin:clearcoat-high',
                ],
            ],
        ];

        $request = new WP_REST_Request('POST', '/g3d/v1/validate-sign');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode($payload));
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
        self::assertArrayNotHasKey('price', $data);
        self::assertArrayNotHasKey('stock', $data);
        self::assertArrayNotHasKey('photo_url', $data);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $data['request_id']);
    }

    public function testHandleReturnsWpErrorWhenSchemaFieldsMissing(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/validate-sign.request.schema.json';
        $validator  = new RequestValidator($schemaPath);
        $clock      = new FixedClock(new \DateTimeImmutable('2025-09-29T00:00:00+00:00'));
        $signer     = new Signer('sig.v1', $clock);
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);

        $controller = new ValidateSignController($validator, $signer, $privateKey);

        $payload = [
            'schema_version' => '1.0.0',
            'producto_id'    => 'prod:rx-classic',
            'locale'         => 'es-ES',
            'state'          => [],
        ];

        $request = new WP_REST_Request('POST', '/g3d/v1/validate-sign');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode($payload));
        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('rest_missing_required_params', $response->get_error_code());
        self::assertSame('Faltan campos requeridos.', $response->get_error_message());
        $data = $response->get_error_data();
        self::assertIsArray($data);
        self::assertSame(400, $data['status']);
        self::assertArrayHasKey('request_id', $data);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $data['request_id']);
        self::assertContains('snapshot_id', $data['missing_fields']);
    }

    public function testHandleReturnsWpErrorWhenTypeInvalid(): void
    {
        $schemaPath = __DIR__ . '/../../schemas/validate-sign.request.schema.json';
        $validator  = new RequestValidator($schemaPath);
        $clock      = new FixedClock(new \DateTimeImmutable('2025-09-29T00:00:00+00:00'));
        $signer     = new Signer('sig.v1', $clock);
        $keyPair    = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);

        $controller = new ValidateSignController($validator, $signer, $privateKey);

        $payload = [
            'schema_version' => '1.0.0',
            'snapshot_id'    => 'snap:2025-09-01',
            'producto_id'    => 'prod:rx-classic',
            'locale'         => 'es-ES',
            'state'          => [],
            'flags'          => 'no-es-un-objeto',
        ];

        $request = new WP_REST_Request('POST', '/g3d/v1/validate-sign');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) json_encode($payload));
        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('rest_invalid_param', $response->get_error_code());
        self::assertSame('Tipos inválidos detectados.', $response->get_error_message());
        $data = $response->get_error_data();
        self::assertIsArray($data);
        self::assertSame(400, $data['status']);
        self::assertArrayHasKey('request_id', $data);
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $data['request_id']);
        self::assertArrayHasKey('type_errors', $data);
    }
}
