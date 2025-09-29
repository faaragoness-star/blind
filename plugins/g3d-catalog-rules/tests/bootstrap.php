<?php
// phpcs:ignoreFile

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'G3D\\CatalogRules\\',
        'G3dCatalogRules\\',
    ];

    foreach ($prefixes as $prefix) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $relativePath = str_replace('\\', '/', $relative);
        $file = __DIR__ . '/../src/' . $relativePath . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
});

$GLOBALS['g3d_catalog_rules_registered_routes'] = [];

if (!function_exists('register_rest_route')) {
    function register_rest_route(string $namespace, string $route, array $args): void
    {
        $GLOBALS['g3d_catalog_rules_registered_routes'][] = [
            'namespace' => $namespace,
            'route' => $route,
            'args' => $args,
        ];
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        /** @var array<string, mixed> */
        private array $params = [];

        /** @var array<string, string> */
        private array $headers = [];

        private ?string $body = null;

        /**
         * @param array<string, mixed>|string $arg1
         * @param ?string                     $route
         */
        public function __construct(array|string $arg1 = [], ?string $route = null)
        {
            if ($route !== null) {
                // Compatibilidad de firma.
            }

            if (is_array($arg1)) {
                $this->params = $arg1;
            }
        }

        public function set_header(string $name, string $value): void
        {
            $this->headers[strtolower($name)] = $value;
        }

        public function get_header(string $name): ?string
        {
            $key = strtolower($name);

            return $this->headers[$key] ?? null;
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
        public function get_json_params(): array
        {
            $contentType = $this->get_header('content-type') ?? $this->get_header('Content-Type') ?? '';

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
        /** @param array<string, mixed> $data */
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
