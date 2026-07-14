<script setup>
import {
  Braces,
  CloudCog,
  Database,
  Laptop,
  MoveRight,
  ServerCog,
  Workflow,
} from 'lucide-vue-next'

defineProps({
  nodes: {
    type: Array,
    default: () => [],
  },
})

const icons = {
  client: Laptop,
  application: ServerCog,
  database: Database,
  service: CloudCog,
  background: Workflow,
  api: Braces,
}
</script>

<template>
  <div
    v-if="nodes.length"
    class="architecture-flow"
  >
    <template
      v-for="(node, index) in nodes"
      :key="node.id"
    >
      <article class="architecture-flow__node">
        <div>
          <component
            :is="icons[node.type] || Braces"
            :size="20"
            aria-hidden="true"
          />
          <span>{{ String(index + 1).padStart(2, '0') }}</span>
        </div>
        <h3>{{ node.label }}</h3>
        <p>{{ node.detail }}</p>
      </article>
      <MoveRight
        v-if="index < nodes.length - 1"
        class="architecture-flow__arrow"
        :size="20"
        aria-hidden="true"
      />
    </template>
  </div>
  <p
    v-else
    class="architecture-flow__empty"
  >
    A public architecture overview has not been added for this case study.
  </p>
</template>
