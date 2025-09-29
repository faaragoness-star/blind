<?php

/**
 * Plugin Name: G3D Admin & Ops
 * Description: Esqueleto inicial (sin lógica). Ver docs/Plugin 5 — G3d Admin & Ops — Informe.md.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: g3d-admin-ops
 */

declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', static function (): void {
    load_plugin_textdomain('g3d-admin-ops', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

require_once __DIR__ . '/src/Admin/Menu.php';
require_once __DIR__ . '/src/Rbac/Capabilities.php';
require_once __DIR__ . '/src/Audit/Contracts.php';

register_activation_hook(__FILE__, static function (): void {
    // TODO: ver Capa5 §16 (Backups & DR).
});

register_deactivation_hook(__FILE__, static function (): void {
    // TODO: ver Capa5 §Checklists operativas.
});

$adminOpsMenu = new \G3D\AdminOps\Admin\Menu();
$adminOpsMenu->register();
