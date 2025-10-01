<?php

declare(strict_types=1);

namespace Gafas3d\WizardModal\Shortcode;

use Gafas3d\WizardModal\UI\Modal;

use function add_shortcode;
use function array_change_key_case;
use function esc_attr;
use function get_locale;
use function implode;
use function ob_get_clean;
use function ob_start;
use function preg_quote;
use function preg_replace;
use function sanitize_text_field;
use function shortcode_atts;
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
        add_shortcode(self::SHORTCODE_TAG, static function (array $attrs = []): string {
            $defaults = [
                'producto_id' => '',
                'snapshot_id' => '',
                'locale' => get_locale(),
            ];

            $normalizedAttrs = array_change_key_case($attrs, CASE_LOWER);
            $parsedAttrs = shortcode_atts($defaults, $normalizedAttrs, self::SHORTCODE_TAG);

            $productoId = sanitize_text_field((string) $parsedAttrs['producto_id']);
            $snapshotId = sanitize_text_field((string) $parsedAttrs['snapshot_id']);
            $locale = sanitize_text_field((string) $parsedAttrs['locale']);

            ob_start();
            Modal::render();
            $modalHtml = (string) ob_get_clean();

            $modalHtml = self::injectDataAttributes($modalHtml, $productoId, $snapshotId, $locale);

            if (str_contains($modalHtml, 'id="' . self::ROOT_ID . '"')) {
                return $modalHtml;
            }

            $attributes = [
                sprintf('id="%s"', esc_attr(self::ROOT_ID)),
                sprintf(
                    'data-g3d-endpoint-rules="%s"',
                    esc_attr('/wp-json/g3d/v1/catalog/rules')
                ),
                sprintf(
                    'data-g3d-endpoint-validate="%s"',
                    esc_attr('/wp-json/g3d/v1/validate-sign')
                ),
                sprintf(
                    'data-g3d-endpoint-verify="%s"',
                    esc_attr('/wp-json/g3d/v1/verify')
                ),
            ];

            return sprintf(
                '<div %s>%s</div>',
                implode(' ', $attributes),
                $modalHtml
            );
        });
    }

    private static function injectDataAttributes(
        string $html,
        string $productoId,
        string $snapshotId,
        string $locale
    ): string {
        $htmlWithProducto = self::replaceAttribute($html, 'data-producto-id', $productoId);
        $htmlWithSnapshot = self::replaceAttribute($htmlWithProducto, 'data-snapshot-id', $snapshotId);

        return self::replaceAttribute($htmlWithSnapshot, 'data-locale', $locale);
    }

    private static function replaceAttribute(string $html, string $attribute, string $value): string
    {
        $pattern = sprintf('/%s="[^"]*"/', preg_quote($attribute, '/'));
        $replacement = sprintf('%s="%s"', $attribute, esc_attr($value));

        $replaced = preg_replace($pattern, $replacement, $html, 1);

        if ($replaced === null) {
            return $html;
        }

        return $replaced;
    }
}
