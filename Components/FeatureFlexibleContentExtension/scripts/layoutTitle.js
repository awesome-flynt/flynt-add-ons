/* global acf, IntersectionObserver */
if (window.acf) {
  acf.add_action('ready', () => {
    const iFrames = document.querySelectorAll('[data-layout] .flyntFeatureFlexibleContentExtensionLayoutTitle iframe')
    iFrames.forEach((iFrame) => {
      observeIframe(iFrame)
    })

    function observeIframe (iframe) {
      const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            setIframeSrc(entry.target)
            observer.unobserve(entry.target)
          }
        })
      }, { rootMargin: '200px' })
      observer.observe(iframe)
    }

    function setIframeSrc (iFrame) {
      if (iFrame?.dataset?.src) {
        iFrame.src = iFrame.dataset.src
      } else {
        console.error('iFrame or iFrame data-src attribute is missing')
      }
    }
  })
}
