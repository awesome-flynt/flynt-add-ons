<?php

/**
 * Search Rewrite Rules
 */

namespace Flynt\SearchRewriteRules;

add_filter('search_rewrite_rules', function (array $searchRewrite): array {
    global $wp_rewrite;
    $searchBase = $wp_rewrite->search_base;
    $paginationBase = $wp_rewrite->pagination_base;

    $newSearchRewriteRules = [
        // Pagination rule needs to be added first.
        "$searchBase/$paginationBase/([0-9]{1,})/?$" => 'index.php?s=&paged=$matches[1]',
        // Make sure that an empty search is handled with the search slug.
        "$searchBase/?$" => 'index.php?s=',
    ];

    foreach ($searchRewrite as $key => $value) {
        if (!isset($newSearchRewriteRules[$key])) {
            $newSearchRewriteRules[str_replace($searchBase, $searchBase, $key)] = $value;
        }
    }

    return $newSearchRewriteRules;
});

/**
 * Redirect the search page when an url parameter is set,
 * for example in forms.
 */
add_action('template_redirect', function () {
    if (!is_search() || !isset($_GET['s'])) {
        return;
    }

    $searchUrl = get_search_link();
    wp_safe_redirect($searchUrl);
});
