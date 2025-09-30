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

        $registeredStyles = $GLOBALS['g3d_wizard_modal_registered_styles'];
        self::assertArrayHasKey(Assets::HANDLE_CSS, $registeredStyles);
        self::assertStringEndsWith('assets/css/wizard-modal.css', $registeredStyles[Assets::HANDLE_CSS]['src']);

        $localized = $GLOBALS['g3d_wizard_modal_localized_scripts'][Assets::HANDLE_JS]['G3DWIZARD'] ?? [];
        self::assertSame('/wp-json/g3d/v1/validate-sign', $localized['api']['validateSign'] ?? null);
        self::assertSame('/wp-json/g3d/v1/verify', $localized['api']['verify'] ?? null);

        self::assertArrayHasKey(Assets::HANDLE_JS, $GLOBALS['g3d_wizard_modal_enqueued_scripts']);
        self::assertArrayHasKey(Assets::HANDLE_CSS, $GLOBALS['g3d_wizard_modal_enqueued_styles']);
    }
}
