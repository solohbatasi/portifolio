<script setup>
import {
  ArrowDownRight,
  ArrowRight,
  Braces,
  Database,
  GitBranch,
  Layers3,
  ServerCog,
} from 'lucide-vue-next'
import AppButton from '../common/AppButton.vue'
import AppContainer from '../common/AppContainer.vue'
import { profile } from '../../data/profile'
import { resolvePublicAsset } from '../../utils/assets'

const architectureNodes = [
  { label: 'Architecture', icon: Layers3 },
  { label: 'APIs', icon: Braces },
  { label: 'Payments', icon: GitBranch },
  { label: 'Data', icon: Database },
  { label: 'Deployment', icon: ServerCog },
]
</script>

<template>
  <section
    class="hero"
    aria-labelledby="hero-title"
  >
    <AppContainer class="hero__layout">
      <div
        class="hero__copy"
        data-reveal
      >
        <p class="hero__identity">
          {{ profile.name }}
        </p>
        <p class="hero__role">
          {{ profile.primaryTitle }}
        </p>
        <h1
          id="hero-title"
          class="hero__title"
        >
          {{ profile.heroHeadline }}
        </h1>
        <p class="hero__lead">
          {{ profile.heroSupportingText }}
        </p>

        <div class="hero__actions">
          <AppButton to="/#work">
            Explore Selected Work
            <ArrowRight
              :size="17"
              aria-hidden="true"
            />
          </AppButton>
          <AppButton
            :href="profile.github.primary.url"
            variant="secondary"
            target="_blank"
            rel="noreferrer"
          >
            View GitHub
          </AppButton>
          <AppButton
            v-if="profile.contact.cvPath"
            :href="resolvePublicAsset(profile.contact.cvPath)"
            variant="ghost"
          >
            View CV
          </AppButton>
        </div>

        <ul
          class="credibility-list"
          aria-label="Engineering focus"
        >
          <li
            v-for="item in profile.credibility"
            :key="item"
          >
            <span aria-hidden="true" />{{ item }}
          </li>
        </ul>
      </div>

      <aside
        class="architecture-panel"
        aria-label="Engineering delivery model"
        data-reveal
      >
        <div class="architecture-panel__header">
          <div>
            <span
              class="architecture-panel__signal"
              aria-hidden="true"
            />
            <span>System delivery map</span>
          </div>
          <span>Production-minded</span>
        </div>
        <div class="architecture-panel__body">
          <div class="architecture-panel__core">
            <span>Solution architecture</span>
            <strong>Reliable application platform</strong>
            <small>Secure · maintainable · operational</small>
          </div>
          <div
            class="architecture-panel__flow"
            aria-hidden="true"
          >
            <ArrowDownRight :size="20" />
          </div>
          <div class="architecture-nodes">
            <div
              v-for="node in architectureNodes"
              :key="node.label"
              class="architecture-node"
            >
              <component
                :is="node.icon"
                :size="18"
                aria-hidden="true"
              />
              <span>{{ node.label }}</span>
            </div>
          </div>
        </div>
        <div class="architecture-panel__footer">
          <span>requirements</span><i /><span>delivery</span><i /><span>support</span>
        </div>
      </aside>
    </AppContainer>
  </section>
</template>
