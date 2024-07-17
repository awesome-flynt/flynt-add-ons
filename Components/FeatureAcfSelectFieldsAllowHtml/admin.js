/* global acf */

if (typeof acf !== 'undefined') {
  /**
   * Available since ACF 6.2.8
   * See: https://www.advancedcustomfields.com/resources/javascript-api/#filters-select2_escape_markup
   *
   * If the field name or key is included in the `acfSelectFieldsToAllowHtml` array, the value will not be escaped.
   */
  acf.add_filter('select2_escape_markup', function (escapedValue, originalValue, $select, settings, field, instance) {
    const key = field.data('key')
    const name = field.data('name')

    if (window.FeatureAcfSelectFieldsAllowHtml.includes(key) || window.FeatureAcfSelectFieldsAllowHtml.includes(name)) {
      return originalValue
    }

    return escapedValue
  })
}
