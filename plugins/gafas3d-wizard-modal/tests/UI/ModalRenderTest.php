<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Tests\UI;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Gafas3d\WizardModal\UI\Modal;

use PHPUnit\Framework\TestCase;
use function libxml_clear_errors;
use function libxml_use_internal_errors;

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
        self::assertStringContainsString(
            'class="g3d-wizard-modal__msg" aria-live="polite"',
            $output
        );
        self::assertStringContainsString('data-g3d-wizard-modal-verify', $output);

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($output);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);

        $tablists = $xpath->query("//*[@role='tablist']");
        self::assertNotFalse($tablists);
        self::assertGreaterThan(0, $tablists->length);

        $tabs = $xpath->query("//*[@role='tab']");
        self::assertNotFalse($tabs);
        self::assertGreaterThan(0, $tabs->length);

        $panels = $xpath->query("//*[@role='tabpanel']");
        self::assertNotFalse($panels);
        self::assertGreaterThan(0, $panels->length);

        $firstTab = $tabs->item(0);
        self::assertInstanceOf(DOMElement::class, $firstTab);
        // TODO(docs/plugin-4-gafas3d-wizard-modal.md §5.2): confirmar estado inicial vs. activación JS.
        self::assertSame('false', $firstTab->getAttribute('aria-selected'));
        self::assertSame('-1', $firstTab->getAttribute('tabindex'));
    }
}
