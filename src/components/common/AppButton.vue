<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  to: {
    type: [String, Object],
    default: null,
  },
  href: {
    type: String,
    default: '',
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'ghost'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
  },
})

const componentType = computed(() => {
  if (props.to) return RouterLink
  if (props.href) return 'a'
  return 'button'
})
</script>

<template>
  <component
    :is="componentType"
    :to="to || undefined"
    :href="href || undefined"
    :type="componentType === 'button' ? type : undefined"
    class="app-button"
    :class="`app-button--${variant}`"
  >
    <slot />
  </component>
</template>
