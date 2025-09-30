<?php

declare(strict_types=1);

namespace G3D\VendorBase\Tests\Rest;

use G3D\VendorBase\Rest\Security;
use PHPUnit\Framework\TestCase;
use Test_Env\Nonce;
use WP_Error;
use WP_REST_Request;

final class SecurityTest extends TestCase
{
    protected function tearDown(): void
    {
        Nonce::allow();
        parent::tearDown();
    }

    public function testCheckOptionalNonceReturnsTrueWhenHeaderMissing(): void
    {
        $request = new WP_REST_Request('GET', '/g3d/v1/ping');

        $result = Security::checkOptionalNonce($request);

        self::assertTrue($result);
    }

    public function testCheckOptionalNonceReturnsTrueWhenNonceValid(): void
    {
        $request = new WP_REST_Request('POST', '/g3d/v1/ping');
        $request->set_header('X-WP-Nonce', 'valid-nonce');
        Nonce::allow();

        $result = Security::checkOptionalNonce($request);

        self::assertTrue($result);
    }

    public function testCheckOptionalNonceReturnsWpErrorWhenNonceInvalid(): void
    {
        $request = new WP_REST_Request('POST', '/g3d/v1/ping');
        $request->set_header('X-WP-Nonce', 'invalid-nonce');
        Nonce::deny();

        $result = Security::checkOptionalNonce($request);

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame('rest_invalid_nonce', $result->get_error_code());
        self::assertSame(401, $result->get_error_data()['status'] ?? null);
    }
}
