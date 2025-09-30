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
        self::assertStringContainsString('class="g3d-wizard-modal__rules"', $output);
    }

    public function testRenderContainsSinglePoliteMessageRegion(): void
    {
        ob_start();
        Modal::render();
        $output = (string) ob_get_clean();

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $output);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query('//*[@class="g3d-wizard-modal__msg"]');

        self::assertSame(1, $nodes->length);

        $node = $nodes->item(0);
        self::assertInstanceOf(\DOMElement::class, $node);
        self::assertSame('polite', $node->getAttribute('aria-live'));
    }

    public function testRenderOutputsTablistTabsAndPanels(): void
    {
        ob_start();
        Modal::render();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('role="tablist"', $output);

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $output);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($document);

        $tablists = $xpath->query('//*[@role="tablist"]');
        self::assertGreaterThan(0, $tablists->length);

        $tabs = $xpath->query('//*[@role="tab"]');
        self::assertGreaterThan(0, $tabs->length);

        $panels = $xpath->query('//*[@role="tabpanel"]');
        self::assertGreaterThan(0, $panels->length);

        $panelIds = [];

        foreach ($panels as $panel) {
            if (! $panel instanceof \DOMElement) {
                continue;
            }

            $panelIds[$panel->getAttribute('id')] = true;
        }

        foreach ($tabs as $tab) {
            if (! $tab instanceof \DOMElement) {
                continue;
            }

            $controls = $tab->getAttribute('aria-controls');

            self::assertNotSame('', $controls);
            self::assertArrayHasKey($controls, $panelIds);
        }
    }
}
