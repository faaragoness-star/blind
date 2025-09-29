<?php
// phpcs:ignoreFile
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

/**
 * Stub mínimo de register_rest_route (no se prueba el router en sí).
 */
if (!function_exists('register_rest_route')) {
    function register_rest_route(string $namespace, string $route, array $args): void
    {
        // No-op: las rutas se validan a través de los tests del controlador.
    }
}

/**
 * Stub de WP_REST_Request compatible con:
 *   new WP_REST_Request('POST', '/ns/route')
 *   new WP_REST_Request(['foo' => 'bar'])
 * Soporta headers + body JSON para get_json_params().
 */
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        /** @var array<string,mixed> */
        private array $params = [];

        /** @var array<string,string> */
        private array $headers = [];

        private ?string $body = null;

        /**
         * @param array<string,mixed>|string $arg1  Array de params, o método HTTP (p.ej. 'POST')
         * @param ?string                    $route Ruta (p.ej. '/g3d/v1/verify') si $arg1 es string (ignorada)
         */
        public function __construct(array|string $arg1 = [], ?string $route = null)
        {
            if (is_array($arg1)) {
                $this->params = $arg1;
                return;
            }
            // Forma WP: método + ruta. Para nuestros tests no necesitamos almacenarlos.
            // Aceptamos la firma para compatibilidad y no hacemos nada más aquí.
        }

        // --- Headers ---------------------------------------------------------

        public function set_header(string $name, string $value): void
        {
            $this->headers[strtolower($name)] = $value;
        }

        public function get_header(string $name): ?string
        {
            $key = strtolower($name);
            return $this->headers[$key] ?? null;
        }

        // --- Body ------------------------------------------------------------

        public function set_body(?string $body): void
        {
            $this->body = $body;
        }

        public function get_body(): ?string
        {
            return $this->body;
        }

        /**
         * Devuelve el payload JSON si:
         *  - Content-Type incluye "application/json", y
         *  - hay body JSON parseable.
         * En otro caso, devuelve los params proporcionados al constructor.
         *
         * @return array<string,mixed>
         */
        public function get_json_params(): array
        {
            $ct = $this->get_header('Content-Type') ?? $this->get_header('content-type') ?? '';

            if ($this->body !== null && stripos($ct, 'application/json') !== false) {
                $decoded = json_decode($this->body, true);
                return is_array($decoded) ? $decoded : [];
            }

            return $this->params;
        }
    }
}

/**
 * Stub de WP_REST_Response.
 */
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        /** @var mixed */
        private $data;

        private int $status;

        public function __construct(mixed $data = null, int $status = 200)
        {
            $this->data   = $data;
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

/**
 * Stub de WP_Error.
 */
if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private string $code;
        private string $message;

        /** @var array<string,mixed> */
        private array $data;

        /**
         * @param array<string,mixed> $data
         */
        public function __construct(string $code, string $message, array $data = [])
        {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
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
         * @return array<string,mixed>
         */
        public function get_error_data(): array
        {
            return $this->data;
        }
    }
}
