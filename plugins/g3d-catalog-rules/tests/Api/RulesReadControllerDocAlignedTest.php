<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace G3D\CatalogRules\Tests\Api {

use G3D\CatalogRules\Api\RulesReadController;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class RulesReadControllerDocAlignedTest extends TestCase
{
    public function testSuccessfulResponseMatchesDocumentedSnapshotShape(): void
    {
        $controller = new RulesReadController();
        $request    = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');
        $request->set_param('producto_id', 'prod:base');
        $request->set_param('locale', 'es-ES');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());

        $data = $response->get_data();
        self::assertIsArray($data);

        $expectedKeys = [
            'entities',
            'id',
            'locales',
            'notes',
            'producto_id',
            'published_at',
            'published_by',
            'rules',
            'schema_version',
            'sku_policy',
            'ver',
        ];
        $actualKeys = array_keys($data);
        sort($expectedKeys);
        sort($actualKeys);
        self::assertSame($expectedKeys, $actualKeys);

        self::assertSame('prod:base', $data['producto_id']);
        self::assertArrayNotHasKey('ok', $data);

        self::assertIsArray($data['entities']);
        self::assertArrayHasKey('piezas', $data['entities']);
        self::assertArrayHasKey('modelos', $data['entities']);
        self::assertArrayHasKey('materiales', $data['entities']);
        self::assertArrayHasKey('colores', $data['entities']);
        self::assertArrayHasKey('texturas', $data['entities']);
        self::assertArrayHasKey('acabados', $data['entities']);

        self::assertIsArray($data['rules']);
        $rulesKeys = array_keys($data['rules']);
        sort($rulesKeys);
        self::assertSame([
            'defaults',
            'encaje',
            'material_to_colores',
            'material_to_modelos',
            'material_to_texturas',
            'morph_rules',
            'slot_mapping_editorial',
        ], $rulesKeys);

        self::assertIsArray($data['locales']);
        self::assertContains('es-ES', $data['locales']);
    }

    public function testMissingProductoIdReturnsRestError(): void
    {
        $controller = new RulesReadController();
        $request    = new WP_REST_Request('GET', '/g3d/v1/catalog/rules');

        $response = $controller->handle($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('rest_missing_required_params', $response->get_error_code());

        $data = $response->get_error_data();
        self::assertIsArray($data);
        self::assertSame(400, $data['status']);
        self::assertSame(['producto_id'], $data['missing_params']);
    }
}

}
