<script setup>
import { RouterView } from 'vue-router'
import AppFooter from './AppFooter.vue'
import AppHeader from './AppHeader.vue'
import CoffeeModal from '../support/CoffeeModal.vue'
import { useCoffeeModal } from '../../composables/useCoffeeModal'

const coffeeModal = useCoffeeModal()
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
    <CoffeeModal
      v-if="coffeeModal.isCoffeeModalOpen.value"
      :open="coffeeModal.isCoffeeModalOpen.value"
      @close="coffeeModal.closeCoffeeModal"
    />
  </div>
</template>
