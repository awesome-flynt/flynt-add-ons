/* globals acf, jQuery, wp */
const searchParams = new URL(window.location).searchParams
const isLiveEditorEditorIframe = (searchParams.get('liveEditorEditorIframe') === 'true')
const isIframe = (window.location !== window.parent.location)
const delay = ms => new Promise(_resolve => setTimeout(_resolve, ms))

if (isIframe || isLiveEditorEditorIframe) {
  const messageSuccess = document.querySelector('#message.updated')
  messageSuccess && window.parent.postMessage('postSaved', '*')

  // Hacking the preview
  let currentFormData
  const $form = jQuery('form#post')
  $form.on('change', e => { submitForm($form) })

  // Hook into the ACF TinyMCE
  acf.add_filter('wysiwyg_tinymce_settings', (mceInit, id) => {
    const existingSetup = mceInit.setup
    mceInit.setup = (editor) => {
      if (typeof existingSetup === 'function') {
        existingSetup(editor)
      }

      // Updates the preview when using undo and redo and
      // when the form reaches the initial state again
      // (then the change does not fire).
      editor.on('keyup', async e => {
        editor.save()
        await delay(250) // Seems to be required.
        submitForm($form)
      })

      // Switch between the visual and text editor.
      editor.on('focus', e => {
        submitForm($form)
      })
    }
    return mceInit
  })

  setTargetAttributeOfLinksToBlank()

  function submitForm ($form) {
    const $ = jQuery
    const $previewField = $('input#wp-preview')
    const ua = navigator.userAgent.toLowerCase()
    if (wp.autosave) {
      wp.autosave.server.tempBlockSave()
    }

    $previewField.val('dopreview')
    const formData = new FormData($form.get(0))

    // Workaround for WebKit bug preventing a form submitting twice to the same action.
    // https://bugs.webkit.org/show_bug.cgi?id=28633
    if (ua.indexOf('safari') !== -1 && ua.indexOf('chrome') === -1) {
      $form.attr('action', function (index, value) {
        return value + '?t=' + (new Date()).getTime()
      })
    }

    $previewField.val('')

    const serializedFormData = JSON.stringify(Object.fromEntries(formData))
    if (serializedFormData === currentFormData) {
      return
    }

    currentFormData = serializedFormData
    fetch($form.attr('action'), {
      method: 'POST',
      body: formData
    }).then(response => {
      response.text().then(html => {
        window.parent.postMessage({ action: 'pageFetched', html }, '*')
      })
    })
  }

  function setTargetAttributeOfLinksToBlank () {
    const links = document.querySelectorAll('a[href]:not(#post-preview)')
    links.forEach((link) => {
      const href = link.getAttribute('href')
      if (href.startsWith('https') || href.startsWith('http')) {
        link.setAttribute('target', '_blank')
      }
    })
  }
}
