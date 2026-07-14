<script setup>
import AboutSection from '../components/home/AboutSection.vue'
import ApproachSection from '../components/home/ApproachSection.vue'
import ContactSection from '../components/home/ContactSection.vue'
import DomainStrip from '../components/home/DomainStrip.vue'
import ExpertiseSection from '../components/home/ExpertiseSection.vue'
import ExperienceSection from '../components/home/ExperienceSection.vue'
import FeaturedProjectsSection from '../components/home/FeaturedProjectsSection.vue'
import HeroSection from '../components/home/HeroSection.vue'
import StackSection from '../components/home/StackSection.vue'
import { useReveal } from '../composables/useReveal'

useReveal()
</script>

<template>
  <HeroSection />
  <DomainStrip />
  <AboutSection />
  <ExpertiseSection />
  <FeaturedProjectsSection />
  <ExperienceSection />
  <StackSection />
  <ApproachSection />
  <ContactSection />
</template>
