import { readonly, ref } from 'vue'

const STORAGE_KEY = 'solomon-portfolio-theme'
const theme = ref('dark')
let mediaQuery
let initialized = false

function getPreferredTheme() {
  const storedTheme = localStorage.getItem(STORAGE_KEY)

  if (storedTheme === 'light' || storedTheme === 'dark') return storedTheme

  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function applyTheme(nextTheme) {
  theme.value = nextTheme
  document.documentElement.dataset.theme = nextTheme
  document.documentElement.style.colorScheme = nextTheme

  const themeColor = document.querySelector('meta[name="theme-color"]')
  themeColor?.setAttribute('content', nextTheme === 'dark' ? '#07111f' : '#f8fafc')
}

function handleSystemThemeChange(event) {
  if (!localStorage.getItem(STORAGE_KEY)) {
    applyTheme(event.matches ? 'dark' : 'light')
  }
}

export function initializeTheme() {
  if (initialized || typeof window === 'undefined') return

  applyTheme(getPreferredTheme())
  mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
  mediaQuery.addEventListener('change', handleSystemThemeChange)
  initialized = true
}

export function useTheme() {
  function setTheme(nextTheme) {
    localStorage.setItem(STORAGE_KEY, nextTheme)
    applyTheme(nextTheme)
  }

  function toggleTheme() {
    setTheme(theme.value === 'dark' ? 'light' : 'dark')
  }

  return {
    theme: readonly(theme),
    setTheme,
    toggleTheme,
  }
}
