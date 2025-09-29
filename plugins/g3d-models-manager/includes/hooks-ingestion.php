<?php

declare(strict_types=1);

use G3D\ModelsManager\Admin\AdminUI;
use G3D\ModelsManager\Service\GlbIngestionService;
use G3D\ModelsManager\Validation\GlbIngestionValidator;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/../src/Service/GlbIngestionService.php';
require_once __DIR__ . '/../src/Validation/GlbIngestionValidator.php';
require_once __DIR__ . '/../src/Validation/GlbValidationError.php';
require_once __DIR__ . '/../admin/AdminUI.php';

add_action('plugins_loaded', static function (): void {
    $validator = new GlbIngestionValidator();
$service = new GlbIngestionService();
    $ui = new AdminUI($service);

    add_action('admin_menu', static function () use ($ui): void {
        $ui->register();
    });
    // TODO: docs/Plugin 1 — G3d Models Manager (ingesta Glb Y Binding Técnico)
    //       — Informe.md §4 — enlazar flujo completo.
});
