<?php

/**
 *
 * Modifies WordPress permalinks and rewrite rules to include
 * the "Page for Posts" URI in post, category and tag URLs when a static
 * page is set as the posts page in WordPress settings.
 *
 * It ensures that all blog-related URLs are prefixed with the URI of the
 * page designated as the "Posts page" in WordPress Reading Settings.
 *
 */

namespace Flynt\PageForPostsPermalinks;

use WP_Post;
use WP_Term;

/**
 * Modify post rewrite rules to include the page for posts URI.
 */
add_filter('post_rewrite_rules', function ($postRewrite): array {
    return is_array($postRewrite) ? setRewriteRules($postRewrite) : $postRewrite;
});

/**
 * Modify post permalinks to include the page for posts URI.
 */
add_filter('pre_post_link', function (string $permalink, WP_Post $post, bool $leavename): string {
    if ($post->post_type !== 'post') {
        return $permalink;
    }
    $pageForPostsUri = getPageForPostsUri();
    return $pageForPostsUri !== null ? $pageForPostsUri . $permalink : $permalink;
}, 20, 3);

/**
 * Modify category rewrite rules to include the page for posts URI.
 */
add_filter('category_rewrite_rules', function ($categoryRewrite): array {
    return is_array($categoryRewrite) ? setRewriteRules($categoryRewrite) : $categoryRewrite;
});

/**
 * Modify tag rewrite rules to include the page for posts URI.
 */
add_filter('post_tag_rewrite_rules', function ($tagRewrite): array {
    return is_array($tagRewrite) ? setRewriteRules($tagRewrite) : $tagRewrite;
});

/**
 * Modify term links for categories and tags to include the page for posts URI.
 */
add_filter('pre_term_link', function (string $termlink, WP_Term $term): string {
    return getTermLink($termlink, $term, ['category', 'post_tag']);
}, 10, 3);

/**
 * Get the URI of the page set as "Posts page" in WordPress settings.
 *
 * @return string|null The URI of the posts page, or null if not set.
 */
function getPageForPostsUri(): ?string
{
    $pageForPosts = get_option('page_for_posts');
    return $pageForPosts ? trailingslashit(get_page_uri($pageForPosts)) : null;
}

/**
 * Modify rewrite rules to include the page for posts URI.
 *
 * @param array $rules The original rewrite rules.
 * @param string $prefix An optional prefix to add to the rules.
 * @return array The modified rewrite rules.
 */
function setRewriteRules(array $rules, string $prefix = ''): array
{
    $pageForPostsUri = getPageForPostsUri();
    if ($pageForPostsUri === null) {
        return $rules; // Return original rules if no posts page is set
    }
    $newRules = [];
    foreach ($rules as $rule => $rewrite) {
        $newRules[$pageForPostsUri . $prefix . $rule] = $rewrite;
    }
    return $newRules;
}

/**
 * Modify term links to include the page for posts URI.
 *
 * @param string $termlink The original term link.
 * @param WP_Term $term The term object.
 * @param array $allowedTaxonomies An array of taxonomy names to modify.
 * @return string The modified term link.
 */
function getTermLink(string $termlink, WP_Term $term, array $allowedTaxonomies): string
{
    $pageForPostsUri = getPageForPostsUri();
    if ($pageForPostsUri === null || !in_array($term->taxonomy, $allowedTaxonomies)) {
        return $termlink; // Return original link if no posts page is set or taxonomy is not allowed
    }
    return $pageForPostsUri . $termlink;
}
