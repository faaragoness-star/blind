<?php

/**
 * Plugin Name: G3D Admin & Ops
 * Description: Utilidades de administración/operaciones. Ver docs/.
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Author: faaragoness-star
 * License: MIT
 * Text Domain: g3d-admin-ops
 */

declare(strict_types=1);

use G3D\AdminOps\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'G3D\\AdminOps\\')) {
        return;
    }

    $relative = substr($class, strlen('G3D\\AdminOps\\'));
    $relativePath = str_replace('\\', '/', $relative);
    $file = __DIR__ . '/src/' . $relativePath . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

add_action('init', static function (): void {
    load_plugin_textdomain('g3d-admin-ops', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

$plugin = new Plugin();
$plugin->register();

$GLOBALS['g3d_admin_ops_audit_reader'] = $plugin->auditLogger();
$GLOBALS['g3d_admin_ops_audit_writer'] = $plugin->auditLogger();

register_activation_hook(__FILE__, static function (): void {
    // TODO(doc §RBAC roles->caps): asignar capacidades a roles según doc.
    // Ejemplo (comentar si el doc no lo fija):
    // $admin = get_role('administrator');
    // if ($admin) {
    //     $admin->add_cap(\G3D\AdminOps\Rbac\Capabilities::CAP_MANAGE_DRAFTS);
    //     $admin->add_cap(\G3D\AdminOps\Rbac\Capabilities::CAP_RUN_VALIDATOR);
    //     $admin->add_cap(\G3D\AdminOps\Rbac\Capabilities::CAP_MANAGE_PUBLICATION);
    //     $admin->add_cap(\G3D\AdminOps\Rbac\Capabilities::CAP_MANAGE_CONFIGURATION);
    // }
});

add_action('rest_api_init', static function (): void {
    $logger = $GLOBALS['g3d_admin_ops_audit_writer'] ?? null;

    if (!($logger instanceof \G3D\AdminOps\Audit\EditorialActionLogger
        && $logger instanceof \G3D\AdminOps\Audit\AuditLogReader)
    ) {
        $logger = $GLOBALS['g3d_admin_ops_audit_reader'] ?? null;
    }

    if (!($logger instanceof \G3D\AdminOps\Audit\EditorialActionLogger
        && $logger instanceof \G3D\AdminOps\Audit\AuditLogReader)
    ) {
        $logger = new \G3D\AdminOps\Audit\InMemoryEditorialActionLogger();
        // TODO(doc §bootstrap): mover a contenedor cuando tengamos DI real.
        $GLOBALS['g3d_admin_ops_audit_reader'] = $logger;
        $GLOBALS['g3d_admin_ops_audit_writer'] = $logger;
    }

    (new \G3D\AdminOps\Api\AuditWriteController($logger))->registerRoutes();
    (new \G3D\AdminOps\Api\AuditReadController($logger))->registerRoutes();
});
