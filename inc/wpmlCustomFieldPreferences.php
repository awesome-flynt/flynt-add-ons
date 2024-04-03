<?php

/**
 * Set WPML preferences for ACF fields registered with ACF Composer
 *
 * References:
 * https://wpml.org/documentation/getting-started-guide/translating-custom-fields/#2-decide-how-to-translate-custom-fields
 * https://wpml.org/documentation/related-projects/translate-sites-built-with-acf/recommended-custom-fields-translation-preferences-for-acf-and-wpml/
 *
 */

namespace Flynt\WpmlCustomFieldPreferences;

if (!defined('ICL_SITEPRESS_VERSION')) {
    return;
}

/**
 * WPML preference to custom field mapping
 *
 * 0 = Ignore
 * 1 = Copy
 * 2 = Translate
 * 3 = Copy Once
 *
 * @see https://wpml.org/documentation/related-projects/translate-sites-built-with-acf/setting-the-translation-preferences-for-acf-fields-created-using-php-code/
 *
 */
const WPML_CUSTOM_FIELD_TYPE_PREFERENCES = [
    0 => [],
    1 => [],
    2 => [
        'wysiwyg',
        'text',
        'textarea',
        'message',
    ],
    3 => [
        'number',
        'range',
        'email',
        'url',
        'password',
        'image',
        'file',
        'oembed',
        'gallery',
        'select',
        'checkbox',
        'radio',
        'true_false',
        'button_group',
        'google_map',
        'date_picker',
        'date_time_picker',
        'time_picker',
        'color_picker',
        'accordion',
        'tab',
        'group',
        'repeater',
        'flexible_content',
        'clone',
        'link',
        'post_object',
        'page_link',
        'relationship',
        'taxonomy',
        'user',
    ],
];

/**
 * Set WPML preferences for ACF fields registered with ACF Composer.
 *
 * @param array $output The resolved entity.
 */
add_filter('ACFComposer/resolveEntity', function (array $output): array {
    if (!isset($output['type'])) {
        return $output;
    }

    foreach (WPML_CUSTOM_FIELD_TYPE_PREFERENCES as $preference => $types) {
        if (in_array($output['type'], $types)) {
            $output['wpml_cf_preferences'] = $preference;
            return $output;
        }
    }

    return $output;
});
