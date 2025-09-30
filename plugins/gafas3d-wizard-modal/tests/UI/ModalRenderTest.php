<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Tests\UI;

use Gafas3d\WizardModal\UI\Modal;
use PHPUnit\Framework\TestCase;

final class ModalRenderTest extends TestCase
{
    public function testRenderIncludesLiveRegionAndDataAttributes(): void
    {
        ob_start();
        Modal::render();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('class="g3d-wizard-modal__msg"', $output);
        self::assertStringContainsString('aria-live="polite"', $output);
        self::assertStringContainsString('data-snapshot-id=""', $output);
        self::assertStringContainsString('data-producto-id=""', $output);
        self::assertStringContainsString('data-locale="', $output);
    }
}
