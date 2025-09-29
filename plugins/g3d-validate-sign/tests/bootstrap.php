<?php
// phpcs:ignoreFile

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

if (!function_exists('register_rest_route')) {
    function register_rest_route(string $namespace, string $route, array $args): void
    {
        // Stub: routing is validated via controller tests.
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        /**
         * @var array<string, mixed>
         */
        private array $params = [];

        /**
         * Admite dos firmas:
         *  A) new WP_REST_Request([payload...])
         *  B) new WP_REST_Request('POST', '/route', [payload...])  // estilo WordPress
         */
        public function __construct(mixed $methodOrParams = [], string $route = '', array $attributes = [])
        {
            if (is_array($methodOrParams)) {
                // Firma A: recibimos directamente el payload
                $this->params = $methodOrParams;
                return;
            }

            // Firma B: ($method, $route, $attributes)
            $this->params = $attributes;
        }

        /**
         * @return array<string, mixed>
         */
        public function get_json_params(): array
        {
            return $this->params;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        /**
         * @var mixed
         */
        private $data;

        private int $status;

        public function __construct(mixed $data = null, int $status = 200)
        {
            $this->data = $data;
            $this->status = $status;
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
        private string $code;
        private string $message;

        /**
         * @var array<string, mixed>
         */
        private array $data;

        /**
         * @param array<string, mixed> $data
         */
        public function __construct(string $code, string $message, array $data = [])
        {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
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
