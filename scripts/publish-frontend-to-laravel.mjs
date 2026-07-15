import { cp, mkdir, readFile, rm, writeFile } from 'node:fs/promises'
import { resolve } from 'node:path'

const dist = resolve('dist')
const publicDirectory = resolve('backend/public')
const copiedDirectories = ['assets', 'documents', 'images']
const copiedFiles = ['favicon.svg', 'robots.txt', 'sitemap.xml']

await mkdir(publicDirectory, { recursive: true })

for (const directory of copiedDirectories) {
  await rm(resolve(publicDirectory, directory), { recursive: true, force: true })
  await cp(resolve(dist, directory), resolve(publicDirectory, directory), { recursive: true })
}

for (const file of copiedFiles) {
  await cp(resolve(dist, file), resolve(publicDirectory, file))
}

const html = await readFile(resolve(dist, 'index.html'), 'utf8')
await writeFile(resolve(publicDirectory, 'portfolio.html'), html)

console.log('Published the Vue build into backend/public without copying payment secrets.')
