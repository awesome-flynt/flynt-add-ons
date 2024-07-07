<?php

/**
 * Show the excerpt field for specific user roles and move it to the top of the editor.
 *
 * 1. Force that the excerpt field is visible for specific user roles.
 * 2. Move the excerpt field to the top of the editor for specific user roles.
 *
 */

namespace Flynt\PostExcerptToTop;

use WP_User;

const USER_ROLES = ['editor', 'administrator', 'author'];
const POST_TYPE = 'post';

/**
 * Force that the excerpt field is visible for specific user roles.
 */
add_action('wp_login', function (string $userLogin, WP_User $user): void {
    $metaKey = 'metaboxhidden_' . POST_TYPE;

    if (!array_intersect(USER_ROLES, $user->roles)) {
        return;
    }

    $userMeta = get_user_meta($user->ID, $metaKey, true);

    if (is_array($userMeta)) {
        $userMeta = array_diff($userMeta, ['postexcerpt']);
    } else {
        $userMeta = [
            'trackbacksdiv',
            'commentstatusdiv',
            'commentsdiv',
            'slugdiv',
            'authordiv'
        ];
    }

    update_user_meta($user->ID, $metaKey, $userMeta);
}, 10, 2);

/**
 * Move the excerpt field to the top of the editor for specific user roles.
 */
add_action('wp_login', function (string $userLogin, WP_User $user): void {
    $metaKey = 'meta-box-order_' . POST_TYPE;

    if (!array_intersect(USER_ROLES, $user->roles)) {
        return;
    }

    $userMeta = get_user_meta($user->ID, $metaKey, true) ?: [];

    foreach ($userMeta as $context => &$order) {
        $order = implode(',', array_diff(explode(',', $order), ['postexcerpt']));
    }

    if (in_array('advanced-custom-fields-pro/acf.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        if (!isset($userMeta['acf_after_title'])) {
            $userMeta['acf_after_title'] = 'postexcerpt';
            update_user_meta($user->ID, $metaKey, $userMeta);
            return;
        }

        $acfAfterTitleOrder = explode(',', $userMeta['acf_after_title'] ?? '');
        $acfAfterTitleOrder = array_diff($acfAfterTitleOrder, ['postexcerpt']);
        array_unshift($acfAfterTitleOrder, 'postexcerpt');
        $userMeta['acf_after_title'] = implode(',', $acfAfterTitleOrder);
        update_user_meta($user->ID, $metaKey, $userMeta);
        return;
    }

    $normalOrder = explode(',', $userMeta['normal'] ?? '');
    $normalOrder = array_diff($normalOrder, ['postexcerpt']);
    array_unshift($normalOrder, 'postexcerpt');
    $userMeta['normal'] = implode(',', $normalOrder);

    update_user_meta($user->ID, $metaKey, $userMeta);
}, 10, 2);
