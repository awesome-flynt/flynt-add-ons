<?php

/**
 * Search Rewrite Rules
 */

namespace Flynt\SearchRewriteRules;

const SEARCH_SLUG = 'search';

add_filter('search_rewrite_rules', function (array $searchRewrite): array {
    $searchSlug = SEARCH_SLUG;

    $newSearchRewriteRules = [
        // Pagination rule needs to be added first.
        "$searchSlug/page/([0-9]{1,})/?$" => 'index.php?s=&paged=$matches[1]',
        // Make sure that an empty search is handled with the search slug.
        "$searchSlug/?$" => 'index.php?s=',
    ];

    // Replace the search slug in the rewrite rules, but keep the original search rewrite rules.
    foreach ($searchRewrite as $key => $value) {
        if (!isset($newSearchRewriteRules[$key])) {
            $newSearchRewriteRules[str_replace('search', $searchSlug, $key)] = $value;
        }
    }

    return $newSearchRewriteRules;
});
