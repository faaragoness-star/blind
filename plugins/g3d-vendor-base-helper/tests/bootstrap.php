<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace {

    /**
     * @phpstan-type RestPayload array<string, mixed>
     */

$autoload = __DIR__ . '/../../../vendor/autoload.php';

if (is_file($autoload)) {
    require $autoload;
}

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

if (!isset($GLOBALS['g3d_tests_registered_rest_routes'])) {
    /**
     * @var list<array{namespace:string,route:string,args:array<string,mixed>}> $GLOBALS['g3d_tests_registered_rest_routes']
     */
    $GLOBALS['g3d_tests_registered_rest_routes'] = [];
}

if (!function_exists('register_rest_route')) {
    /**
     * @param array<string, mixed> $args
     */
    function register_rest_route(string $ns, string $route, array $args): void
    {
        foreach ($GLOBALS['g3d_tests_registered_rest_routes'] as $index => $registered) {
            if ($registered['namespace'] === $ns && $registered['route'] === $route) {
                $existing = $registered['args']['methods'] ?? '';
                $incoming = $args['methods'] ?? '';

                if (is_string($existing) && is_string($incoming)) {
                    $merged = array_filter(array_map('trim', explode(',', $existing)));
                    foreach (array_filter(array_map('trim', explode(',', $incoming))) as $method) {
                        if ($method !== '' && !in_array($method, $merged, true)) {
                            $merged[] = $method;
                        }
                    }

                    $registered['args']['methods'] = implode(',', $merged);
                } else {
                    $registered['args']['methods'] = $incoming;
                }

                $GLOBALS['g3d_tests_registered_rest_routes'][$index] = $registered;

                return;
            }
        }

        $GLOBALS['g3d_tests_registered_rest_routes'][] = [
            'namespace' => $ns,
            'route' => $route,
            'args' => $args,
        ];
    }
}

// --- Hooks WP mínimos para pruebas ---
if (!isset($GLOBALS['g3d_tests_wp_actions'])) {
    /** @var array<string, array<int, array{priority:int, cb:callable}>> $GLOBALS['g3d_tests_wp_actions'] */
    $GLOBALS['g3d_tests_wp_actions'] = [];
}

if (!function_exists('add_action')) {
    function add_action(string $hook, callable $cb, int $priority = 10, int $args = 1): void
    {
        $GLOBALS['g3d_tests_wp_actions'][$hook] ??= [];
        $GLOBALS['g3d_tests_wp_actions'][$hook][] = ['priority' => $priority, 'cb' => $cb];
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hook, mixed ...$params): void
    {
        $list = $GLOBALS['g3d_tests_wp_actions'][$hook] ?? [];
        usort($list, static fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($list as $item) {
            ($item['cb'])(...$params);
        }
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        /** @var array<string, mixed> */
        private array $params = [];

        /**
         * @var array<string, string>
         */
        private array $headers = [];

        private ?string $body = null;

        /**
         * @param array<string, mixed>|string $params
         */
        public function __construct(array|string $params = [], ?string $route = null)
        {
            if ($route !== null) {
                // Firma compatible con WP.
            }

            if (is_array($params)) {
                $this->params = $params;
            }
        }

        public function set_header(string $key, string $value): void
        {
            $this->headers[strtolower($key)] = $value;
        }

        public function get_header(string $key): ?string
        {
            $lookup = strtolower($key);

            return $this->headers[$lookup] ?? null;
        }

        public function set_body(?string $body): void
        {
            $this->body = $body;
        }

        public function get_body(): ?string
        {
            return $this->body;
        }

        /**
         * @return array<string, mixed>
         */
        public function get_params(): array
        {
            if ($this->params !== []) {
                return $this->params;
            }

            return $this->get_json_params();
        }

        public function get_param(string $key): mixed
        {
            $params = $this->get_params();

            return $params[$key] ?? null;
        }

        public function set_param(string $key, mixed $value): void
        {
            $this->params[$key] = $value;
        }

        /**
         * @param array<string, mixed> $params
         */
        public function set_params(array $params): void
        {
            foreach ($params as $key => $value) {
                $this->set_param((string) $key, $value);
            }
        }

        /**
         * @return array<string, mixed>
         */
        public function get_json_params(): array
        {
            $contentType = $this->get_header('Content-Type') ?? $this->get_header('content-type') ?? '';

            if ($this->body !== null && stripos($contentType, 'application/json') !== false) {
                $decoded = json_decode($this->body, true);

                return is_array($decoded) ? $decoded : [];
            }

            return $this->params;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        public function __construct(private mixed $data = null, private int $status = 200)
        {
        }

        public function get_data(): mixed
        {
            return $this->data;
        }

        public function get_status(): int
        {
            return $this->status;
        }
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        /**
         * @param array<string, mixed> $data
         */
        public function __construct(private string $code, private string $message, private array $data = [])
        {
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        /**
         * @return array<string, mixed>
         */
        public function get_error_data(): array
        {
            return $this->data;
        }
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $cap): bool
    {
        // TODO(doc §auth)
        return \Test_Env\Perms::allows($cap);
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce(string $nonce, string $action = 'wp_rest'): bool
    {
        return \Test_Env\Nonce::verify($nonce, $action);
    }
}

}

namespace Test_Env {
    final class Nonce
    {
        private static bool $allow = true;

        public static function allow(): void
        {
            self::$allow = true;
        }

        public static function deny(): void
        {
            self::$allow = false;
        }

        public static function verify(string $nonce, string $action): bool
        {
            return self::$allow;
        }
    }

    final class Perms
    {
        private static bool $allow = true;

        public static function allowAll(): void
        {
            self::$allow = true;
        }

        public static function denyAll(): void
        {
            self::$allow = false;
        }

        public static function allows(string $cap): bool
        {
            return self::check($cap);
        }

        public static function check(string $cap): bool
        {
            return self::$allow;
        }
    }
}
