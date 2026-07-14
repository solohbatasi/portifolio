import { cp, mkdir } from 'node:fs/promises'
import { resolve } from 'node:path'

const sourceDirectory = resolve('server')
const outputDirectory = resolve('dist/api')

await mkdir(outputDirectory, { recursive: true })
await cp(resolve(sourceDirectory, 'api/index.php'), resolve(outputDirectory, 'index.php'))
await cp(resolve(sourceDirectory, 'api/.htaccess'), resolve(outputDirectory, '.htaccess'))
await cp(resolve(sourceDirectory, 'src'), resolve(outputDirectory, 'src'), { recursive: true })

console.log('Copied the secure PHP support API into dist/api.')
