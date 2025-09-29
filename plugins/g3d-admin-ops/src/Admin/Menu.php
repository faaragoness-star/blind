<?php

declare(strict_types=1);

namespace G3D\AdminOps\Admin;

final class Menu
{
    private const ROOT_SLUG = 'g3d-admin-ops';

    /** @var array<string, array{page_title:string, menu_title:string, capability:string, doc:string}> */
    private const SECTIONS = [
        'modelos-glb' => [
            'page_title' => 'Modelos (GLB)',
            'menu_title' => 'Modelos (GLB)',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.1',
        ],
        'materiales' => [
            'page_title' => 'Materiales',
            'menu_title' => 'Materiales',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.2',
        ],
        'colores' => [
            'page_title' => 'Colores',
            'menu_title' => 'Colores',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.3',
        ],
        'texturas' => [
            'page_title' => 'Texturas',
            'menu_title' => 'Texturas',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.4',
        ],
        'acabados' => [
            'page_title' => 'Acabados',
            'menu_title' => 'Acabados',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.5',
        ],
        'reglas' => [
            'page_title' => 'Reglas',
            'menu_title' => 'Reglas',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.6',
        ],
        'i18n' => [
            'page_title' => 'i18n',
            'menu_title' => 'i18n',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.7',
        ],
        'previsualizacion' => [
            'page_title' => 'Previsualización',
            'menu_title' => 'Previsualización',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.8',
        ],
        'publicacion' => [
            'page_title' => 'Publicación',
            'menu_title' => 'Publicación',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.9',
        ],
        'versiones-auditoria' => [
            'page_title' => 'Versiones & Auditoría',
            'menu_title' => 'Versiones & Auditoría',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.10',
        ],
        'configuracion' => [
            'page_title' => 'Configuración',
            'menu_title' => 'Configuración',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.11',
        ],
        'slots' => [
            'page_title' => 'Slots (mapeo editorial)',
            'menu_title' => 'Slots',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §3.12',
        ],
        'parejas-patillas' => [
            'page_title' => 'Parejas de Patillas',
            'menu_title' => 'Parejas L/R',
            'capability' => 'manage_options',
            'doc' => 'Capa5 §Addenda 2025-09-27',
        ],
    ];

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu'], 20);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('G3D Admin & Ops', 'g3d-admin-ops'),
            __('G3D Admin & Ops', 'g3d-admin-ops'),
            'manage_options',
            self::ROOT_SLUG,
            [$this, 'renderRoot']
        );

        foreach (self::SECTIONS as $slug => $section) {
            add_submenu_page(
                self::ROOT_SLUG,
                $section['page_title'],
                $section['menu_title'],
                $section['capability'],
                self::ROOT_SLUG . '-' . $slug,
                function () use ($section): void {
                    $this->renderPlaceholder($section['page_title'], $section['doc']);
                }
            );
        }
    }

    public function renderRoot(): void
    {
        $this->renderPlaceholder('G3D Admin & Ops', 'Plugin5 §5');
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
