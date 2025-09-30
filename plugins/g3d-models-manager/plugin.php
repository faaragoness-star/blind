<?php

/**
 * Plugin Name: G3D Models Manager
 * Description: Esqueleto inicial (sin lógica). Ver docs/.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: g3d-models-manager
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', static function (): void {
    load_plugin_textdomain('g3d-models-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('rest_api_init', static function (): void {
    $service = $GLOBALS['g3d_models_manager_glb_service']
        ?? new \G3D\ModelsManager\Service\GlbIngestionService();

    $GLOBALS['g3d_models_manager_glb_service'] = $service;

    (new \G3D\ModelsManager\Api\GlbIngestController($service))->registerRoutes();
    (new \G3D\ModelsManager\Api\IngestionController($service))->registerRoutes();
});
