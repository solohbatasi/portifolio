<script setup>
import { Award, BriefcaseBusiness, GraduationCap } from 'lucide-vue-next'
import AppContainer from '../common/AppContainer.vue'
import SectionHeading from '../common/SectionHeading.vue'
import { certifications, education, experience } from '../../data/experience'
import { profile } from '../../data/profile'
</script>

<template>
  <section
    id="experience"
    class="home-section home-section--surface"
    aria-labelledby="experience-title"
  >
    <AppContainer>
      <SectionHeading
        eyebrow="Experience and education"
        :title="profile.homepage.experience.title"
        title-id="experience-title"
      />
      <div class="experience-layout">
        <div class="experience-list">
          <article
            v-for="item in experience"
            :key="item.id"
            class="experience-item"
            data-reveal
          >
            <BriefcaseBusiness
              :size="20"
              aria-hidden="true"
            />
            <div>
              <p>
                {{ item.organisation }}
                <span v-if="item.current"> · Current</span>
                <span v-if="item.location"> · {{ item.location }}</span>
              </p>
              <h3>{{ item.title }}</h3>
              <p class="experience-item__summary">
                {{ item.summary }}
              </p>
              <ul v-if="item.responsibilities.length">
                <li
                  v-for="responsibility in item.responsibilities"
                  :key="responsibility"
                >
                  {{ responsibility }}
                </li>
              </ul>
            </div>
          </article>
        </div>
        <div
          class="credentials"
          data-reveal
        >
          <article
            v-for="item in education"
            :key="item.id"
            class="credential-card"
          >
            <GraduationCap
              :size="20"
              aria-hidden="true"
            />
            <div>
              <span>Education</span>
              <h3>{{ item.qualification }}</h3>
              <p>{{ item.institution }}</p>
              <small>{{ item.classification }} · {{ item.startYear }}–{{ item.endYear }}</small>
            </div>
          </article>
          <article
            v-for="item in certifications"
            :key="item.id"
            class="credential-card"
          >
            <Award
              :size="20"
              aria-hidden="true"
            />
            <div>
              <span>Certification</span>
              <h3>{{ item.name }}</h3>
              <p>{{ item.result }}</p>
              <small>{{ item.issuedAt }}</small>
            </div>
          </article>
        </div>
      </div>
    </AppContainer>
  </section>
</template>
