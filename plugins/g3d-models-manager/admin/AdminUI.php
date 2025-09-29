<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Admin;

use G3D\ModelsManager\Service\GlbIngestionService;

use function add_menu_page;
use function esc_html;
use function esc_textarea;

final class AdminUI
{
    private GlbIngestionService $service;

    public function __construct(GlbIngestionService $service)
    {
        $this->service = $service;
    }

    public function register(): void
    {
        add_menu_page(
            'Ingesta GLB',
            'Ingesta GLB',
            'manage_options',
            'g3d-models-manager-ingesta',
            [$this, 'renderIngestionPage']
        );
        // TODO: docs/Plugin 1 — G3d Models Manager (ingesta Glb Y Binding Técnico)
        //       — Informe.md §4 — ajustar capabilities RBAC.
    }

    public function renderIngestionPage(): void
    {
        /** @var array{
         *   binding: array<string,mixed>,
         *   validation: array{
         *     missing: string[],
         *     type: array<int, array{field: string, expected: string}>,
         *     ok: bool
         *   }
         * }|null $result
         */
        $result = null;

        if (($_SERVER['REQUEST_METHOD'] ?? '') ===
