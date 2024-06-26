<?php

/**
 * Search Rewrite Rules
 */

namespace Flynt\SearchRewriteRules;

add_filter('search_rewrite_rules', function (array $searchRewrite): array {
    // Make search results page accessible via /search/ instead of /?s=
    $searchRewrite['search/?$'] = 'index.php?s=';
    return $searchRewrite;
});
