<?php

/**
 * ACFML introduces a feature that allows users to sync repeater fields across translations.
 *
 * In combination flexible content fields and when the content should vary per language and
 * it can lead to unexpected behavior, so that this file does the following:
 *
 * 1. Define a constant that sets the default value for the repeater sync checkbox.
 * 2. Remove the meta box that displays the repeater sync checkbox in the post edit screen.
 * 3. Delete the option that stores the sync status in the database.
 *
 */

namespace Flynt\AcmlDisableRepeaterSync;

/*
* Define the default value for the repeater sync checkbox.
*
* @see https://wpml.org/documentation/support/wpml-coding-api/wpml-constants/#acfml_repeater_sync_default
*/
if (!defined('ACFML_REPEATER_SYNC_DEFAULT')) {
    define('ACFML_REPEATER_SYNC_DEFAULT', false);
}

/*
* Remove the meta box that displays the repeater sync checkbox in the post edit screen.
*/
add_action('add_meta_boxes', function (): void {
    if (!class_exists('ACFML\Repeater\Sync\CheckboxUI') || !defined('ACFML\Repeater\Sync\CheckboxUI::META_BOX_ID')) {
        return;
    }

    global $pagenow;
    $metaBoxId = \ACFML\Repeater\Sync\CheckboxUI::META_BOX_ID;
    $isRepeaterDisplayOnPostEdit = isset($pagenow) && 'post.php' === $pagenow;
    $screen = get_current_screen();
    $postType = $screen->post_type ?? null;

    if (!$isRepeaterDisplayOnPostEdit || $postType === null) {
        return;
    }

    remove_meta_box($metaBoxId, $postType, 'normal');
}, 11);

/*
* Delete the option that stores the sync status.
*/
add_action('plugins_loaded', function (): void {
    if (!is_admin() || !class_exists('ACFML\Repeater\Sync\CheckboxOption') || !defined('ACFML\Repeater\Sync\CheckboxOption::SYNCHRONISE_WP_OPTION_NAME')) {
        return;
    }

    $optionName = \ACFML\Repeater\Sync\CheckboxOption::SYNCHRONISE_WP_OPTION_NAME;

    if (get_option($optionName) === false) {
        return;
    }

    delete_option($optionName);
});
