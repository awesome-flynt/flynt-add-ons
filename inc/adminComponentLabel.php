<?php

/**
 * ACF Component Label Feature
 *
 * Adds a label field to options groups and displays it in the flexible content layout title.
 * This helps administrators easily identify and organize components in the WordPress admin.
 *
 * @see https://www.advancedcustomfields.com/resources/acf-fields-flexible_content-layout_title
 *
 * @package flynt
 * @since 2.1.0
 */

namespace Flynt\Components\FeatureAdminComponentLabel;

/**
 * Defines the field configuration for the component label.
 *
 * @return array ACF field configuration with all required properties.
 */
function getLabel(): array
{
    return [
        'key' => 'field_adminComponentLabel',
        'label' => __('Component Label', 'flynt'),
        'name' => 'adminComponentLabel',
        '_name' => 'adminComponentLabel',
        'type' => 'text',
        'instructions' => __('The label will be placed before the title of the component.', 'flynt'),
        'placeholder' => __('Optional label for the component.', 'flynt'),
        'append' => __('- Component: Name', 'flynt')
    ];
}

// Only run in admin and when ACF is active
if (is_admin() && class_exists('acf')) {
    /**
     * Modifies the layout title in flexible content fields to include the component label.
     */
    add_filter('acf/fields/flexible_content/layout_title', function (string $title, array $field, array $layout): string {
        $optionsGroup = get_sub_field('options');
        $customTitle = $optionsGroup['adminComponentLabel'] ?? null;

        if (!empty($customTitle)) {
            $title = sprintf('<strong>%s</strong> - %s', $customTitle, $title);
        }

        return $title;
    }, 11, 4);

    /**
     * Adds the component label field to options groups.
     */
    add_filter('acf/load_field/name=options', function (array $field): array {
        // Return early if sub_fields doesn't exist
        if (empty($field['sub_fields']) || !is_array($field['sub_fields'])) {
            return $field;
        }

        // Check if adminComponentLabel already exists
        foreach ($field['sub_fields'] as $subField) {
            if (!empty($subField['name']) && $subField['name'] === 'adminComponentLabel') {
                return $field; // Field already exists, return unmodified
            }
        }

        // Add the label field as the first field in the options group
        $field['sub_fields'] = array_merge(
            [getLabel()],
            $field['sub_fields']
        );

        return $field;
    });
}
