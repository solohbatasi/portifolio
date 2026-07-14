import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { initializeTheme } from './composables/useTheme'
import { profile } from './data/profile'
import { getPublicSiteUrl, setStructuredData } from './utils/seo'
import './styles/main.css'

initializeTheme()

const siteUrl = getPublicSiteUrl()
setStructuredData('person', {
  '@context': 'https://schema.org',
  '@type': 'Person',
  '@id': `${siteUrl}/#person`,
  name: profile.name,
  url: siteUrl,
  jobTitle: profile.primaryTitle,
  description: profile.professionalSummary,
  alumniOf: {
    '@type': 'CollegeOrUniversity',
    name: 'Chuka University',
  },
  sameAs: [profile.github.primary.url, profile.github.legacy.url],
})
setStructuredData('website', {
  '@context': 'https://schema.org',
  '@type': 'WebSite',
  '@id': `${siteUrl}/#website`,
  name: `${profile.name} — Software Engineering Portfolio`,
  url: siteUrl,
  description: profile.heroSupportingText,
  author: { '@id': `${siteUrl}/#person` },
})

createApp(App).use(router).mount('#app')
