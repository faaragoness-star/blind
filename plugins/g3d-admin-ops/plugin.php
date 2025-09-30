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
use G3D\AdminOps\Services\Registry;

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
    $logger = null;

    // 1) Intentar resolver desde el contenedor (si existe).
    if (class_exists(\G3D\AdminOps\Services\Registry::class)) {
        $service = Registry::instance()->get(Registry::S_AUDIT_LOGGER);

        if (
            $service instanceof \G3D\AdminOps\Audit\AuditLogReader
            && $service instanceof \G3D\AdminOps\Audit\EditorialActionLogger
        ) {
            $logger = $service;
        }
    }

    // 2) Intentar globals (por compat).
    if ($logger === null) {
        $candidate = $GLOBALS['g3d_admin_ops_audit_writer'] ?? ($GLOBALS['g3d_admin_ops_audit_reader'] ?? null);

        if (
            $candidate instanceof \G3D\AdminOps\Audit\AuditLogReader
            && $candidate instanceof \G3D\AdminOps\Audit\EditorialActionLogger
        ) {
            $logger = $candidate;
        }
    }

    // 3) Fallback en memoria y exponer globals para compatibilidad.
    if ($logger === null) {
        $logger = new \G3D\AdminOps\Audit\InMemoryEditorialActionLogger();
        // TODO(doc §bootstrap): mover a contenedor cuando tengamos DI real.
        $GLOBALS['g3d_admin_ops_audit_reader'] = $logger;
        $GLOBALS['g3d_admin_ops_audit_writer'] = $logger;
    }

    (new \G3D\AdminOps\Api\AuditWriteController($logger))->registerRoutes();
    (new \G3D\AdminOps\Api\AuditReadController($logger))->registerRoutes();
});
