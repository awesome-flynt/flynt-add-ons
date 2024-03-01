/* global acf, jQuery */

/**
 * If the field name or key is included in the `acfSelectFieldsToAllowHtml` array,
 * a custom template is used for the dropdown selection that allows HTML content.
 *
 * @param {Object} options - The original Select2 options.
 * @param {jQuery} $select - The jQuery object for the Select2 dropdown.
 * @param {Object} data - The data for the Select2 dropdown.
 * @param {Object} field - The field data from ACF.
 *
 * @returns {Object} The modified Select2 options.
 */
acf.addFilter('select2_args', function (options, $select, data, field) {
  const { key } = field.data
  const { name } = field.data
  if (window.FeatureAcfSelectFieldsAllowHtml.includes(key) || window.FeatureAcfSelectFieldsAllowHtml.includes(name)) {
    options.templateSelection = function (selection) {
      const $ = jQuery
      const $selection = $('<span class="acf-selection"></span>')
      $selection.html(acf.escHtml(selection.text))
      $selection.data('element', selection.element)

      return $selection
    }
  }

  return options
})
