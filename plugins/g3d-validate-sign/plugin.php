<?php

/**
 * Plugin Name: G3D Validate & Sign
 * Description: Esqueleto inicial (sin lógica). Ver docs/ para funciones y contratos.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: g3d-validate-sign
 */

if (!defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, function () {
    // Placeholder de activación (nop).
});
register_deactivation_hook(__FILE__, function () {
    // Placeholder de desactivación (nop).
});

add_action('init', function () {
    load_plugin_textdomain('g3d-validate-sign', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('rest_api_init', function () {
    $basePath = plugin_dir_path(__FILE__);

    require_once $basePath . 'src/Validation/RequestValidator.php';
    require_once $basePath . 'src/Domain/Expiry.php';
    require_once $basePath . 'src/Crypto/Signer.php';
    require_once $basePath . 'src/Crypto/Verifier.php';
    require_once $basePath . 'src/Api/ValidateSignController.php';
    require_once $basePath . 'src/Api/VerifyController.php';

    $validateRequestValidator = new \G3D\ValidateSign\Validation\RequestValidator(
        $basePath . 'schemas/validate-sign.request.schema.json'
    );

    $verifyRequestValidator = new \G3D\ValidateSign\Validation\RequestValidator(
        $basePath . 'schemas/verify.request.schema.json'
    );

    $expiry = new \G3D\ValidateSign\Domain\Expiry();
    $signaturePrefix = apply_filters('g3d_validate_sign_signature_prefix', 'sig.v1');
    $privateKey = apply_filters('g3d_validate_sign_private_key', '');
    $publicKey = apply_filters('g3d_validate_sign_public_key', '');

    $signer = new \G3D\ValidateSign\Crypto\Signer($signaturePrefix);
    $verifier = new \G3D\ValidateSign\Crypto\Verifier([$signaturePrefix]);

    (new \G3D\ValidateSign\Api\ValidateSignController(
        $validateRequestValidator,
        $signer,
        $expiry,
        $privateKey
    ))->registerRoutes();

    (new \G3D\ValidateSign\Api\VerifyController(
        $verifyRequestValidator,
        $verifier,
        $expiry,
        $publicKey
    ))->registerRoutes();
});
