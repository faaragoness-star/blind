<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Tests\UI;

use Gafas3d\WizardModal\UI\Modal;
use PHPUnit\Framework\TestCase;

use function get_locale;
use function sprintf;

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
        self::assertStringContainsString('data-g3d-wizard-modal-cta', $output);
        self::assertStringContainsString('data-g3d-wizard-modal-verify', $output);
    }

    public function testModalRootContainsRulesContainerAndAttributes(): void
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

        $modalQuery = '//*[@class="g3d-wizard-modal" or contains(@class,"g3d-wizard-modal ")]';
        $modalNode = $xpath->query($modalQuery)->item(0);

        self::assertInstanceOf(\DOMElement::class, $modalNode);
        self::assertTrue($modalNode->hasAttribute('data-snapshot-id'));
        self::assertSame('', $modalNode->getAttribute('data-snapshot-id'));
        self::assertTrue($modalNode->hasAttribute('data-producto-id'));
        self::assertSame('', $modalNode->getAttribute('data-producto-id'));
        self::assertTrue($modalNode->hasAttribute('data-locale'));
        self::assertSame(get_locale(), $modalNode->getAttribute('data-locale'));

        $rulesQuery = '//*[@class="g3d-wizard-modal__rules" or contains(@class,"g3d-wizard-modal__rules ")]';
        $rulesNodes = $xpath->query($rulesQuery);

        self::assertNotFalse($rulesNodes);
        self::assertSame(1, $rulesNodes->length);
        $rulesNode = $rulesNodes->item(0);
        self::assertInstanceOf(\DOMElement::class, $rulesNode);
        self::assertSame('polite', $rulesNode->getAttribute('aria-live'));
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
        self::assertSame('status', $node->getAttribute('role'));
        self::assertSame('true', $node->getAttribute('aria-atomic'));
        self::assertSame('', $node->getAttribute('aria-busy'));
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
        self::assertGreaterThanOrEqual(2, $tabs->length);

        $panels = $xpath->query('//*[@role="tabpanel"]');
        self::assertGreaterThanOrEqual(2, $panels->length);

        $panelIds = [];

        foreach ($panels as $panel) {
            if (!$panel instanceof \DOMElement) {
                continue;
            }

            $panelIds[$panel->getAttribute('id')] = true;
            self::assertNotSame('', $panel->getAttribute('id'));
            self::assertNotSame('', $panel->getAttribute('aria-labelledby'));
            self::assertTrue($panel->hasAttribute('hidden'));
        }

        foreach ($tabs as $tab) {
            if (!$tab instanceof \DOMElement) {
                continue;
            }

            $controls = $tab->getAttribute('aria-controls');

            self::assertNotSame('', $controls);
            self::assertArrayHasKey($controls, $panelIds);
            self::assertTrue($tab->hasAttribute('aria-selected'));
            self::assertTrue($tab->hasAttribute('tabindex'));
        }
    }

    public function testRenderProvidesAtLeastOneTabPanelPair(): void
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

        $tab = $xpath->query('//*[@role="tab"]')->item(0);
        self::assertInstanceOf(\DOMElement::class, $tab);

        $controls = $tab->getAttribute('aria-controls');
        self::assertNotSame('', $controls);

        $panelQuery = sprintf('//*[@role="tabpanel" and @id="%s"]', $controls);
        $panel = $xpath->query($panelQuery)->item(0);
        self::assertInstanceOf(\DOMElement::class, $panel);
        self::assertTrue($panel->hasAttribute('hidden'));
    }
}
