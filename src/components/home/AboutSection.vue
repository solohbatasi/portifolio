<script setup>
import { ArrowRight } from 'lucide-vue-next'
import AppContainer from '../common/AppContainer.vue'
import SectionHeading from '../common/SectionHeading.vue'
import { profile } from '../../data/profile'
</script>

<template>
  <section
    id="about"
    class="home-section"
    aria-labelledby="about-title"
  >
    <AppContainer class="about-layout">
      <SectionHeading
        eyebrow="About"
        :title="profile.homepage.about.title"
        title-id="about-title"
      />
      <div
        class="about-copy"
        data-reveal
      >
        <p>{{ profile.professionalSummary }}</p>
        <p>{{ profile.homepage.about.supportingText }}</p>
      </div>
      <ol
        class="lifecycle"
        aria-label="Software delivery lifecycle"
        data-reveal
      >
        <li
          v-for="(stage, index) in profile.lifecycle"
          :key="stage"
        >
          <span>{{ String(index + 1).padStart(2, '0') }}</span>
          <strong>{{ stage }}</strong>
          <ArrowRight
            v-if="index < profile.lifecycle.length - 1"
            :size="15"
            aria-hidden="true"
          />
        </li>
      </ol>
    </AppContainer>
  </section>
</template>
