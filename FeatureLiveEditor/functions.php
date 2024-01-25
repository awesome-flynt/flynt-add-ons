<?php

namespace Flynt\Components\FeatureLiveEditor;

use Timber\Timber;
use WP_Admin_Bar;

add_filter('Flynt/addComponentData?name=FeatureLiveEditor', function ($data) {
    $postID = get_the_ID();

    $iFrameContentLink = get_post_permalink($postID);
    $iFrameContentLink = add_query_arg('liveEditorHideAdminBar', 'true', $iFrameContentLink);
    $iFrameContentLink = add_query_arg('liveEditorContentIframe', 'true', $iFrameContentLink);

    $iFrameEditorLink = get_edit_post_link($postID);
    $iFrameEditorLink = add_query_arg('liveEditorHideAdminBar', 'true', $iFrameEditorLink);
    $iFrameEditorLink = add_query_arg('liveEditorEditorIframe', 'true', $iFrameEditorLink);

    return array_merge($data, [
        'isLiveEditorVisible' => isLiveEditorVisible(),
        'spinnerUrl' => get_admin_url(null, 'images/spinner-2x.gif'),
        'srcContent' => $iFrameContentLink,
        'srcEditor' => $iFrameEditorLink
    ]);
});

/**
 * Maybe render component in footer
 *
 * @return void
 */
add_action('wp_footer', function () {
    if (!userCanEditPost() || has_blocks() || isLiveEditorContentIframe()) {
        return;
    }

    $context = Timber::context();
    Timber::render_string('{{ renderComponent("FeatureLiveEditor") }}', $context);
});

/**
 * Maybe hide admin bar on frontend
 *
 * @return void
 */
add_action('init', function () {
    if (isLiveEditorHideAdminBar()) {
        add_filter('show_admin_bar', '__return_false');
    }
});

/**
 * Maybe add css to the frontend
 *
 * @return void
 */
add_filter('wp_head', function () {
    if (isLiveEditorContentIframe()) {
        echo '<style id="Flynt/FeatureLiveEditor-css">a {cursor: not-allowed !important;}</style>';
    }
});

/**
 * Maybe add Live Editor class to body in admin
 *
 * @return void
 */
add_filter('admin_body_class', function (string $classes) {

    if (
        (isset($_GET["post"]) &&
            isset($_GET["action"]) &&
            $_GET["action"] == "edit" &&
            $_SERVER['HTTP_SEC_FETCH_DEST'] === 'iframe') ||
        isLiveEditorContentIframe() ||
        isLiveEditorHideAdminBar()
    ) {
        $classes .= ' featureLiveEditor-iframe-editor';
    }

    return $classes;
});

/**
 * Add Live Editor button to admin bar
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
add_action('admin_bar_menu', function (WP_Admin_Bar $wp_admin_bar) {
    $isPreview = isset($_GET['preview']) && $_GET['preview'];

    if ($isPreview || has_blocks() || !userCanEditPost() || !is_admin_bar_showing()) {
        return;
    }

    $isLiveEditorActive = isLiveEditorVisible();
    $toggleFrondEndEditorHref = get_permalink();
    $toggleFrondEndEditorHref = add_query_arg(
        'live-editor',
        $isLiveEditorActive === false ? 'true' : null,
        $toggleFrondEndEditorHref
    );

    $args = [
        'id' => 'toggle-live-editor',
        'title' => '<span class="ab-icon dashicons dashicons-welcome-view-site" aria-hidden="true"></span><span class="ab-label">' . __('Live Editor', 'flynt') . '</span>',
        'href' => $toggleFrondEndEditorHref,
        'meta' => [
            'class' => 'toggle-live-editor-button' . ($isLiveEditorActive ? ' live-editor-active' : '')
        ]
    ];

    $wp_admin_bar->add_node($args);
}, 80);

/**
 * Check if user can edit post
 *
 * @return boolean
 */
function userCanEditPost(): bool
{
    return current_user_can('edit_post', get_the_ID());
}

/**
 * Check if Live Editor is visible search parameter is set
 *
 * @return boolean
 */
function isLiveEditorVisible(): bool
{
    return isset($_GET['live-editor']) && $_GET['live-editor'] === 'true';
}

/**
 * Check if liveEditorHideAdminBar search parameter is set
 *
 * @return boolean
 */
function isLiveEditorHideAdminBar(): bool
{
    return isset($_GET['liveEditorHideAdminBar']) && $_GET['liveEditorHideAdminBar'] === 'true';
}

/**
 * Check if liveEditorContentIframe search parameter is set
 *
 * @return boolean
 */
function isLiveEditorContentIframe(): bool
{
    return isset($_GET['liveEditorContentIframe']) && $_GET['liveEditorContentIframe'] === 'true';
}
