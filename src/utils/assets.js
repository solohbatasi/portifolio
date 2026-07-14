export function resolvePublicAsset(path) {
  if (!path) return null
  if (/^(https?:|data:|blob:)/i.test(path)) return path

  const basePath = import.meta.env.BASE_URL || '/'
  return `${basePath}${path.replace(/^\/+/, '')}`
}
