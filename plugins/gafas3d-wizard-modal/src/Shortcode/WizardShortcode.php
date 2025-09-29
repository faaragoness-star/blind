<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Shortcode;

use Gafas3d\WizardModal\UI\Modal;

use function add_shortcode;
use function esc_attr;
use function implode;
use function ob_get_clean;
use function ob_start;
use function sprintf;
use function str_contains;

final class WizardShortcode
{
    private const SHORTCODE_TAG = 'g3d_wizard_modal';
    private const ROOT_ID = 'gafas3d-wizard-modal-root';

    private function __construct()
    {
    }

    public static function register(): void
    {
        add_shortcode(self::SHORTCODE_TAG, static function (): string {
            ob_start();
            Modal::render();
            $modalHtml = (string) ob_get_clean();

            if (str_contains($modalHtml, 'id="' . self::ROOT_ID . '"')) {
                return $modalHtml;
            }

            $attributes = [
                sprintf('id="%s"', esc_attr(self::ROOT_ID)),
                sprintf(
                    'data-g3d-endpoint-rules="%s"',
                    esc_attr('// TODO(docs/plugin-4-gafas3d-wizard-modal.md §9)')
                ),
                sprintf('data-g3d-endpoint-validate="%s"', esc_attr('/validate-sign')),
            ];

            return sprintf(
                '<div %s>%s</div>',
                implode(' ', $attributes),
                $modalHtml
            );
        });
    }
}
