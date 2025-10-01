<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Tests\Assets;

use Gafas3d\WizardModal\Assets\Assets;
use PHPUnit\Framework\TestCase;

use function do_action;

final class RulesUrlBuildTest extends TestCase
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

    public function testRulesEndpointIsLocalized(): void
    {
        Assets::register();
        do_action('wp_enqueue_scripts');

        $localized = $GLOBALS['g3d_wizard_modal_localized_scripts'][Assets::HANDLE_JS]['G3DWIZARD'] ?? [];

        self::assertArrayHasKey('api', $localized);
        self::assertArrayHasKey('rules', $localized['api']);
        self::assertNotSame('', $localized['api']['rules']);
        // TODO(test JS runtime): validar composición de querystring en entorno JS.
    }
}
