<?php

/**
 * Plugin Name: G3D Validate & Sign
 * Description: Validación y firma Ed25519 para SKUs. Ver docs/.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: g3d-validate-sign
 */

declare(strict_types=1);

use G3D\ValidateSign\Api\ValidateSignController;
use G3D\ValidateSign\Api\VerifyController;
use G3D\ValidateSign\Crypto\Signer;
use G3D\ValidateSign\Crypto\Verifier;
use G3D\ValidateSign\Validation\RequestValidator;
use G3D\VendorBase\Time\SystemClock;

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', static function (): void {
    load_plugin_textdomain('g3d-validate-sign', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('rest_api_init', static function (): void {
    $schemaDir = __DIR__ . '/schemas';

    $validateValidator = new RequestValidator($schemaDir . '/validate-sign.request.schema.json');
    $verifyValidator = new RequestValidator($schemaDir . '/verify.request.schema.json');

    $clock = new SystemClock();
    $signer = new Signer(null, $clock);
    $privateKey = defined('G3D_VALIDATE_SIGN_PRIVATE_KEY') ? (string) G3D_VALIDATE_SIGN_PRIVATE_KEY : '';
    $validateController = new ValidateSignController($validateValidator, $signer, $privateKey);

    $verifier = new Verifier(Signer::ALLOWED_SIGNATURE_PREFIXES, $clock);
    $publicKey = defined('G3D_VALIDATE_SIGN_PUBLIC_KEY') ? (string) G3D_VALIDATE_SIGN_PUBLIC_KEY : '';
    $verifyController = new VerifyController($verifyValidator, $verifier, $publicKey);

    $validateController->registerRoutes();
    $verifyController->registerRoutes();
});
