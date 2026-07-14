import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

function normalizeBasePath(value = '/') {
  const trimmedValue = value.trim()
  if (!trimmedValue || trimmedValue === '/') return '/'

  return `/${trimmedValue.replace(/^\/+|\/+$/g, '')}/`
}

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    base: normalizeBasePath(env.VITE_BASE_PATH),
    plugins: [vue(), tailwindcss()],
  }
})
