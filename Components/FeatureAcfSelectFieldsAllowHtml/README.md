# Feature ACF Select Fields Allow Html

Starting with ACF 6.2.7 the default render template for Select2 fields no longer allows HTML to be rendered in the admin area.

Relevant ACF changes:

- `6.2.7`: The default render template for Select2 fields no longer allows HTML to be rendered, resolving a security issue.
- `6.2.8`: ACF introduced the `select2_escape_markup` JS filter to customize Select2 HTML escaping behavior.
- `6.4.3`: ACF hardened client-side HTML escaping further by switching `acf.escHtml` to DOMPurify and tightening Select2-related HTML rendering.

Because of those changes, `select2_escape_markup` alone is not always sufficient in newer ACF versions. This add-on therefore also customizes the Select2 result and selection templates for allow-listed fields.

References:

- <https://github.com/AdvancedCustomFields/acf/commit/4bc79c87dae490e1571c7feabc269da550b4a912#diff-d419728b37776c58987e188513ded9f0a67cfacde36cf2f3a0a5031bb3244a7dL8329>
- <https://www.advancedcustomfields.com/changelog/>
- <https://www.advancedcustomfields.com/resources/javascript-api/#filters-select2_escape_markup>

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
