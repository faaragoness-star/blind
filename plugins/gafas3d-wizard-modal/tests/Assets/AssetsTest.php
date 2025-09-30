<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Tests\Assets;

use Gafas3d\WizardModal\Assets\Assets;
use PHPUnit\Framework\TestCase;

use function do_action;

final class AssetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['g3d_tests_wp_actions']['wp_enqueue_scripts'] = [];
        $GLOBALS['g3d_wizard_modal_registered_scripts'] = [];
        $GLOBALS['g3d_wizard_modal_registered_styles'] = [];
        $GLOBALS['g3d_wizard_modal_localized_scripts'] = [];
        $GLOBALS['g3d_wizard_modal_enqueued_scripts'] = [];
        $GLOBALS['g3d_wizard_modal_enqueued_styles'] = [];
        $GLOBALS['g3d_wizard_modal_script_translations'] = [];
    }

    public function testRegistersHooksAndEnqueuesAssets(): void
    {
        Assets::register();

        self::assertArrayHasKey('wp_enqueue_scripts', $GLOBALS['g3d_tests_wp_actions']);
        self::assertNotEmpty($GLOBALS['g3d_tests_wp_actions']['wp_enqueue_scripts']);

        do_action('wp_enqueue_scripts');

        $registeredScripts = $GLOBALS['g3d_wizard_modal_registered_scripts'];
        self::assertArrayHasKey(Assets::HANDLE_JS, $registeredScripts);
        self::assertStringEndsWith('assets/js/wizard-modal.js', $registeredScripts[Assets::HANDLE_JS]['src']);
        self::assertContains('wp-i18n', $registeredScripts[Assets::HANDLE_JS]['deps']);

        $registeredStyles = $GLOBALS['g3d_wizard_modal_registered_styles'];
        self::assertArrayHasKey(Assets::HANDLE_CSS, $registeredStyles);
        self::assertStringEndsWith('assets/css/wizard-modal.css', $registeredStyles[Assets::HANDLE_CSS]['src']);

        $localized = $GLOBALS['g3d_wizard_modal_localized_scripts'][Assets::HANDLE_JS]['G3DWIZARD'] ?? [];
        self::assertArrayHasKey('api', $localized);
        self::assertArrayHasKey('validateSign', $localized['api']);
        self::assertArrayHasKey('verify', $localized['api']);
        self::assertArrayHasKey('audit', $localized['api']);
        self::assertSame(
            'http://example.test/wp-json/g3d/v1/validate-sign',
            $localized['api']['validateSign'] ?? null
        );
        self::assertSame('http://example.test/wp-json/g3d/v1/verify', $localized['api']['verify'] ?? null);
        self::assertSame(
            'http://example.test/wp-json/g3d/v1/audit',
            $localized['api']['audit'] ?? null
        );
        self::assertArrayHasKey('rules', $localized['api']);
        self::assertIsString($localized['api']['rules']);
        self::assertStringStartsWith('http://example.test/wp-json/', $localized['api']['rules']);
        self::assertArrayHasKey('nonce', $localized);
        self::assertSame('nonce-123', $localized['nonce'] ?? null);
        self::assertArrayHasKey('locale', $localized);
        self::assertSame('es_ES', $localized['locale'] ?? null);

        self::assertArrayHasKey(Assets::HANDLE_JS, $GLOBALS['g3d_wizard_modal_enqueued_scripts']);
        self::assertArrayHasKey(Assets::HANDLE_CSS, $GLOBALS['g3d_wizard_modal_enqueued_styles']);

        $translations = $GLOBALS['g3d_wizard_modal_script_translations'];
        self::assertNotEmpty($translations);

        $translation = $translations[0];
        self::assertSame(Assets::HANDLE_JS, $translation['handle']);
        self::assertSame('gafas3d-wizard-modal', $translation['domain']);
        self::assertStringEndsWith('/languages', $translation['path']);
    }
}
