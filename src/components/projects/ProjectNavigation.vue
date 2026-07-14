<script setup>
import { ArrowLeft, ArrowRight } from 'lucide-vue-next'

defineProps({
  previousProject: {
    type: Object,
    default: null,
  },
  nextProject: {
    type: Object,
    default: null,
  },
})
</script>

<template>
  <nav
    class="project-navigation"
    aria-label="Case study navigation"
  >
    <RouterLink
      v-if="previousProject"
      :to="`/projects/${previousProject.slug}`"
      class="project-navigation__item"
    >
      <ArrowLeft
        :size="18"
        aria-hidden="true"
      />
      <span><small>Previous project</small>{{ previousProject.title }}</span>
    </RouterLink>
    <span v-else />
    <RouterLink
      v-if="nextProject"
      :to="`/projects/${nextProject.slug}`"
      class="project-navigation__item project-navigation__item--next"
    >
      <span><small>Next project</small>{{ nextProject.title }}</span>
      <ArrowRight
        :size="18"
        aria-hidden="true"
      />
    </RouterLink>
  </nav>
</template>
