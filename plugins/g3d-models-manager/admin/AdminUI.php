<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Admin;

use G3D\ModelsManager\Service\GlbIngestionService;

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
        $result = null;

        if (
            isset($_SERVER['REQUEST_METHOD'])
            && $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_FILES['g3d_glb_file'])
        ) {
            // Nota: confiar en GlbIngestionService para la validación real.
            $result = $this->service->ingest($_FILES['g3d_glb_file']);
        }

        // Estructura esperada (según typing visto por PHPStan):
        // array{
        //   binding: array<string,mixed>,
        //   validation: array{
        //     missing: array<string>,
        //     type: array<int, array{field:string, expected:string}>,
        //     ok: bool
        //   }
        // }|null
        $binding = is_array($result) ? ($result['binding'] ?? []) : [];

        // Construimos una lista de strings de error a partir de 'validation'.
        $errors = [];
        $validation = is_array($result) ? ($result['validation'] ?? null) : null;

        if (is_array($validation)) {
            $missing = $validation['missing'] ?? [];
            if (is_array($missing)) {
                foreach ($missing as $field) {
                    $errors[] = 'E_MISSING: ' . (string) $field;
                }
            }

            $typeErrors = $validation['type'] ?? [];
            if (is_array($typeErrors)) {
                foreach ($typeErrors as $tErr) {
                    $field = isset($tErr['field']) ? (string) $tErr['field'] : '?';
                    $expected = isset($tErr['expected']) ? (string) $tErr['expected'] : '?';
                    $errors[] = sprintf('E_TYPE: %s expected %s', $field, $expected);
                }
            }
        }

        $slotsValue = '';
        if (!empty($binding['slots_detectados']) && is_array($binding['slots_detectados'])) {
            $slotsValue = implode("\n", array_map('strval', $binding['slots_detectados']));
        }

        $anchorsValue = '';
        if (!empty($binding['anchors_present']) && is_array($binding['anchors_present'])) {
            $anchorsValue = implode("\n", array_map('strval', $binding['anchors_present']));
        }

        $propsValue = '';
        if (!empty($binding['props']) && is_array($binding['props'])) {
            $lines = [];
            foreach ($binding['props'] as $key => $value) {
                $lines[] = sprintf('%s=%s', (string) $key, (string) $value);
            }
            $propsValue = implode("\n", $lines);
        }

        $objectValue = '';
        $hasObjectBinding = (
            !empty($binding['object_name'])
            || !empty($binding['object_name_pattern'])
            || !empty($binding['model_code'])
        );
        if ($hasObjectBinding) {
            $objectValue = (string) ($binding['object_name'] ?? '');
            if (!empty($binding['object_name_pattern'])) {
                $objectValue .= $objectValue !== '' ? "\n" : '';
                $objectValue .= (string) $binding['object_name_pattern'];
            }
            if (!empty($binding['model_code'])) {
                $objectValue .= $objectValue !== '' ? "\n" : '';
                $objectValue .= (string) $binding['model_code'];
            }
        }

        $hash = isset($binding['file_hash']) ? (string) $binding['file_hash'] : '';
        $size = isset($binding['filesize_bytes']) ? (string) $binding['filesize_bytes'] : '';
        $draco = isset($binding['draco_enabled']) ? (string) $binding['draco_enabled'] : '';
        $bounding = isset($binding['bounding_box'])
            ? json_encode($binding['bounding_box'], JSON_PRETTY_PRINT)
            : '';

        ?>
        <div class="wrap">
            <h1>Ingesta GLB</h1>

            <?php if (!empty($errors)) : ?>
                <div class="notice notice-error">
                    <p><strong>Errores</strong></p>
                    <ul>
                        <?php foreach ($errors as $error) : ?>
                            <li><?php echo esc_html((string) $error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <p>
                    <label for="g3d_glb_file">Subir archivo (drag&amp;drop + checksum)</label><br>
                    <input type="file" name="g3d_glb_file" id="g3d_glb_file" accept=".glb">
                </p>

                <p>Metadatos: tamaño, hash, compresión (Draco), bounding boxes.</p>

                <p>
                    <label for="g3d_slots_detectados">
                        Slots detectados (lista de nombres tal cual vienen del GLB).
                    </label><br>
                    <textarea
                        id="g3d_slots_detectados"
                        name="g3d_slots_detectados"
                        rows="4"
                        readonly
                    ><?php echo esc_textarea($slotsValue); ?></textarea>
                </p>

                <p>
                    <label for="g3d_anchors">
                        Anchors obligatorios: Frame_Anchor, Temple_L_Anchor, Temple_R_Anchor,
                        Socket_Cage (si aplica).
                    </label><br>
                    <textarea
                        id="g3d_anchors"
                        name="g3d_anchors"
                        rows="4"
                        readonly
                    ><?php echo esc_textarea($anchorsValue); ?></textarea>
                </p>

                <p>
                    <label for="g3d_props">
                        Props leídas del objeto: socket_*_mm, lug_*_mm, side, variant, mount_type, tolerancias.
                    </label><br>
                    <textarea
                        id="g3d_props"
                        name="g3d_props"
                        rows="4"
                        readonly
                    ><?php echo esc_textarea($propsValue); ?></textarea>
                </p>

                <p>
                    <label for="g3d_object">
                        Object name / pattern y model_code.
                    </label><br>
                    <textarea
                        id="g3d_object"
                        name="g3d_object"
                        rows="3"
                        readonly
                    ><?php echo esc_textarea($objectValue); ?></textarea>
                </p>

                <fieldset>
                    <legend>Metadatos actuales</legend>
                    <p><strong>filesize_bytes</strong>: <?php echo esc_html($size); ?></p>
                    <p><strong>file_hash</strong>: <?php echo esc_html($hash); ?></p>
                    <p><strong>draco_enabled</strong>: <?php echo esc_html($draco); ?></p>
                    <p>
                        <strong>bounding_box</strong>:
                        <pre><?php echo esc_html($bounding); ?></pre>
                    </p>
                </fieldset>

                <p class="submit">
                    <button type="submit" class="button button-primary">Ingesta GLB</button>
                </p>
            </form>
        </div>
        <?php
    }
}
