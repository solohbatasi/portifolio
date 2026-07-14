<script setup>
defineProps({
  tone: {
    type: String,
    default: 'teal',
    validator: (value) => ['teal', 'sky', 'amber', 'neutral'].includes(value),
  },
})
</script>

<template>
  <span
    class="app-badge"
    :class="`app-badge--${tone}`"
  >
    <slot />
  </span>
</template>
