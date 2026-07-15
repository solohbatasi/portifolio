<script setup>
import { ArrowUpRight } from 'lucide-vue-next'
import AppContainer from '../common/AppContainer.vue'
import BrandLogo from '../common/BrandLogo.vue'
import { profile } from '../../data/profile'

const currentYear = new Date().getFullYear()
</script>

<template>
  <footer class="site-footer">
    <AppContainer class="site-footer__inner">
      <div class="site-footer__brand">
        <BrandLogo
          variant="lockup"
          size="large"
          show-tagline
          linked
          loading="lazy"
        />
        <p class="footer-summary">
          {{ profile.footerSummary }}
        </p>
      </div>
      <nav
        class="footer-navigation"
        aria-label="Footer navigation"
      >
        <RouterLink to="/#about">
          About
        </RouterLink>
        <RouterLink to="/#work">
          Selected Work
        </RouterLink>
        <RouterLink to="/#experience">
          Experience
        </RouterLink>
      </nav>
      <div class="site-footer__meta">
        <a
          :href="profile.github.primary.url"
          class="footer-link"
          target="_blank"
          rel="noreferrer"
        >
          {{ profile.github.primary.label }} on GitHub
          <ArrowUpRight
            :size="15"
            aria-hidden="true"
          />
        </a>
        <p>© {{ currentYear }} {{ profile.name }}. All rights reserved.</p>
      </div>
    </AppContainer>
  </footer>
</template>
