<?php
// phpcs:ignoreFile

declare(strict_types=1);

require_once __DIR__ . '/../../g3d-vendor-base-helper/tests/bootstrap.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Gafas3d\\WizardModal\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relative);
    $file = __DIR__ . '/../src/' . $relativePath . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return $text;
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}
