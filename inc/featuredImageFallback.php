<?php

/**
 * Fallback Featured Image
 *
 * This snippet provides a fallback for featured images. If a post does not have a featured image set, it will use the image set in the global options.
 *
 * 1. Add a new field group to the global options page with an image field to select an image from the media library.
 * 2. Add a filter to the post_thumbnail_html hook to replace the featured image with the fallback image.
 * 3. Add a filter to the get_post_metadata hook to set the fallback image as the featured image if no featured image is set.
 *
 */

namespace Flynt\FallbackFeaturedImage;

use Flynt\Utils\Options;

Options::addGlobal('FallbackFeaturedImage', [
    [
        'label' => __('Image', 'flynt'),
        'name' => 'image',
        'type' => 'image',
        'preview_size' => 'medium',
        'instructions' => __('Image-Format: JPG, PNG, SVG, WebP', 'flynt'),
        'required' => 1,
        'mime_types' => 'jpg,jpeg,png,svg,webp'
    ],
]);

/**
 * Post thumbnail html filter.
 * https://developer.wordpress.org/reference/hooks/post_thumbnail_html/
 *
 * @param string $html
 * @param int $postId
 * @param int $postThumbnailId
 * @param string|int $size
 * @param string|array $attr
 *
 * @return string
 */
add_filter('post_thumbnail_html', function (string $html, int $postId, int $postThumbnailId, $size, array $attr) {
    $fallbackFeaturedImageId = Options::getGlobal('FallbackFeaturedImage')['image']->id;

    if ((int) $fallbackFeaturedImageId !== $postThumbnailId) {
        return $html;
    }

    if (isset($attr['class'])) {
        $attr['class'] .= ' fallback-featured-img';
    } else {
        $sizeClass = is_array($size) ? 'size-' . implode('x', $size) : $size;
        $attr = ['class' => "attachment-{$sizeClass} fallback-featured-img"];
    }

    return wp_get_attachment_image($fallbackFeaturedImageId, $size, false, $attr);
}, 5);

/**
 * Get post metadata filter.
 * https://developer.wordpress.org/reference/hooks/get_post_metadata/
 *
 * @param mixed $value
 * @param int $objectId
 * @param string $metaKey
 * @param bool $single
 * @param string $metaType
 *
 * @return mixed
 */
add_filter('get_post_metadata', function ($value, int $objectId, string $metaKey) {
    // Only affect thumbnails on the frontend, do allow ajax calls.
    if ((is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX))) {
        return $value;
    }

    // Check only empty meta_key and '_thumbnail_id'.
    if ($metaKey !== '' && $metaKey !== '0' && '_thumbnail_id' !== $metaKey) {
        return $value;
    }

    // Check if this post type supports featured images.
    if (!post_type_supports(get_post_type($objectId), 'thumbnail')) {
        return $value; // post type does not support featured images.
    }

    // Get current Cache.
    $metaCache = wp_cache_get($objectId, 'post_meta');

    /**
     * Empty objects probably need to be initiated.
     *
     * @see get_metadata() in /wp-includes/meta.php
     */
    if (!$metaCache) {
        $metaCache = update_meta_cache('post', [$objectId]);
        $metaCache = $metaCache[$objectId] ?? [];
    }

    // Is the _thumbnail_id present in cache?
    if (!empty($metaCache['_thumbnail_id'][0])) {
        return $value; // it is present, don't check anymore.
    }

    // Get the Fallback Featured Image ID.
    $fallbackFeaturedImageOptions = Options::getGlobal('FallbackFeaturedImage');
    $fallbackFeaturedImage = $fallbackFeaturedImageOptions['image'];
    if ($fallbackFeaturedImage) {
        $fallbackFeaturedImageId = $fallbackFeaturedImage->id;

        // Set the featuredImageFallback_thumbnail_id in cache.
        $metaCache['_thumbnail_id'][0] = apply_filters('featuredImageFallback_thumbnail_id', $fallbackFeaturedImageId, $objectId);
        wp_cache_set($objectId, $metaCache, 'post_meta');
    }

    return $value;
}, 5, 3);
