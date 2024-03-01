<?php

namespace Flynt\FeatureAcfSelectFieldsAllowHtml;

add_action('admin_enqueue_scripts', function () {
    $selectFieldsToAllowHtml = apply_filters('Flynt/FeatureAcfSelectFieldsAllowHtml', []);
    $selectFieldsToAllowHtml = array_unique($selectFieldsToAllowHtml);
    wp_localize_script('Flynt/assets/admin', 'FeatureAcfSelectFieldsAllowHtml', $selectFieldsToAllowHtml);
});
