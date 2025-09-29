<?php

declare(strict_types=1);

namespace G3D\AdminOps\Rbac;

final class Capabilities
{
    /**
     * Capacidad para tareas de Editor: crear/editar en Borrador, cargar GLB e i18n local
     * (docs/plugin-5-g3d-admin-ops.md §4).
     */
    public const CAP_MANAGE_DRAFTS = 'g3d_admin_ops_manage_drafts';

    /**
     * Capacidad para QA/Revisor: ejecutar Validador y marcar listo/aprobado
     * (docs/plugin-5-g3d-admin-ops.md §4).
     */
    public const CAP_RUN_VALIDATOR = 'g3d_admin_ops_run_validator';

    /**
     * Capacidad para Publicador: snapshot + publicar/rollback (docs/plugin-5-g3d-admin-ops.md §4).
     */
    public const CAP_MANAGE_PUBLICATION = 'g3d_admin_ops_manage_publication';

    /**
     * Capacidad para Admin de configuración: firma, caducidades, CORS, backups (docs/plugin-5-g3d-admin-ops.md §4).
     */
    public const CAP_MANAGE_CONFIGURATION = 'g3d_admin_ops_manage_configuration';

    /**
     * TODO: definir mapeo roles↔capacidades (docs/plugin-5-g3d-admin-ops.md §4).
     */
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
