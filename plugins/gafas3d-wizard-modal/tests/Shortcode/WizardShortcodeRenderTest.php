<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Tests\Shortcode;

use Gafas3d\WizardModal\Shortcode\WizardShortcode;
use PHPUnit\Framework\TestCase;

use function get_locale;

final class WizardShortcodeRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['g3d_wizard_modal_shortcodes'] = [];
    }

    public function testRenderWithAttributes(): void
    {
        WizardShortcode::register();

        self::assertArrayHasKey('g3d_wizard_modal', $GLOBALS['g3d_wizard_modal_shortcodes']);

        $callback = $GLOBALS['g3d_wizard_modal_shortcodes']['g3d_wizard_modal'];
        $html = $callback([
            'producto_id' => 'prod:rx',
            'snapshot_id' => 'snap:2025-10-01',
            'locale' => 'es_ES',
        ]);

        self::assertStringContainsString('data-producto-id="prod:rx"', $html);
        self::assertStringContainsString('data-snapshot-id="snap:2025-10-01"', $html);
        self::assertStringContainsString('data-locale="es_ES"', $html);
    }

    public function testRenderWithoutAttributesUsesDefaults(): void
    {
        WizardShortcode::register();

        self::assertArrayHasKey('g3d_wizard_modal', $GLOBALS['g3d_wizard_modal_shortcodes']);

        $callback = $GLOBALS['g3d_wizard_modal_shortcodes']['g3d_wizard_modal'];
        $html = $callback([]);

        self::assertStringContainsString('data-producto-id=""', $html);
        self::assertStringContainsString('data-snapshot-id=""', $html);
        self::assertStringContainsString('data-locale="' . get_locale() . '"', $html);
    }
}
