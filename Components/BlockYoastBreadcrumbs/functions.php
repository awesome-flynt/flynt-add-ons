<?php

namespace Flynt\Components\BlockYoastBreadcrumbs;

use ACFComposer\ACFComposer;
use Flynt\FieldVariables;
use DOMDocument;
use Timber\Timber;

add_filter('Flynt/addComponentData?name=BlockYoastBreadcrumbs', function (array $data): array {
    if (!isYoastBreadcrumbFunctionAvailable()) {
        return $data;
    }

    $data['breadcrumb'] = yoast_breadcrumb('<p class="breadcrumbs">', '</p>', false);

    $post = Timber::get_post();
    $data['options'] = ($post !== null && is_array($post->meta('blockYoastBreadcrumbs')))
        ? ($post->meta('blockYoastBreadcrumbs')['options'] ?? [])
        : [];

    is_front_page() && $data['options']['isBreadcrumbsDisabled'] = true;
    is_single() && $data['options']['theme'] = 'light';

    return $data;
});

/**
 * Check if the Yoast breadcrumb function is available
 */
function isYoastBreadcrumbFunctionAvailable(): bool
{
    return function_exists('yoast_breadcrumb');
}

/**
 * Add a notice if the Yoast SEO plugin is not installed and activated
 * or if the breadcrumbs are not enabled in the Yoast SEO settings.
 */
add_action('admin_notices', function (): void {
    if (!isYoastBreadcrumbFunctionAvailable()) {
        $message = esc_html__('BlockYoastBreadcrumbs component requires Yoast SEO to be installed and activated.', 'flynt');
        echo "<div class=\"notice notice-warning\"><p>{$message}</p></div>";
    }

    if (isYoastBreadcrumbFunctionAvailable() && null === yoast_breadcrumb('<p class="breadcrumbs">', '</p>', false)) {
        $message = esc_html__('BlockYoastBreadcrumbs component requires “Enable breadcrumbs for your theme” to be enabled in Yoast SEO settings.', 'flynt');
        echo "<div class=\"notice notice-warning\"><p>{$message}</p></div>";
    }
});

add_action('Flynt/afterRegisterComponents', function (): void {
    ACFComposer::registerFieldGroup([
        'name' => 'blockYoastBreadcrumbs',
        'title' => __('Block: Yoast Breadcrumbs', 'flynt'),
        'style' => 'seamless',
        'fields' => [
            [
                'label' => __('Block: Yoast Breadcrumbs', 'flynt'),
                'name' => 'blockYoastBreadcrumbs',
                'type' => 'group',
                'sub_fields' => [
                    [
                        'label' => '',
                        'name' => 'options',
                        'type' => 'group',
                        'layout' => 'row',
                        'sub_fields' => [
                            [
                                'label' => __('Disable Breadcrumbs', 'flynt'),
                                'name' => 'isBreadcrumbsDisabled',
                                'type' => 'true_false',
                                'default_value' => 0,
                                'ui' => 1,
                            ],
                            array_merge(FieldVariables\getTheme(), [
                                'conditional_logic' => [
                                    [
                                        [
                                            'fieldPath' => 'isBreadcrumbsDisabled',
                                            'operator' => '==',
                                            'value' => '0'
                                        ]
                                    ]
                                ],
                            ]),
                        ]
                    ]
                ]
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_type',
                    'operator' => '!=',
                    'value' => 'front_page',
                ],
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
            ],
        ],
    ]);
});

/**
 * Remove the first span tag from the Yoast breadcrumb output
 */
add_filter('wpseo_breadcrumb_output', function (string $output): string {
    if (empty($output)) {
        return $output;
    }

    $doc = new DOMDocument();
    $doc->loadHTML(
        htmlentities($output, ENT_QUOTES, 'UTF-8'),
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    $span = $doc->getElementsByTagName('span')->item(0);

    if ($span === null) {
        return $output;
    }

    $newOutput = '';
    foreach ($span->childNodes as $child) {
        $newOutput .= $doc->saveHTML($child);
    }

    return $newOutput;
});

/**
 * Add a span tag around the separator.
 */
add_filter('wpseo_breadcrumb_separator', function (string $separator): string {
    return '<span class="separator">' . $separator . '</span>';
});
