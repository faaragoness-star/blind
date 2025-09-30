<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Tests\Admin;

use Gafas3d\WizardModal\Admin\Assets;
use PHPUnit\Framework\TestCase;

use function do_action;

final class AssetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['g3d_tests_wp_actions']['admin_enqueue_scripts'] = [];
        $GLOBALS['g3d_wizard_modal_enqueued_scripts'] = [];
        $GLOBALS['g3d_wizard_modal_enqueued_styles'] = [];
    }

    public function testEnqueuesAssetsOnWizardPage(): void
    {
        $assets = new Assets(dirname(__DIR__, 2) . '/plugin.php');
        $assets->register();

        do_action('admin_enqueue_scripts', 'toplevel_page_g3d-wizard');

        self::assertArrayHasKey('g3d-wizard-modal-js', $GLOBALS['g3d_wizard_modal_enqueued_scripts']);
        self::assertArrayHasKey('g3d-wizard-modal-css', $GLOBALS['g3d_wizard_modal_enqueued_styles']);
    }

    public function testDoesNotEnqueueAssetsOnOtherPages(): void
    {
        $assets = new Assets(dirname(__DIR__, 2) . '/plugin.php');
        $assets->register();

        do_action('admin_enqueue_scripts', 'toplevel_page_other');

        self::assertSame([], $GLOBALS['g3d_wizard_modal_enqueued_scripts']);
        self::assertSame([], $GLOBALS['g3d_wizard_modal_enqueued_styles']);
    }
}
