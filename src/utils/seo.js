import { profile } from '../data/profile'

export function getPublicSiteUrl() {
  const configuredUrl = import.meta.env.VITE_PUBLIC_SITE_URL || profile.site.publicUrl
  if (configuredUrl) return configuredUrl.replace(/\/$/, '')

  return new URL(import.meta.env.BASE_URL, window.location.origin).href.replace(/\/$/, '')
}

function setMeta(attribute, key, content) {
  if (!content) return

  let element = document.head.querySelector(`meta[${attribute}="${key}"]`)
  if (!element) {
    element = document.createElement('meta')
    element.setAttribute(attribute, key)
    element.dataset.managedSeo = 'true'
    document.head.appendChild(element)
  }
  element.setAttribute('content', content)
}

function removeMeta(attribute, key) {
  document.head.querySelector(`meta[${attribute}="${key}"]`)?.remove()
}

function setCanonical(url) {
  let element = document.head.querySelector('link[rel="canonical"]')
  if (!element) {
    element = document.createElement('link')
    element.rel = 'canonical'
    element.dataset.managedSeo = 'true'
    document.head.appendChild(element)
  }
  element.href = url
}

export function setStructuredData(id, data) {
  let element = document.head.querySelector(`script[data-structured-data="${id}"]`)
  if (!element) {
    element = document.createElement('script')
    element.type = 'application/ld+json'
    element.dataset.structuredData = id
    document.head.appendChild(element)
  }
  element.textContent = JSON.stringify(data)
}

export function setPageSeo({ title, description, path = '/', image = null, type = 'website' }) {
  const siteUrl = getPublicSiteUrl()
  const normalizedPath = path.replace(/^\/+/, '')
  const canonicalUrl = normalizedPath ? `${siteUrl}/${normalizedPath}` : `${siteUrl}/`
  const imageUrl = image
    ? /^(https?:)/i.test(image)
      ? image
      : `${siteUrl}/${image.replace(/^\/+/, '')}`
    : null

  document.title = title
  setCanonical(canonicalUrl)
  setMeta('name', 'description', description)
  setMeta('property', 'og:type', type)
  setMeta('property', 'og:title', title)
  setMeta('property', 'og:description', description)
  setMeta('property', 'og:url', canonicalUrl)
  setMeta('property', 'og:site_name', profile.name)
  setMeta('property', 'og:locale', profile.site.locale)
  setMeta('name', 'twitter:card', imageUrl ? 'summary_large_image' : 'summary')
  setMeta('name', 'twitter:title', title)
  setMeta('name', 'twitter:description', description)

  if (imageUrl) {
    setMeta('property', 'og:image', imageUrl)
    setMeta('name', 'twitter:image', imageUrl)
  } else {
    removeMeta('property', 'og:image')
    removeMeta('name', 'twitter:image')
  }
}
