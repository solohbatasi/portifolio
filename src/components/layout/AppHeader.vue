<script setup>
import { Menu, X } from 'lucide-vue-next'
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import AppButton from '../common/AppButton.vue'
import AppContainer from '../common/AppContainer.vue'
import ThemeToggle from '../common/ThemeToggle.vue'
import { profile } from '../../data/profile'

const route = useRoute()
const isMenuOpen = ref(false)
const menuButton = ref(null)
const mobilePanel = ref(null)

const navigation = [
  { label: 'Home', to: '/' },
  { label: 'About', to: '/#about' },
  { label: 'Expertise', to: '/#expertise' },
  { label: 'Work', to: '/#work' },
  { label: 'Experience', to: '/#experience' },
  { label: 'Contact', to: '/#contact' },
]

function isActive(item) {
  const hash = item.to.includes('#') ? item.to.slice(item.to.indexOf('#')) : ''
  if (!hash) return route.path === '/' && !route.hash
  return route.path === '/' && route.hash === hash
}

function closeMenu({ restoreFocus = false } = {}) {
  if (!isMenuOpen.value) return
  isMenuOpen.value = false

  if (restoreFocus) nextTick(() => menuButton.value?.focus())
}

function handleKeydown(event) {
  if (event.key === 'Escape') closeMenu({ restoreFocus: true })
}

watch(
  () => route.fullPath,
  () => closeMenu(),
)

watch(isMenuOpen, (isOpen) => {
  document.body.classList.toggle('menu-open', isOpen)

  if (isOpen) {
    document.addEventListener('keydown', handleKeydown)
    nextTick(() => mobilePanel.value?.querySelector('a[href]')?.focus())
  }
  else document.removeEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  document.body.classList.remove('menu-open')
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <header class="site-header">
    <AppContainer class="site-header__inner">
      <RouterLink
        to="/"
        class="wordmark"
        aria-label="Solomon Batasi, home"
      >
        <span
          class="wordmark__mark"
          aria-hidden="true"
        >SB</span>
        <span class="wordmark__text">
          <strong>{{ profile.name }}</strong>
          <small>{{ profile.shortTitle }}</small>
        </span>
      </RouterLink>

      <nav
        class="desktop-nav"
        aria-label="Primary navigation"
      >
        <RouterLink
          v-for="item in navigation"
          :key="item.label"
          :to="item.to"
          class="nav-link"
          :class="{ 'nav-link--active': isActive(item) }"
        >
          {{ item.label }}
        </RouterLink>
      </nav>

      <div class="site-header__actions">
        <ThemeToggle />
        <AppButton
          class="header-cta"
          to="/#work"
        >
          View Work
        </AppButton>
        <button
          ref="menuButton"
          type="button"
          class="icon-button mobile-menu-button"
          :aria-expanded="isMenuOpen"
          aria-controls="mobile-navigation"
          :aria-label="isMenuOpen ? 'Close navigation menu' : 'Open navigation menu'"
          @click="isMenuOpen = !isMenuOpen"
        >
          <X
            v-if="isMenuOpen"
            :size="20"
            aria-hidden="true"
          />
          <Menu
            v-else
            :size="20"
            aria-hidden="true"
          />
        </button>
      </div>
    </AppContainer>

    <div
      v-if="isMenuOpen"
      id="mobile-navigation"
      ref="mobilePanel"
      class="mobile-panel"
    >
      <AppContainer>
        <nav
          class="mobile-nav"
          aria-label="Mobile navigation"
        >
          <RouterLink
            v-for="item in navigation"
            :key="item.label"
            :to="item.to"
            class="mobile-nav__link"
            :class="{ 'mobile-nav__link--active': isActive(item) }"
            @click="closeMenu()"
          >
            {{ item.label }}
          </RouterLink>
          <AppButton
            to="/#work"
            class="mobile-nav__cta"
            @click="closeMenu()"
          >
            View Work
          </AppButton>
        </nav>
      </AppContainer>
    </div>
  </header>
</template>
