<?php
// phpcs:ignoreFile

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'G3D\\VendorBase\\')) {
        return;
    }

    $relative = substr($class, strlen('G3D\\VendorBase\\'));
    $relativePath = str_replace('\\', '/', $relative);
    $file = __DIR__ . '/../src/' . $relativePath . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!function_exists('plugin_basename')) {
    function plugin_basename(string $file): string
    {
        return basename($file);
    }
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain(string $domain, bool $deprecated = false, string $pluginRelPath = ''): void
    {
        // No-op en pruebas.
    }
}

if (!function_exists('register_activation_hook')) {
    /** @var array<string, callable> $GLOBALS['g3d_vendor_base_helper_activation_hooks'] */
    $GLOBALS['g3d_vendor_base_helper_activation_hooks'] = [];

    function register_activation_hook(string $file, callable $callback): void
    {
        $GLOBALS['g3d_vendor_base_helper_activation_hooks'][$file] = $callback;
    }
}

if (!function_exists('register_deactivation_hook')) {
    /** @var array<string, callable> $GLOBALS['g3d_vendor_base_helper_deactivation_hooks'] */
    $GLOBALS['g3d_vendor_base_helper_deactivation_hooks'] = [];

    function register_deactivation_hook(string $file, callable $callback): void
    {
        $GLOBALS['g3d_vendor_base_helper_deactivation_hooks'][$file] = $callback;
    }
}
