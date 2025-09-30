<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Tests\Api;

use G3D\ValidateSign\Api\ValidateSignController;
use G3D\ValidateSign\Api\VerifyController;
use G3D\ValidateSign\Validation\RequestValidator;
use PHPUnit\Framework\TestCase;
use Test_Env\Nonce;
use Test_Env\Perms;
use WP_REST_Request;

final class PermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Perms::allowAll();
        Nonce::allow();
        $GLOBALS['g3d_tests_registered_rest_routes'] = [];

        $this->registerRoutes();
    }

    public function testValidateSignRouteIsPublicAccordingDocs(): void
    {
        $callback = $this->getPermissionCallback('/validate-sign');

        $this->assertRouteIsPublic($callback, '/validate-sign');
    }

    public function testVerifyRouteIsPublicAccordingDocs(): void
    {
        $callback = $this->getPermissionCallback('/verify');

        $this->assertRouteIsPublic($callback, '/verify');
    }

    /**
     * @return callable
     */
    private function getPermissionCallback(string $route): callable
    {
        foreach ($GLOBALS['g3d_tests_registered_rest_routes'] as $registered) {
            if ($registered['namespace'] === 'g3d/v1' && $registered['route'] === $route) {
                self::assertArrayHasKey('permission_callback', $registered['args']);
                self::assertIsCallable($registered['args']['permission_callback']);

                return $registered['args']['permission_callback'];
            }
        }

        self::fail('Route not registered: ' . $route);
    }

    private function assertRouteIsPublic(callable $callback, string $route): void
    {
        Perms::allowAll();
        Nonce::allow();
        $request = $this->createRequest($route, true);
        self::assertTrue($callback($request));

        Perms::denyAll();
        Nonce::allow();
        $request = $this->createRequest($route, true);
        self::assertTrue($callback($request));

        Perms::allowAll();
        Nonce::deny();
        $request = $this->createRequest($route, false);
        self::assertTrue($callback($request));

        Perms::denyAll();
        Nonce::deny();
        $request = $this->createRequest($route, false);
        self::assertTrue($callback($request));
    }

    private function createRequest(string $route, bool $withNonce): WP_REST_Request
    {
        $request = new WP_REST_Request('POST', '/g3d/v1' . $route);

        if ($withNonce) {
            $request->set_header('X-WP-Nonce', 'ok');
        }

        return $request;
    }

    private function registerRoutes(): void
    {
        $validator = $this->createStub(RequestValidator::class);
        $signer = $this->getMockBuilder(\G3D\ValidateSign\Crypto\Signer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $validateController = new ValidateSignController($validator, $signer, 'private-key');
        $validateController->registerRoutes();

        $verifier = $this->getMockBuilder(\G3D\ValidateSign\Crypto\Verifier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $verifyController = new VerifyController($validator, $verifier, 'public-key');
        $verifyController->registerRoutes();
    }
}
