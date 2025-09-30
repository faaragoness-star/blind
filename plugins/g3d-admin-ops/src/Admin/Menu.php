<?php

declare(strict_types=1);

namespace G3D\AdminOps\Admin;

use G3D\AdminOps\Audit\AuditLogReader;
use G3D\AdminOps\Rbac\Capabilities;
use G3D\AdminOps\Rbac\CapabilityGuard;

use function esc_html;
use function esc_html__;
use function sprintf;

final class Menu
{
    private const ROOT_SLUG = 'g3d-admin-ops';

    /** @var array<string, array{page_title:string, menu_title:string, capability:string, doc:string}> */
    private const SECTIONS = [
        'modelos-glb' => [
            'page_title' => 'Modelos (GLB)',
            'menu_title' => 'Modelos (GLB)',
            'capability' => Capabilities::CAP_MANAGE_DRAFTS,
            'doc' => 'Capa5 §3.1',
        ],
        'materiales' => [
            'page_title' => 'Materiales',
            'menu_title' => 'Materiales',
            'capability' => Capabilities::CAP_MANAGE_DRAFTS,
            'doc' => 'Capa5 §3.2',
        ],
        'colores' => [
            'page_title' => 'Colores',
            'menu_title' => 'Colores',
            'capability' => Capabilities::CAP_MANAGE_DRAFTS,
            'doc' => 'Capa5 §3.3',
        ],
        'texturas' => [
            'page_title' => 'Texturas',
            'menu_title' => 'Texturas',
            'capability' => Capabilities::CAP_MANAGE_DRAFTS,
            'doc' => 'Capa5 §3.4',
        ],
        'acabados' => [
            'page_title' => 'Acabados',
            'menu_title' => 'Acabados',
            'capability' => Capabilities::CAP_MANAGE_DRAFTS,
            'doc' => 'Capa5 §3.5',
        ],
        'reglas' => [
            'page_title' => 'Reglas',
            'menu_title' => 'Reglas',
            'capability' => Capabilities::CAP_MANAGE_DRAFTS,
            'doc' => 'Capa5 §3.6',
        ],
        'i18n' => [
            'page_title' => 'i18n',
            'menu_title' => 'i18n',
            'capability' => Capabilities::CAP_MANAGE_DRAFTS,
            'doc' => 'Capa5 §3.7',
        ],
        'previsualizacion' => [
            'page_title' => 'Previsualización',
            'menu_title' => 'Previsualización',
            'capability' => Capabilities::CAP_RUN_VALIDATOR,
            'doc' => 'Capa5 §3.8',
        ],
        'publicacion' => [
            'page_title' => 'Publicación',
            'menu_title' => 'Publicación',
            'capability' => Capabilities::CAP_MANAGE_PUBLICATION,
            'doc' => 'Capa5 §3.9',
        ],
        'versiones-auditoria' => [
            'page_title' => 'Versiones & Auditoría',
            'menu_title' => 'Versiones & Auditoría',
            'capability' => Capabilities::CAP_MANAGE_PUBLICATION,
            'doc' => 'Capa5 §3.10',
        ],
        'configuracion' => [
            'page_title' => 'Configuración',
            'menu_title' => 'Configuración',
            'capability' => Capabilities::CAP_MANAGE_CONFIGURATION,
            'doc' => 'Capa5 §3.11',
        ],
        'slots' => [
            'page_title' => 'Slots (mapeo editorial)',
            'menu_title' => 'Slots',
            'capability' => Capabilities::CAP_MANAGE_DRAFTS,
            'doc' => 'Capa5 §3.12',
        ],
        'parejas-patillas' => [
            'page_title' => 'Parejas de Patillas',
            'menu_title' => 'Parejas L/R',
            'capability' => Capabilities::CAP_MANAGE_DRAFTS,
            'doc' => 'Capa5 §Addenda 2025-09-27',
        ],
    ];

    public function __construct(
        private CapabilityGuard $guard,
        private AuditLogReader $auditLogReader
    ) {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu'], 20);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('G3D Admin & Ops', 'g3d-admin-ops'),
            __('G3D Admin & Ops', 'g3d-admin-ops'),
            Capabilities::CAP_MANAGE_DRAFTS,
            self::ROOT_SLUG,
            [$this, 'renderRoot']
        );

        foreach (self::SECTIONS as $slug => $section) {
            $callback = $slug === 'versiones-auditoria'
                ? [$this, 'renderAuditTrail']
                : function () use ($section): void {
                    $this->renderPlaceholder($section['page_title'], $section['doc']);
                };

            add_submenu_page(
                self::ROOT_SLUG,
                $section['page_title'],
                $section['menu_title'],
                $section['capability'],
                self::ROOT_SLUG . '-' . $slug,
                $callback
            );
        }
    }

    public function renderRoot(): void
    {
        $this->renderPlaceholder('G3D Admin & Ops', 'Plugin5 §5');
    }

    public function renderAuditTrail(): void
    {
        if (!$this->guard->can(Capabilities::CAP_MANAGE_PUBLICATION)) {
            echo '<div class="wrap">';
            printf('<p>%s</p>', esc_html__('Acceso denegado.', 'g3d-admin-ops'));
            echo '</div>';

            return;
        }

        echo '<div class="wrap">';
        printf('<h1>%s</h1>', esc_html__('Versiones & Auditoría', 'g3d-admin-ops'));
        printf(
            '<p>%s</p>',
            esc_html(
                sprintf(
                    /* translators: %s is the documentation reference. */
                    __('Registro quién/cuándo/qué (ver %s).', 'g3d-admin-ops'),
                    'docs/plugin-5-g3d-admin-ops.md §13'
                )
            )
        );

        $events = $this->auditLogReader->getEvents();

        if ($events === []) {
            printf('<p>%s</p>', esc_html__('Sin eventos registrados.', 'g3d-admin-ops'));
            echo '</div>';

            return;
        }

        echo '<table>';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Quién', 'g3d-admin-ops') . '</th>';
        echo '<th>' . esc_html__('Cuándo', 'g3d-admin-ops') . '</th>';
        echo '<th>' . esc_html__('Acción', 'g3d-admin-ops') . '</th>';
        echo '<th>' . esc_html__('Qué', 'g3d-admin-ops') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($events as $event) {
            echo '<tr>';
            echo '<td>' . esc_html($event['actor_id']) . '</td>';
            echo '<td>' . esc_html($event['occurred_at']) . '</td>';
            echo '<td>' . esc_html($event['action']) . '</td>';
            echo '<td>' . esc_html($event['what']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        printf(
            '<p>%s</p>',
            esc_html__(
                'TODO: persistir 90 días en almacenamiento aprobado '
                . '(docs/Capa 5 — Admin & Operaciones — Addenda Aplicada 2025-09-27.md '
                . '§Auditoría y logs).',
                'g3d-admin-ops'
            )
        );
        echo '</div>';
    }

    private function renderPlaceholder(string $title, string $docSection): void
    {
        echo '<div class="wrap">';
        printf('<h1>%s</h1>', esc_html($title));
        printf(
            '<p>%s</p>',
            esc_html(
                sprintf(
                    /* translators: %s is the documentation reference. */
                    __('TODO: ver %s', 'g3d-admin-ops'),
                    $docSection
                )
            )
        );
        echo '</div>';
    }
}
