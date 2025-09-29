<?php

declare(strict_types=1);

namespace G3D\AdminOps\Rbac;

final class Capabilities
{
    public const ROLE_EDITOR = 'Editor';
    public const ROLE_QA_REVISOR = 'QA/Revisor';
    public const ROLE_PUBLICADOR = 'Publicador';
    public const ROLE_ADMIN = 'Admin';

    public const WORKFLOW_BORRADOR = 'Borrador';
    public const WORKFLOW_EN_REVISION = 'En revisión';
    public const WORKFLOW_APROBADO_QA = 'Aprobado QA';
    public const WORKFLOW_STAGING = 'Staging';
    public const WORKFLOW_PUBLICADO = 'Publicado';

    public const PAIR_SYNC_CONTROLS_DEFAULT = [
        'material',
        'color',
        'textura',
    ];

    public const PAIR_UNSYNCED_CONTROLS_DEFAULT = [
        'acabado',
    ];
}
