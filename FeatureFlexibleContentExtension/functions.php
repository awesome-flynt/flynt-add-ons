<?php

namespace Flynt\Components\FeatureFlexibleContentExtension;

use Flynt\ComponentManager;
use Flynt\Utils\Options;
use Timber\Timber;

const ROUTE_NAME = 'FeatureFlexibleContentExtensionComponentPreview';
add_action('admin_enqueue_scripts', function () {
    $componentManager = ComponentManager::getInstance();
    $templateDirectory = get_template_directory();
    $data = [
        'labels' => [
            'placeholder' => __('Search...', 'flynt'),
            'noResults' => __('No components found', 'flynt'),
        ],
        'templateDirectoryUri' => get_template_directory_uri(),
        'components' => array_map(function ($componentPath) use ($templateDirectory) {
            return str_replace($templateDirectory, '', $componentPath);
        }, $componentManager->getAll()),
    ];
    wp_localize_script('Flynt/assets/admin', 'FeatureFlexibleContentExtension', $data);
});

add_filter('acf/fields/flexible_content/layout_title', function ($title, $field, $layout, $i) {

    $componentName = ucfirst($layout['name']);
    $titleWithScreenshot = Timber::compile('templates/layoutTitle.twig', [
        'title' => $title,
        'componentScreenshot' => getScreenshotData($componentName)
    ]);

    if (!Options::getGlobal('FeatureFlexibleContentExtension')['componentLivePreview'] ?? false) {
        return $titleWithScreenshot;
    }

    add_filter('wp_kses_allowed_html', __NAMESPACE__ . '\maybeAddIframeToAllowedTags', PHP_INT_MAX, 2);

    $postId = get_the_ID();
    $flexibleContentFieldName = $field['_name'] ?? null;
    $componentData = getComponentData($postId, $flexibleContentFieldName, $i, $componentName);

    if (empty($componentData)) {
        return $titleWithScreenshot;
    }

    $previewUrl = home_url(ROUTE_NAME);
    $previewUrl = add_query_arg('postId', $postId, $previewUrl);
    $previewUrl = add_query_arg('componentName', $componentName, $previewUrl);
    $previewUrl = add_query_arg('flexibleContentFieldName', $flexibleContentFieldName, $previewUrl);
    $previewUrl = add_query_arg('layoutIndex', $i, $previewUrl);

    $title = Timber::compile(
        'templates/layoutTitle.twig',
        [
            'title' => $title,
            'iframe' => [
                'src' => $previewUrl,
                'title' => sprintf(
                    /* translators: %1$s: Name of the component. */
                    __('Component Preview: %1$s', 'flynt'),
                    $componentName
                )
            ],
        ]
    );

    remove_filter('wp_kses_allowed_html', __NAMESPACE__ . '\maybeAddIframeToAllowedTags');

    return $title;
}, 11, 4);

add_action('init', function () {
    // Don’t call acf/fields/flexible_content/layout_title via ajax, because it can break the iframe.
    remove_all_actions('wp_ajax_acf/fields/flexible_content/layout_title');
    remove_all_actions('wp_ajax_nopriv_acf/fields/flexible_content/layout_title');

    $routeName = ROUTE_NAME;
    add_rewrite_rule("{$routeName}/?$", "index.php?pagename={$routeName}", "top");
    add_rewrite_tag("%{$routeName}%", "([^&]+)");
});

add_filter('template_include', function ($template) {
    global $wp_query;
    $routeName = ROUTE_NAME;
    $queryVarPageName = get_query_var('pagename');

    if ($queryVarPageName === $routeName || $queryVarPageName === strtolower($routeName)) {
        if (!is_user_logged_in()) {
            return get_404_template();
        }

        // Hide admin bar.
        add_filter('show_admin_bar', '__return_false');

        // Prevent yoast overwriting the title.
        add_filter('pre_get_document_title', '__return_empty_string', 99);

        // Set custom title and keep the default separator and site name.
        add_filter('document_title_parts', function ($title) {
            $title['title'] = __('Component Preview', 'flynt');
            return $title;
        }, 99);

        $wp_query->is_404 = false;
        status_header(200);

        $componentManager = ComponentManager::getInstance();
        $componentDirPath = $componentManager->getComponentDirPath('FeatureFlexibleContentExtension');
        $componentDirPath = rtrim($componentDirPath);

        return "{$componentDirPath}/templates/componentPreview.php";
    }

    return $template;
});

Options::addGlobal('FeatureFlexibleContentExtension', [
    [
        'label' => __('Component Live Preview', 'flynt'),
        'instructions' => __('Display a live preview of components inside flexible content layouts.', 'flynt'),
        'name' => 'componentLivePreview',
        'type' => 'true_false',
        'default_value' => 0,
        'ui' => true,
    ]
]);

add_action('update_option_options_global_FeatureFlexibleContentExtension_componentLivePreview', function ($oldValue, $value) {
    if ($oldValue === $value) {
        return;
    }

    add_action('shutdown', 'flush_rewrite_rules');
}, 10, 2);

function maybeAddIframeToAllowedTags($tags, $context)
{
    if ('acf' === $context) {
        $tags['iframe'] = [
            'src' => true,
            'srcdoc' => true,
            'height' => true,
            'width' => true,
            'frameborder' => true,
            'allowfullscreen' => true,
            'class' => true,
            'sandbox' => true,
            'data-src' => true,
        ];
    }
    return $tags;
}

function getComponentData($postId, $flexibleContentFieldName, $layoutIndex, $componentName): array
{
    $fields = get_fields($postId);

    $componentData = $fields[$flexibleContentFieldName][$layoutIndex] ?? [];
    if (!isset($componentData['acf_fc_layout']) || ucfirst($componentData['acf_fc_layout']) !== $componentName) {
        return [];
    }

    return $componentData;
}

function getScreenshotData($componentName): array
{
    $componentManager = ComponentManager::getInstance();

    $placeholderData = [
        'url' => "https://via.placeholder.com/1440x768?text={$componentName}",
        'width' => 1440,
        'height' => 768,
        'alt'   => __('Placeholder', 'flynt')
    ];

    if (!$componentManager->isRegistered($componentName)) {
        return $placeholderData;
    }

    $componentDirPath = $componentManager->getComponentDirPath($componentName);
    $componentDirPath = rtrim($componentDirPath);
    $componentScreenshotPath = "{$componentDirPath}/screenshot.png";

    if (is_file($componentScreenshotPath)) {
        $themeVersion = wp_get_theme()->get('Version');
        $imageSize = getimagesize($componentScreenshotPath);

        $templateDirectoryUri = get_template_directory_uri();
        $componentPathRelative = trim(str_replace(get_template_directory(), '', $componentDirPath), '/\\');
        $componentScreenshotUrl = "{$templateDirectoryUri}/{$componentPathRelative}/screenshot.png?v={$themeVersion}";

        return [
            'url' => $componentScreenshotUrl,
            'width' => $imageSize[0],
            'height' => $imageSize[1],
            'alt'   => sprintf(
                /* translators: %1$s: Name of the component. */
                __('%1$s screenshot', 'flynt'),
                $componentName
            )
        ];
    }

    return $placeholderData;
}
