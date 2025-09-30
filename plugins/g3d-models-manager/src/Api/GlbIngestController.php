<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Api;

use G3D\ModelsManager\Rbac\Capabilities;
use G3D\ModelsManager\Service\GlbIngestionService;
use G3D\VendorBase\Rest\Responses;
use G3D\VendorBase\Rest\Security;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class GlbIngestController
{
    public function __construct(private GlbIngestionService $service)
    {
    }

    public function registerRoutes(): void
    {
        \register_rest_route(
            'g3d/v1',
            '/glb-ingest',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'handle'],
                'permission_callback' => static fn (): bool =>
                    \function_exists('current_user_can')
                        ? \current_user_can(Capabilities::CAP_RUN_VALIDATOR)
                        : true,
            ]
        );
    }

    /** @return WP_REST_Response|WP_Error */
    public function handle(WP_REST_Request $req)
    {
        $nonce = Security::checkOptionalNonce($req);
        if ($nonce instanceof WP_Error) {
            // TODO(doc §auth): si los docs exigen nonce obligatorio, devolver $nonce.
        }

        $file = $_FILES['g3d_glb_file'] ?? null;
        if (!\is_array($file) || !isset($file['tmp_name'])) {
            return new WP_REST_Response(
                Responses::error('E_MISSING_FILE', 'missing_file', 'Necesario g3d_glb_file.'),
                400
            );
        }

        /** @var array{name:string,type?:string,tmp_name:string,error:int,size:int} $file */

        $result = $this->service->ingest($file);

        return new WP_REST_Response(
            ['ok' => true] + $result,
            200
        );
    }
}
