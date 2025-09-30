<?php

declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../../g3d-vendor-base-helper/tests/bootstrap.php';
}

namespace G3D\ModelsManager\Tests\Api {

    use G3D\ModelsManager\Api\GlbIngestController;
    use G3D\ModelsManager\Service\GlbIngestionService;
    use PHPUnit\Framework\TestCase;
    use WP_REST_Request;
    use WP_REST_Response;

    final class GlbIngestControllerBridgeTest extends TestCase
    {
        /**
         * Stub con propiedades observables para las aserciones.
         *
         * @var object{
         *   ingestCalls:int,
         *   result: array{
         *     binding: array<string,mixed>,
         *     validation: array{
         *       ok: bool,
         *       missing: list<string>,
         *       type: list<array<string,string>>
         *     }
         *   },
         *   lastFile: array<string,mixed>|null
         * }
         */
        private $stub;

        /** @var GlbIngestionService */
        private $service;

        protected function setUp(): void
        {
            parent::setUp();
            $_FILES = [];

            // Clase anónima: implementa ingest() y expone contadores/estado para el test.
            $this->stub = new class extends GlbIngestionService {
                public int $ingestCalls = 0;

                /** @var array{binding: array<string,mixed>, validation: array{ok: bool, missing: list<string>, type: list<array<string,string>>}} */
                public array $result = [
                    'binding' => [],
                    'validation' => [
                        'ok' => true,
                        'missing' => [],
                        'type' => [],
                    ],
                ];

                /** @var array<string,mixed>|null */
                public ?array $lastFile = null;

                public function ingest(array $file): array
                {
                    $this->ingestCalls++;
                    $this->lastFile = $file;

                    return $this->result;
                }
            };

            // El controlador depende del tipo base; inyectamos el stub.
            /** @var GlbIngestionService $svc */
            $svc = $this->stub;
            $this->service = $svc;
        }

        protected function tearDown(): void
        {
            $_FILES = [];
            parent::tearDown();
        }

        public function testHandleReturns400WhenFileMissing(): void
        {
            $controller = new GlbIngestController($this->service);

            $request = new WP_REST_Request('POST', '/g3d/v1/glb-ingest');

            $response = $controller->handle($request);

            self::assertInstanceOf(WP_REST_Response::class, $response);
            self::assertSame(400, $response->get_status());

            $data = $response->get_data();
            self::assertIsArray($data);
            self::assertSame(
                [
                    'ok'         => false,
                    'code'       => 'E_MISSING_FILE',
                    'reason_key' => 'missing_file',
                    'detail'     => 'Necesario g3d_glb_file.',
                ],
                $data
            );

            self::assertSame(0, $this->stub->ingestCalls);
        }

        public function testHandleDelegatesToServiceAndReturnsResult(): void
        {
            $tmp = \tempnam(\sys_get_temp_dir(), 'glb');
            self::assertIsString($tmp);

            $bytesWritten = \file_put_contents($tmp, 'abc');
            self::assertNotFalse($bytesWritten);

            $size = \filesize($tmp);
            self::assertIsInt($size);

            $_FILES['g3d_glb_file'] = [
                'name'     => 'test.glb',
                'type'     => 'model/gltf-binary',
                'tmp_name' => $tmp,
                'error'    => 0,
                'size'     => $size,
            ];

            try {
                $expected = [
                    'binding' => ['foo' => 'bar'],
                    'validation' => [
                        'ok' => true,
                        'missing' => [],
                        'type' => [],
                    ],
                ];

                // Configurar el resultado simulado del servicio.
                $this->stub->result = $expected;

                $controller = new GlbIngestController($this->service);

                $request = new WP_REST_Request('POST', '/g3d/v1/glb-ingest');

                $response = $controller->handle($request);

                self::assertInstanceOf(WP_REST_Response::class, $response);
                self::assertSame(200, $response->get_status());

                $data = $response->get_data();
                self::assertIsArray($data);
                self::assertSame(['ok' => true] + $expected, $data);

                self::assertSame(1, $this->stub->ingestCalls);
                self::assertSame($_FILES['g3d_glb_file'], $this->stub->lastFile);
            } finally {
                unset($_FILES['g3d_glb_file']);
                @\unlink($tmp);
            }
        }
    }
}
