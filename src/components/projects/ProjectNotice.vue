<script setup>
import { LockKeyhole, ShieldCheck } from 'lucide-vue-next'

defineProps({
  visibility: {
    type: String,
    required: true,
  },
  isPrivate: {
    type: Boolean,
    required: true,
  },
})
</script>

<template>
  <aside
    class="project-notice"
    :class="{ 'project-notice--private': isPrivate }"
  >
    <LockKeyhole
      v-if="isPrivate"
      :size="21"
      aria-hidden="true"
    />
    <ShieldCheck
      v-else
      :size="21"
      aria-hidden="true"
    />
    <div>
      <h2>{{ isPrivate ? 'Confidentiality and platform security' : 'Repository information' }}</h2>
      <p v-if="isPrivate">
        Source code and selected production details are not publicly available because of client
        confidentiality and platform security. This case study focuses on the problem,
        architecture and engineering contribution.
      </p>
      <p v-else>
        {{ visibility }}. Public links are shown only when a verified repository or project URL
        is available.
      </p>
    </div>
  </aside>
</template>
