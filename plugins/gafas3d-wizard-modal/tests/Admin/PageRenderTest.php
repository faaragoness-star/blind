<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Tests\Admin;

use Gafas3d\WizardModal\Admin\Page;
use PHPUnit\Framework\TestCase;

final class PageRenderTest extends TestCase
{
    public function testRenderOutputsExpectedMarkup(): void
    {
        ob_start();
        Page::render();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('id="gafas3d-wizard-modal-root"', $output);
        self::assertStringContainsString('data-g3d-wizard-modal-open', $output);
        self::assertStringContainsString('data-g3d-wizard-modal-close', $output);
        self::assertStringContainsString('data-g3d-wizard-modal-overlay', $output);
    }
}
