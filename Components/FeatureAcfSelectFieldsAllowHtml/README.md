# Feature ACF Select Fields Allow Html

Starting with ACF 6.2.7 the default render template for select2 fields no longer allows HTML to be rendered in the admin area.

See: <https://github.com/AdvancedCustomFields/acf/commit/4bc79c87dae490e1571c7feabc269da550b4a912#diff-d419728b37776c58987e188513ded9f0a67cfacde36cf2f3a0a5031bb3244a7dL8329>

To opt-in and use HTML inside the render template the following filter can be used:

```php
add_filter('Flynt/FeatureAcfSelectFieldsAllowHtml', function (array $selectFieldsToAllowHtml) {
    $selectFieldsToAllowHtml[] = 'acfSelectField'; // Name or Key of the field
    return $selectFieldsToAllowHtml;
});

# Or
add_filter('Flynt/FeatureAcfSelectFieldsAllowHtml', function (array $selectFieldsToAllowHtml) {
    return array_merge($selectFieldsToAllowHtml, [
        'acfSelectField', // Name or Key of the field
        'acfSelectField2', // Name or Key of the field
    ]);
});
```
