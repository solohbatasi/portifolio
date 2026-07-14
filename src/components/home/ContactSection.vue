<script setup>
import { ArrowUpRight, Github, Linkedin, Mail, MessageCircle } from 'lucide-vue-next'
import AppContainer from '../common/AppContainer.vue'
import { profile } from '../../data/profile'

const isDevelopment = import.meta.env.DEV
</script>

<template>
  <section
    id="contact"
    class="contact-section"
    aria-labelledby="contact-title"
  >
    <AppContainer
      class="contact-panel"
      data-reveal
    >
      <div class="contact-panel__copy">
        <p>Contact</p>
        <h2 id="contact-title">
          {{ profile.homepage.contact.title }}
        </h2>
        <span>{{ profile.homepage.contact.description }}</span>
      </div>
      <div class="contact-links">
        <a
          :href="profile.github.primary.url"
          target="_blank"
          rel="noreferrer"
        >
          <Github
            :size="19"
            aria-hidden="true"
          />
          <span><small>Primary GitHub</small>{{ profile.github.primary.label }}</span>
          <ArrowUpRight
            :size="17"
            aria-hidden="true"
          />
        </a>
        <a
          :href="profile.github.legacy.url"
          target="_blank"
          rel="noreferrer"
        >
          <Github
            :size="19"
            aria-hidden="true"
          />
          <span><small>{{ profile.github.legacy.description }}</small>{{ profile.github.legacy.label }}</span>
          <ArrowUpRight
            :size="17"
            aria-hidden="true"
          />
        </a>
        <a
          v-if="profile.contact.linkedInUrl"
          :href="profile.contact.linkedInUrl"
          target="_blank"
          rel="noreferrer"
        >
          <Linkedin
            :size="19"
            aria-hidden="true"
          />
          <span><small>Professional profile</small>LinkedIn</span>
          <ArrowUpRight
            :size="17"
            aria-hidden="true"
          />
        </a>
        <a
          v-if="profile.contact.email"
          :href="`mailto:${profile.contact.email}`"
        >
          <Mail
            :size="19"
            aria-hidden="true"
          />
          <span><small>Email</small>{{ profile.contact.email }}</span>
          <ArrowUpRight
            :size="17"
            aria-hidden="true"
          />
        </a>
        <a
          v-if="profile.contact.whatsappUrl"
          :href="profile.contact.whatsappUrl"
          target="_blank"
          rel="noreferrer"
        >
          <MessageCircle
            :size="19"
            aria-hidden="true"
          />
          <span><small>Direct message</small>WhatsApp</span>
          <ArrowUpRight
            :size="17"
            aria-hidden="true"
          />
        </a>
        <div
          v-if="isDevelopment && (!profile.contact.email || !profile.contact.whatsappUrl)"
          class="contact-development-note"
        >
          Direct contact details are awaiting configuration. This note is hidden in production.
        </div>
      </div>
    </AppContainer>
  </section>
</template>
