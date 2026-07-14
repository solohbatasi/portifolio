import { mkdir, writeFile } from 'node:fs/promises'
import { resolve } from 'node:path'
import { loadEnv } from 'vite'
import { projects } from '../src/data/projects.js'

const outputDirectory = resolve('public')
const fileEnv = loadEnv('production', process.cwd(), '')
const rawSiteUrl = (process.env.VITE_PUBLIC_SITE_URL || fileEnv.VITE_PUBLIC_SITE_URL)?.trim()
const siteUrl = rawSiteUrl ? rawSiteUrl.replace(/\/$/, '') : null

await mkdir(outputDirectory, { recursive: true })

if (siteUrl) {
  let parsedUrl
  try {
    parsedUrl = new URL(siteUrl)
  } catch {
    throw new Error('VITE_PUBLIC_SITE_URL must be a valid absolute URL.')
  }

  if (!['http:', 'https:'].includes(parsedUrl.protocol)) {
    throw new Error('VITE_PUBLIC_SITE_URL must use http or https.')
  }

  const routes = ['/', ...projects.map((project) => `/projects/${project.slug}`)]
  const urlEntries = routes
    .map((route) => `  <url><loc>${siteUrl}${route}</loc></url>`)
    .join('\n')

  await writeFile(
    resolve(outputDirectory, 'sitemap.xml'),
    `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urlEntries}\n</urlset>\n`,
  )
  await writeFile(
    resolve(outputDirectory, 'robots.txt'),
    `User-agent: *\nAllow: /\n\nSitemap: ${siteUrl}/sitemap.xml\n`,
  )
  console.log(`Generated SEO files for ${siteUrl}.`)
} else {
  await writeFile(
    resolve(outputDirectory, 'sitemap.xml'),
    '<?xml version="1.0" encoding="UTF-8"?>\n<!-- Configure VITE_PUBLIC_SITE_URL to generate production sitemap entries. -->\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>\n',
  )
  await writeFile(
    resolve(outputDirectory, 'robots.txt'),
    'User-agent: *\nAllow: /\n\n# Configure VITE_PUBLIC_SITE_URL to publish the sitemap location.\n',
  )
  console.log('VITE_PUBLIC_SITE_URL is not configured; generated safe placeholder SEO files.')
}
