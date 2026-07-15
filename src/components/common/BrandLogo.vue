<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  variant: {
    type: String,
    default: 'signature',
    validator: (value) => ['signature', 'lockup'].includes(value),
  },
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large'].includes(value),
  },
  showTagline: Boolean,
  linked: Boolean,
  loading: {
    type: String,
    default: 'lazy',
    validator: (value) => ['eager', 'lazy'].includes(value),
  },
})

const accessibleLabel = 'Solomon Batasi — Full-Stack Software Engineer'
const imageFailed = ref(false)
const pngFallbackActive = ref(false)
const assetVariant = computed(() => props.showTagline ? 'lockup' : props.variant)
const assetName = computed(() => assetVariant.value === 'lockup' ? 'lock' : 'signature')
const assetBaseUrl = computed(() => `${import.meta.env.BASE_URL}brand/solomon-batasi-${assetName.value}`)
const imageSource = computed(() => `${assetBaseUrl.value}.${pngFallbackActive.value ? 'png' : 'svg'}`)
const intrinsicDimensions = computed(() => assetVariant.value === 'lockup'
  ? { width: 550, height: 285 }
  : { width: 555, height: 190 })

watch(assetVariant, () => {
  imageFailed.value = false
  pngFallbackActive.value = false
})

function handleImageError() {
  if (!pngFallbackActive.value) {
    pngFallbackActive.value = true
    return
  }

  imageFailed.value = true
}
</script>

<template>
  <component
    :is="linked ? RouterLink : 'span'"
    :to="linked ? '/' : undefined"
    class="brand-logo"
    :class="[`brand-logo--${size}`, `brand-logo--${assetVariant}`]"
    :aria-label="linked ? accessibleLabel : undefined"
  >
    <img
      v-if="!imageFailed"
      class="brand-logo__image"
      :src="imageSource"
      :width="intrinsicDimensions.width"
      :height="intrinsicDimensions.height"
      :loading="loading"
      :fetchpriority="loading === 'eager' ? 'high' : 'auto'"
      :alt="linked ? '' : accessibleLabel"
      @error="handleImageError"
    >
    <span
      v-else
      class="brand-logo__fallback"
      :aria-hidden="linked ? 'true' : undefined"
    >
      <strong>S. Batasi</strong>
      <small v-if="assetVariant === 'lockup'">Building Solutions. Creating Impact.</small>
    </span>
  </component>
</template>
