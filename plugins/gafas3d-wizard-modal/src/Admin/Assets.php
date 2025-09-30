<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Admin;

use function add_action;
use function file_exists;
use function filemtime;
use function plugin_dir_path;
use function plugins_url;
use function wp_enqueue_script;
use function wp_enqueue_style;

final class Assets
{
    private string $pluginFile;

    public function __construct(string $pluginFile)
    {
        $this->pluginFile = $pluginFile;
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue'], 10, 1);
    }

    public function enqueue(string $hook): void
    {
        if ($hook !== 'toplevel_page_' . Page::MENU_SLUG) {
            return;
        }

        $pluginDir = plugin_dir_path($this->pluginFile);

        $jsPath = $pluginDir . 'assets/js/modal.js';
        $cssPath = $pluginDir . 'assets/css/modal.css';

        $jsVersion = $this->resolveVersion($jsPath);
        $cssVersion = $this->resolveVersion($cssPath);

        wp_enqueue_script(
            'g3d-wizard-modal-js',
            plugins_url('assets/js/modal.js', $this->pluginFile),
            [],
            $jsVersion,
            true
        );

        wp_enqueue_style(
            'g3d-wizard-modal-css',
            plugins_url('assets/css/modal.css', $this->pluginFile),
            [],
            $cssVersion
        );
    }

    private function resolveVersion(string $path): string
    {
        if (file_exists($path)) {
            $timestamp = filemtime($path);

            if ($timestamp !== false) {
                return (string) $timestamp;
            }
        }

        return '1.0.0';
    }
}
