<script setup>
import { computed } from 'vue'
import AppContainer from '../common/AppContainer.vue'
import SectionHeading from '../common/SectionHeading.vue'
import ProjectCard from '../projects/ProjectCard.vue'
import { profile } from '../../data/profile'
import { projects } from '../../data/projects'

const featuredProjects = computed(() =>
  projects
    .filter((project) => project.featured)
    .sort((a, b) => a.sortOrder - b.sortOrder)
    .slice(0, 6),
)
</script>

<template>
  <section
    id="work"
    class="home-section"
    aria-labelledby="work-title"
  >
    <AppContainer>
      <div class="section-intro-row">
        <SectionHeading
          eyebrow="Selected work"
          :title="profile.homepage.work.title"
          title-id="work-title"
          :description="profile.homepage.work.description"
        />
        <p class="section-intro-row__note">
          Selected projects · public details only
        </p>
      </div>
      <div class="project-grid">
        <ProjectCard
          v-for="(project, index) in featuredProjects"
          :key="project.id"
          :project="project"
          :index="index"
          :prominent="index < 2"
        />
      </div>
    </AppContainer>
  </section>
</template>
