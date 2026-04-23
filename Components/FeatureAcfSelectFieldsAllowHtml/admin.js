/* global acf */

if (typeof acf !== 'undefined') {
  const shouldAllowHtml = function (field) {
    if (!field || typeof field.data !== 'function') {
      return false
    }

    const key = field.data('key')
    const name = field.data('name')

    return window.FeatureAcfSelectFieldsAllowHtml.includes(key) || window.FeatureAcfSelectFieldsAllowHtml.includes(name)
  }

  const renderMarkup = function (item) {
    if (typeof item.text === 'undefined') {
      return item.text
    }

    const wrapper = document.createElement('span')
    wrapper.className = 'acf-selection'
    wrapper.innerHTML = item.text
    return wrapper
  }

  /**
   * In newer ACF versions, allow-listed Select2 field labels may already be
   * escaped before `select2_escape_markup` runs. Render from the original
   * `item.text` for allow-listed fields so HTML labels still render.
   */
  acf.add_filter('select2_args', function (args, $select, settings, field, instance) {
    if (shouldAllowHtml(field)) {
      args.templateResult = renderMarkup
      args.templateSelection = renderMarkup
    }

    return args
  })

  acf.addAction('select2_init', function ($select, args, settings, field) {
    if (!shouldAllowHtml(field) || !$select.hasClass('select2-hidden-accessible')) {
      return
    }

    const instance = $select.data('select2')
    const options = instance?.options?.options

    if (!options) {
      return
    }

    $select.select2('destroy')
    $select.select2({
      ...options,
      templateResult: renderMarkup,
      templateSelection: renderMarkup
    })
  })
}
