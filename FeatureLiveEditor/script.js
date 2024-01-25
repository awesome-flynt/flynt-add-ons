/* global DOMParser */
import { buildRefs } from '@/assets/scripts/helpers.js'
import { disableBodyScroll, enableBodyScroll } from 'body-scroll-lock'

export default function (el) {
  const refs = buildRefs(el)
  const adminBarButtonToggleLiveEditor = document.querySelector('#wp-admin-bar-toggle-live-editor > .ab-item')

  const searchParams = new URL(document.location).searchParams
  const isLiveEditorVisible = (searchParams.get('live-editor') === 'true')
  isLiveEditorVisible && disableBodyScroll(el)

  const onAdminBarButtonToggleLiveEditorClick = (e) => {
    e.preventDefault()
    e.stopPropagation()

    toggleLiveEditor(el)
    adminBarButtonToggleLiveEditor.blur()
    adminBarButtonToggleLiveEditor.parentElement.classList.toggle('live-editor-active')
  }
  adminBarButtonToggleLiveEditor.addEventListener('click', onAdminBarButtonToggleLiveEditorClick)

  const onWindowMessage = async (event) => {
    if (event.data.action === 'pageFetched') {
      updateMainContent(refs.iFrameContent, event.data.html)
      disableHrefs(refs.iFrameContent)
    }

    if (event.data === 'postSaved') {
      try {
        const url = new URL(window.location.href)
        const res = await fetch(url)
        const html = await res.text()

        // Update iFrameContent
        updateMainContent(refs.iFrameContent, html)
        disableHrefs(refs.iFrameContent)

        // Update original page content
        updateMainContent(window.document.body, html)
      } catch (error) {
        console.error(error)
      }
    }
  }
  window.addEventListener('message', onWindowMessage)

  const onIFrameContentLoad = () => { disableHrefs(refs.iFrameContent) }
  refs.iFrameContent.addEventListener('load', onIFrameContentLoad)

  const onIFrameContentRangeChange = (e) => {
    const targetValue = parseInt(e.target.value)

    if (targetValue > 100) {
      const scale = 100 / targetValue

      refs.iFrameContentWrapper.style.setProperty('--inline-size', targetValue + '%')

      refs.iFrameContent.style.removeProperty('--inline-size')
      refs.iFrameContent.style.setProperty('--scale', scale)
    } else {
      refs.iFrameContentWrapper.style.removeProperty('--inline-size')

      refs.iFrameContent.style.removeProperty('--scale')
      refs.iFrameContent.style.setProperty('--inline-size', targetValue + '%')
    }
  }
  refs.inputContentScale.addEventListener('change', onIFrameContentRangeChange)

  const onButtonResetScaleClick = () => {
    refs.inputContentScale.value = 100
    refs.inputContentScale.dispatchEvent(new Event('change'))
  }
  refs.buttonResetScale.addEventListener('click', onButtonResetScaleClick)

  return () => {
    adminBarButtonToggleLiveEditor.removeEventListener('click', onAdminBarButtonToggleLiveEditorClick)
    window.removeEventListener('message', onWindowMessage)
    refs.iFrameContent.removeEventListener('load', onIFrameContentLoad)
    refs.inputContentScale.removeEventListener('change', onIFrameContentRangeChange)
    refs.buttonResetScale.removeEventListener('click', onButtonResetScaleClick)
  }
}

function toggleLiveEditor (el) {
  const searchParams = new URL(document.location).searchParams
  const isLiveEditorVisible = (searchParams.get('live-editor') === 'true')
  const url = new URL(window.location.href)

  el.setAttribute('aria-hidden', isLiveEditorVisible)
  if (isLiveEditorVisible) {
    enableBodyScroll(el)
    url.searchParams.delete('live-editor')
  } else {
    disableBodyScroll(el)
    url.searchParams.set('live-editor', 'true')
  }

  window.history.replaceState({}, '', url)
}

async function updateMainContent (element, html) {
  if (!element || !html) return

  const isIframe = element.tagName.toLowerCase() === 'iframe'
  isIframe && (element = element.contentDocument || element.contentWindow.document)

  try {
    const currentNodes = element.querySelector('.mainContent')
    const parser = new DOMParser()
    const futureHtml = parser.parseFromString(html, 'text/html')
    const futureNodes = futureHtml.querySelector('.mainContent')
    currentNodes.replaceChildren(...futureNodes.children)
  } catch (error) {
    console.error(error)
  }
}

async function disableHrefs (iframe) {
  if (!iframe) return

  const iframeDocument = iframe.contentDocument || iframe.contentWindow.document
  const links = iframeDocument.querySelectorAll('a')

  links.forEach(link => {
    link.href && (link.setAttribute('data-href-original', link.href))
    link.target && (link.setAttribute('data-target-original', link.target))
    link.title && (link.setAttribute('data-title-original', link.title))

    link.removeAttribute('href')
    link.removeAttribute('target')

    link.title = 'Links are disabled in Live Editor'
  })

  iframeDocument.addEventListener('click', (e) => {
    if (e.target.tagName === 'A') {
      e.preventDefault()
    }
  })
}
