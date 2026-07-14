<script setup>
import AppContainer from '../common/AppContainer.vue'
import SectionHeading from '../common/SectionHeading.vue'
import { profile } from '../../data/profile'
</script>

<template>
  <section
    class="home-section home-section--surface"
    aria-labelledby="approach-title"
  >
    <AppContainer>
      <SectionHeading
        eyebrow="Engineering approach"
        :title="profile.homepage.approach.title"
        title-id="approach-title"
        :description="profile.homepage.approach.description"
      />
      <ol class="approach-grid">
        <li
          v-for="(stage, index) in profile.engineeringApproach"
          :key="stage.id"
          data-reveal
        >
          <span>{{ String(index + 1).padStart(2, '0') }}</span>
          <h3>{{ stage.title }}</h3>
          <p>{{ stage.description }}</p>
        </li>
      </ol>
    </AppContainer>
  </section>
</template>
