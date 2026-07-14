<script setup>
import AppContainer from '../common/AppContainer.vue'
import SectionHeading from '../common/SectionHeading.vue'
import { profile } from '../../data/profile'
import { skillGroups } from '../../data/skills'
</script>

<template>
  <section
    id="stack"
    class="home-section"
    aria-labelledby="stack-title"
  >
    <AppContainer>
      <SectionHeading
        eyebrow="Technical stack"
        :title="profile.homepage.stack.title"
        title-id="stack-title"
        :description="profile.homepage.stack.description"
      />
      <div
        class="stack-groups"
        data-reveal
      >
        <div
          v-for="group in skillGroups"
          :key="group.id"
          class="stack-group"
        >
          <h3>{{ group.title }}</h3>
          <ul>
            <li
              v-for="skill in group.skills"
              :key="skill"
            >
              {{ skill }}
            </li>
          </ul>
        </div>
      </div>
    </AppContainer>
  </section>
</template>
