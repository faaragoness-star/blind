<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace Gafas3d\WizardModal\Tests\UI {

    use Gafas3d\WizardModal\UI\Modal;
    use PHPUnit\Framework\TestCase;

    final class ModalTabsMarkupTest extends TestCase
    {
        public function testTabsExposeRolesAndLinkedPanels(): void
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

            $tabNodes = $xpath->query('//*[@role="tab"]');
            self::assertNotFalse($tabNodes);
            self::assertGreaterThan(0, $tabNodes->length);

            $panelNodes = $xpath->query('//*[@role="tabpanel"]');
            self::assertNotFalse($panelNodes);
            self::assertGreaterThan(0, $panelNodes->length);

            $panelIds = [];

            foreach ($panelNodes as $panelNode) {
                if (!$panelNode instanceof \DOMElement) {
                    continue;
                }

                $panelId = $panelNode->getAttribute('id');

                if ($panelId !== '') {
                    $panelIds[$panelId] = true;
                }
            }

            self::assertNotSame([], $panelIds);

            foreach ($tabNodes as $tabNode) {
                if (!$tabNode instanceof \DOMElement) {
                    continue;
                }

                $controls = $tabNode->getAttribute('aria-controls');
                self::assertNotSame('', $controls);
                self::assertArrayHasKey($controls, $panelIds);
            }
        }
    }
}
