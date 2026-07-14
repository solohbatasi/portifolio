<script setup>
import AppContainer from '../common/AppContainer.vue'
import { profile } from '../../data/profile'
</script>

<template>
  <section
    class="domain-strip"
    aria-label="Industry experience"
  >
    <AppContainer class="domain-strip__inner">
      <p>Domains</p>
      <ul>
        <li
          v-for="domain in profile.domains"
          :key="domain"
        >
          {{ domain }}
        </li>
      </ul>
    </AppContainer>
  </section>
</template>
