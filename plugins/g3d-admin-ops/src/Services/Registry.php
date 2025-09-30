<?php

declare(strict_types=1);

namespace G3D\AdminOps\Services;

/**
 * Registro sencillo de servicios para compartir instancias entre UI y REST.
 */
final class Registry
{
    public const S_AUDIT_LOGGER = 'audit_logger';

    /**
     * @var array<string, object>
     */
    private array $services = [];

    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function set(string $id, object $service): void
    {
        $this->services[$id] = $service;
    }

    public function get(string $id): ?object
    {
        return $this->services[$id] ?? null;
    }
}
