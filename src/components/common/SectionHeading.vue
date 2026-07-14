<script setup>
defineProps({
  eyebrow: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    required: true,
  },
  titleId: {
    type: String,
    default: undefined,
  },
  description: {
    type: String,
    default: '',
  },
  align: {
    type: String,
    default: 'left',
    validator: (value) => ['left', 'center'].includes(value),
  },
})
</script>

<template>
  <header
    class="section-heading"
    :class="{ 'section-heading--center': align === 'center' }"
  >
    <p
      v-if="eyebrow"
      class="section-heading__eyebrow"
    >
      {{ eyebrow }}
    </p>
    <h2
      :id="titleId"
      class="section-heading__title"
    >
      {{ title }}
    </h2>
    <p
      v-if="description"
      class="section-heading__description"
    >
      {{ description }}
    </p>
  </header>
</template>
