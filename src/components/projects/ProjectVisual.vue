<script setup>
import { Boxes, CircleDot, Database, PanelTop } from 'lucide-vue-next'
import { resolvePublicAsset } from '../../utils/assets'

defineProps({
  project: {
    type: Object,
    required: true,
  },
  loading: {
    type: String,
    default: 'eager',
    validator: (value) => ['eager', 'lazy'].includes(value),
  },
})

function initials(title) {
  return title
    .split(' ')
    .filter((word) => !['and', 'of', 'the'].includes(word.toLowerCase()))
    .slice(0, 3)
    .map((word) => word[0])
    .join('')
}
</script>

<template>
  <figure class="project-visual">
    <img
      v-if="project.image"
      :src="resolvePublicAsset(project.image)"
      :alt="`${project.title} project interface overview`"
      :loading="loading"
      :width="project.imageWidth || 1600"
      :height="project.imageHeight || 900"
      decoding="async"
    >
    <div
      v-else
      class="project-visual__fallback"
      role="img"
      :aria-label="`${project.title} visual`"
    >
      <div
        class="project-visual__toolbar"
        aria-hidden="true"
      >
        <span><CircleDot :size="13" /> Case study system</span>
        <span>{{ project.category }}</span>
      </div>
      <div
        class="project-visual__canvas"
        aria-hidden="true"
      >
        <div class="project-visual__identity">
          <span>{{ initials(project.title) }}</span>
          <div>
            <small>{{ project.category }}</small>
            <strong>{{ project.title }}</strong>
          </div>
        </div>
        <div class="project-visual__motif">
          <div><PanelTop :size="20" /><span>Interface</span></div>
          <i />
          <div><Boxes :size="20" /><span>Application</span></div>
          <i />
          <div><Database :size="20" /><span>Data</span></div>
        </div>
      </div>
    </div>
  </figure>
</template>
