<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Tests\UI;

use Gafas3d\WizardModal\UI\Modal;
use PHPUnit\Framework\TestCase;

final class ModalRenderTest extends TestCase
{
    public function testRenderOutputsModalStructure(): void
    {
        ob_start();
        Modal::render();
        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString('data-g3d-wizard-modal-open', $output);
        self::assertStringContainsString('data-g3d-wizard-modal-overlay', $output);
        self::assertStringContainsString('data-g3d-wizard-modal-close', $output);
        self::assertStringContainsString('data-snapshot-id=""', $output);
        self::assertStringContainsString('data-producto-id=""', $output);
        self::assertStringContainsString('data-locale="', $output);
        self::assertStringContainsString('data-actor-id=""', $output);
        self::assertStringContainsString('data-what=""', $output);
        self::assertMatchesRegularExpression(
            '/<footer[^>]*>.*class="g3d-wizard-modal__rules"/s',
            $output
        );
        self::assertStringContainsString('class="g3d-wizard-modal__msg"', $output);
        self::assertStringContainsString('data-g3d-wizard-modal-verify', $output);
    }
}
