<?php

/**
 * Local User Profile Picture
 *
 * Select an image from the media library and use it as the user profile picture.
 *
 * 1. Add a new field group to the user profile page with an image field to select an image from the media library.
 * 2. Add a filter to the get_avatar hook to replace the avatar with the local profile picture.
 * 3. Add a filter to the get_avatar_url hook to replace the avatar URL with the local profile picture URL.
 */

namespace Flynt\FieldGroups\LocalUserProfilePicture;

use ACFComposer\ACFComposer;

const LOCAL_PROFILE_PICTURE_FIELD_NAME = 'localProfilePicture';
const LOCAL_PROFILE_PICTURE_SIZE = 'thumbnail';

add_action('Flynt/afterRegisterComponents', function (): void {
    ACFComposer::registerFieldGroup([
        'name' => 'localUserProfilePictureFields',
        'title' => __('Local User Profile Picture Fields', 'flynt'),
        'style' => 'seamless',
        'fields' => [
            [
                'label' => __('Local Profile Picture', 'flynt'),
                'name' => LOCAL_PROFILE_PICTURE_FIELD_NAME,
                'instructions' => sprintf(
                    '%s<br>%s',
                    __('Replace the “Profile Picture” with an image from the media library.', 'flynt'),
                    __('Image-Format: JPG, PNG, SVG, WebP', 'flynt')
                ),
                'type' => 'image',
                'preview_size' => 'medium',
                'required' => 0,
                'mime_types' => 'jpg,jpeg,png,svg,webp',
            ],
            [
                'label' => __('Hide e-mail address at frontend?', 'flynt'),
                'name' => 'hideEmailAddressAtFrontend',
                'type' => 'true_false',
                'default_value' => 0,
                'ui' => 1
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'user_form',
                    'operator' => '==',
                    'value' => 'edit',
                ],
            ],
        ],
    ]);
});

/**
 * Get Avatar filter.
 * https://developer.wordpress.org/reference/hooks/get_avatar/
 *
 * @param string $avatar
 * @param mixed $idOrEmail
 * @param int $size
 *
 * @return string
 */
add_filter('get_avatar', function (string $avatar, $idOrEmail, int $size): string {
    $user = null;

    if (is_numeric($idOrEmail)) {
        $user = get_user_by('id', (int) $idOrEmail);
    } elseif (!empty($idOrEmail->user_id)) {
        $user = get_user_by('id', (int) $idOrEmail->user_id);
    }

    if (!$user) {
        return $avatar;
    }

    $imageId = get_user_meta($user->ID, LOCAL_PROFILE_PICTURE_FIELD_NAME, true);

    if (!$imageId) {
        return $avatar;
    }

    $imageSrc = wp_get_attachment_image_src($imageId, LOCAL_PROFILE_PICTURE_SIZE);
    if (!$imageSrc) {
        return $avatar;
    }

    $avatarSrc = $imageSrc[0];
    $altText = get_the_author_meta('display_name', $user->ID);
    if (!$altText) {
        $altText = ''; // default alt text
    }

    $size = absint($size); // ensure size is a positive integer
    $altText = esc_attr($altText);
    $avatarSrc = esc_url($avatarSrc);

    return "<img alt='{$altText}' src='{$avatarSrc}' class='avatar avatar-{$size}' height='{$size}' width='{$size}'/>";
}, 10, 3);

/**
 * Get Avatar URL filter.
 * https://developer.wordpress.org/reference/hooks/get_avatar_url/
 *
 * @param string $url
 * @param mixed $idOrEmail
 *
 * @return string
 */
add_filter('get_avatar_url', function (string $url, $idOrEmail) {
    $user = null;

    if (is_numeric($idOrEmail)) {
        $user = get_user_by('id', (int) $idOrEmail);
    } elseif (!empty($idOrEmail->user_id)) {
        $user = get_user_by('id', (int) $idOrEmail->user_id);
    }

    if (!$user) {
        return $url;
    }

    $imageId = get_user_meta($user->ID, LOCAL_PROFILE_PICTURE_FIELD_NAME, true);

    if (!$imageId) {
        return $url;
    }

    $imageUrl = wp_get_attachment_image_src($imageId, LOCAL_PROFILE_PICTURE_SIZE);

    return $imageUrl[0];
}, 10, 2);
