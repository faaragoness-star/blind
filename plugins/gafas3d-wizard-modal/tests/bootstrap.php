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

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path(string $file): string
    {
        return rtrim(dirname($file), '\\/') . '/';
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url(string $path = '', string $pluginFile = ''): string
    {
        $base = 'https://example.com/wp-content/plugins';

        if ($pluginFile !== '') {
            $base .= '/' . basename(dirname($pluginFile));
        }

        if ($path !== '') {
            $path = '/' . ltrim($path, '/');
        }

        return $base . $path;
    }
}

if (!function_exists('rest_url')) {
    function rest_url(string $path = ''): string
    {
        $base = 'http://example.test/wp-json/';

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce(string $action = 'wp_rest'): string
    {
        return 'nonce-123';
    }
}

if (!function_exists('get_locale')) {
    function get_locale(): string
    {
        return 'es_ES';
    }
}

if (!isset($GLOBALS['g3d_wizard_modal_enqueued_scripts'])) {
    /**
     * @var array<string, array{src:string,deps:array<int, string>,ver:string|bool,in_footer:bool}> $GLOBALS['g3d_wizard_modal_enqueued_scripts']
     */
    $GLOBALS['g3d_wizard_modal_enqueued_scripts'] = [];
}

if (!isset($GLOBALS['g3d_wizard_modal_enqueued_styles'])) {
    /**
     * @var array<string, array{src:string,deps:array<int, string>,ver:string|bool,media:string}> $GLOBALS['g3d_wizard_modal_enqueued_styles']
     */
    $GLOBALS['g3d_wizard_modal_enqueued_styles'] = [];
}

if (!isset($GLOBALS['g3d_wizard_modal_registered_scripts'])) {
    /**
     * @var array<string, array{src:string,deps:array<int, string>,ver:string|bool,in_footer:bool}> $GLOBALS['g3d_wizard_modal_registered_scripts']
     */
    $GLOBALS['g3d_wizard_modal_registered_scripts'] = [];
}

if (!isset($GLOBALS['g3d_wizard_modal_registered_styles'])) {
    /**
     * @var array<string, array{src:string,deps:array<int, string>,ver:string|bool,media:string}> $GLOBALS['g3d_wizard_modal_registered_styles']
     */
    $GLOBALS['g3d_wizard_modal_registered_styles'] = [];
}

if (!isset($GLOBALS['g3d_wizard_modal_localized_scripts'])) {
    /**
     * @var array<string, array<string, mixed>> $GLOBALS['g3d_wizard_modal_localized_scripts']
     */
    $GLOBALS['g3d_wizard_modal_localized_scripts'] = [];
}

if (!function_exists('wp_enqueue_script')) {
    /**
     * @param array<int, string> $deps
     */
    function wp_enqueue_script(
        string $handle,
        string $src = '',
        array $deps = [],
        string|bool $ver = false,
        bool $inFooter = false
    ): void {
        $GLOBALS['g3d_wizard_modal_enqueued_scripts'][$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'in_footer' => $inFooter,
        ];
    }
}

if (!function_exists('wp_register_script')) {
    /**
     * @param array<int, string> $deps
     */
    function wp_register_script(
        string $handle,
        string $src,
        array $deps = [],
        string|bool $ver = false,
        bool $inFooter = false
    ): void {
        $GLOBALS['g3d_wizard_modal_registered_scripts'][$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'in_footer' => $inFooter,
        ];
    }
}

if (!function_exists('wp_register_style')) {
    /**
     * @param array<int, string> $deps
     */
    function wp_register_style(
        string $handle,
        string $src,
        array $deps = [],
        string|bool $ver = false,
        string $media = 'all'
    ): void {
        $GLOBALS['g3d_wizard_modal_registered_styles'][$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'media' => $media,
        ];
    }
}

if (!function_exists('wp_localize_script')) {
    /**
     * @param array<string, mixed> $l10n
     */
    function wp_localize_script(string $handle, string $objectName, array $l10n): void
    {
        $GLOBALS['g3d_wizard_modal_localized_scripts'][$handle][$objectName] = $l10n;
    }
}

if (!function_exists('wp_enqueue_style')) {
    /**
     * @param array<int, string> $deps
     */
    function wp_enqueue_style(
        string $handle,
        string $src = '',
        array $deps = [],
        string|bool $ver = false,
        string $media = 'all'
    ): void {
        $GLOBALS['g3d_wizard_modal_enqueued_styles'][$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'media' => $media,
        ];
    }
}
