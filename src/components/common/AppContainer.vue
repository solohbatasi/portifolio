<script setup>
defineProps({
  as: {
    type: String,
    default: 'div',
  },
})
</script>

<template>
  <component
    :is="as"
    class="app-container"
  >
    <slot />
  </component>
</template>
