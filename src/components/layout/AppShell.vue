<script setup>
import { RouterView } from 'vue-router'
import AppFooter from './AppFooter.vue'
import AppHeader from './AppHeader.vue'
</script>

<template>
  <div class="site-shell">
    <a
      class="skip-link"
      href="#main-content"
    >Skip to main content</a>
    <AppHeader />
    <main
      id="main-content"
      tabindex="-1"
    >
      <RouterView />
    </main>
    <AppFooter />
  </div>
</template>
