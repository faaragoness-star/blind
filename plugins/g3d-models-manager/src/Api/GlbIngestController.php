<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Api;

use G3D\ModelsManager\Rbac\Capabilities;
use G3D\VendorBase\Rest\Responses;
use G3D\VendorBase\Rest\Security;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class GlbIngestController
{
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

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function handle(WP_REST_Request $req)
    {
        $nonce = Security::checkOptionalNonce($req);
        if ($nonce instanceof WP_Error) {
            // TODO(doc §auth): confirmar si la validación de nonce debe bloquear la petición.
        }

        $payload = $req->get_json_params();
        if (!\is_array($payload)) {
            $payload = [];
        }

        /** @var array{
         *     binding: array<string, mixed>,
         *     validation: array{
         *         ok: bool,
         *         missing: list<string>,
         *         type: list<array{field:string, expected:string}>
         *     }
         * } $response
         */
        $response = Responses::ok([
            'binding' => [
                'file_hash'       => 'stub:sha256:deadbeef',
                'filesize_bytes'  => 123456,
                'draco_enabled'   => false,
                'bounding_box'    => [
                    'min' => [0.0, 0.0, 0.0],
                    'max' => [10.0, 5.0, 2.0],
                ],
                'piece_type'          => 'FRAME',
                'slots_detectados'    => ['Frame_Anchor', 'Socket_Cage'],
                'anchors_present'     => ['Frame_Anchor', 'Temple_L_Anchor', 'Temple_R_Anchor'],
                'props'               => [
                    'socket_w_mm' => 45,
                    'socket_h_mm' => 35,
                    'variant'     => 'rx-classic',
                ],
                'object_name'         => 'frame_MAIN',
                'object_name_pattern' => 'frame_*',
                'model_code'          => 'mdl:rx-2025',
            ],
            'validation' => [
                'ok'      => true,
                'missing' => [],
                'type'    => [],
            ],
        ]);

        return new WP_REST_Response($response, 200);
    }
}
