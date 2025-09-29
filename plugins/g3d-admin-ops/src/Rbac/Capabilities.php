<?php

declare(strict_types=1);

namespace G3D\AdminOps\Rbac;

final class Capabilities
{
    public const CAP_MANAGE_DRAFTS = 'g3d_manage_drafts';
    public const CAP_RUN_VALIDATOR = 'g3d_run_validator';
    public const CAP_MANAGE_PUBLICATION = 'g3d_manage_publication';
    public const CAP_MANAGE_CONFIGURATION = 'g3d_manage_configuration';

    private function __construct()
    {
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CAP_MANAGE_DRAFTS,
            self::CAP_RUN_VALIDATOR,
            self::CAP_MANAGE_PUBLICATION,
            self::CAP_MANAGE_CONFIGURATION,
        ];
    }
}
