<script setup>
import { ArrowUpRight, Boxes } from 'lucide-vue-next'
import AppBadge from '../common/AppBadge.vue'
import { resolvePublicAsset } from '../../utils/assets'

defineProps({
  project: {
    type: Object,
    required: true,
  },
  prominent: {
    type: Boolean,
    default: false,
  },
  index: {
    type: Number,
    default: 0,
  },
})

function statusTone(status) {
  if (status === 'Production system') return 'teal'
  if (status.includes('development') || status.includes('readiness')) return 'amber'
  return 'sky'
}
</script>

<template>
  <article
    class="project-card"
    :class="{ 'project-card--prominent': prominent }"
    data-reveal
  >
    <div
      class="project-card__cover"
      :class="`project-card__cover--${(index % 3) + 1}`"
    >
      <img
        v-if="project.image"
        :src="resolvePublicAsset(project.image)"
        :alt="`${project.title} interface`"
        :width="project.imageWidth || 1600"
        :height="project.imageHeight || 900"
        loading="lazy"
        decoding="async"
      >
      <div
        v-else
        class="project-cover-fallback"
        aria-hidden="true"
      >
        <div class="project-cover-fallback__top">
          <Boxes :size="18" />
          <span>{{ project.category }}</span>
        </div>
        <div class="project-cover-fallback__diagram">
          <i /><span /><i /><span /><i />
        </div>
        <strong>{{ String(index + 1).padStart(2, '0') }}</strong>
      </div>
    </div>
    <div class="project-card__content">
      <div class="project-card__labels">
        <AppBadge tone="neutral">
          {{ project.category }}
        </AppBadge>
        <AppBadge :tone="statusTone(project.status)">
          {{ project.status }}
        </AppBadge>
      </div>
      <div>
        <h3>{{ project.title }}</h3>
        <p class="project-card__role">
          {{ project.role }}
        </p>
        <p>{{ project.shortSummary }}</p>
      </div>
      <ul
        class="technology-list"
        aria-label="Selected technologies"
      >
        <li
          v-for="technology in project.technologies?.slice(0, 4) || []"
          :key="technology"
        >
          {{ technology }}
        </li>
      </ul>
      <RouterLink
        :to="`/projects/${project.slug}`"
        class="project-card__link"
      >
        View Case Study
        <ArrowUpRight
          :size="16"
          aria-hidden="true"
        />
      </RouterLink>
    </div>
  </article>
</template>
