<?php

declare(strict_types=1);

namespace G3D\AdminOps;

use G3D\AdminOps\Admin\Menu;
use G3D\AdminOps\Audit\InMemoryEditorialActionLogger;
use G3D\AdminOps\Rbac\CapabilityGuard;
use G3D\AdminOps\Services\Registry;

final class Plugin
{
    private Menu $menu;

    private InMemoryEditorialActionLogger $auditLogger;

    private CapabilityGuard $guard;

    public function __construct()
    {
        $this->auditLogger = new InMemoryEditorialActionLogger();
        Registry::instance()->set(Registry::S_AUDIT_LOGGER, $this->auditLogger);
        $this->guard = new CapabilityGuard();
        $this->menu = new Menu($this->guard, $this->auditLogger);
    }

    public function register(): void
    {
        $this->menu->register();
    }

    public function auditLogger(): InMemoryEditorialActionLogger
    {
        return $this->auditLogger;
    }

    public function guard(): CapabilityGuard
    {
        return $this->guard;
    }
}
