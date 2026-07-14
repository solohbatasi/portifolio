<script setup>
import {
  CloudCog,
  Database,
  PanelsTopLeft,
  ServerCog,
  Workflow,
} from 'lucide-vue-next'
import AppContainer from '../common/AppContainer.vue'
import SectionHeading from '../common/SectionHeading.vue'
import { profile } from '../../data/profile'
import { skillGroups } from '../../data/skills'

const icons = {
  server: ServerCog,
  panels: PanelsTopLeft,
  database: Database,
  workflow: Workflow,
  cloud: CloudCog,
}
</script>

<template>
  <section
    id="expertise"
    class="home-section home-section--surface"
    aria-labelledby="expertise-title"
  >
    <AppContainer>
      <SectionHeading
        eyebrow="Engineering capabilities"
        :title="profile.homepage.expertise.title"
        title-id="expertise-title"
        :description="profile.homepage.expertise.description"
      />
      <div class="capability-grid">
        <article
          v-for="group in skillGroups"
          :key="group.id"
          class="capability-card"
          data-reveal
        >
          <div class="capability-card__icon">
            <component
              :is="icons[group.icon]"
              :size="21"
              aria-hidden="true"
            />
          </div>
          <h3>{{ group.title }}</h3>
          <p>{{ group.description }}</p>
          <ul>
            <li
              v-for="skill in group.skills.slice(0, 4)"
              :key="skill"
            >
              {{ skill }}
            </li>
          </ul>
        </article>
      </div>
    </AppContainer>
  </section>
</template>
