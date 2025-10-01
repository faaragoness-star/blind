<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Tests\UI;

use Gafas3d\WizardModal\UI\Modal;
use PHPUnit\Framework\TestCase;

final class ModalRulesContainerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Carga el bootstrap de pruebas sin efectos a nivel de archivo.
        require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
    }

    public function testModalContainsRulesContainerAndDataAttributes(): void
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

        $rulesNodes = $xpath->query('//*[@class="g3d-wizard-modal__rules" or contains(@class,"g3d-wizard-modal__rules ")]');
        self::assertNotFalse($rulesNodes);
        self::assertSame(1, $rulesNodes->length);

        $rulesNode = $rulesNodes->item(0);
        self::assertInstanceOf(\DOMElement::class, $rulesNode);
        self::assertSame('g3d-wizard-rules-title', $rulesNode->getAttribute('aria-labelledby'));

        $summaryNode = $xpath->query('//*[@data-g3d-rules-summary]')->item(0);
        self::assertInstanceOf(\DOMElement::class, $summaryNode);
        self::assertSame('polite', $summaryNode->getAttribute('aria-live'));

        $listNode = $xpath->query('//*[@data-g3d-rules-list]')->item(0);
        self::assertInstanceOf(\DOMElement::class, $listNode);

        $modalNodes = $xpath->query('//*[@class="g3d-wizard-modal" or contains(@class,"g3d-wizard-modal ")]');
        self::assertNotFalse($modalNodes);
        self::assertGreaterThan(0, $modalNodes->length);

        $modalNode = $modalNodes->item(0);
        self::assertInstanceOf(\DOMElement::class, $modalNode);
        self::assertTrue($modalNode->hasAttribute('data-snapshot-id'));
        self::assertSame('', $modalNode->getAttribute('data-snapshot-id'));
        self::assertTrue($modalNode->hasAttribute('data-producto-id'));
        self::assertSame('', $modalNode->getAttribute('data-producto-id'));
        self::assertTrue($modalNode->hasAttribute('data-locale'));
        self::assertNotSame('', $modalNode->getAttribute('data-locale'));
    }
}
