<?php

/**
 * Render blocks in components.
 *
 * <!-- wp:image {"lightbox":{"enabled":false} } -->
 * <!-- /wp:image -->
 *
 */

namespace Flynt\RenderBlocksInComponents;

add_filter('Flynt/renderComponent', function (string $content): string {
    $hasBlocks = str_contains((string) $content, '<!-- wp:');

    if (!$hasBlocks) {
        return $content;
    }

    return do_blocks($content);
});
