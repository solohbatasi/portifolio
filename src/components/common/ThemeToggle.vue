<script setup>
import { Moon, Sun } from 'lucide-vue-next'
import { useTheme } from '../../composables/useTheme'

const { theme, toggleTheme } = useTheme()
</script>

<template>
  <button
    type="button"
    class="icon-button"
    :aria-label="`Switch to ${theme === 'dark' ? 'light' : 'dark'} theme`"
    :title="`Switch to ${theme === 'dark' ? 'light' : 'dark'} theme`"
    @click="toggleTheme"
  >
    <Sun
      v-if="theme === 'dark'"
      :size="18"
      aria-hidden="true"
    />
    <Moon
      v-else
      :size="18"
      aria-hidden="true"
    />
  </button>
</template>
