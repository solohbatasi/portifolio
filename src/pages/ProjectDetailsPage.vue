<script setup>
import { ArrowLeft, ExternalLink, Github, ImageOff, ListChecks } from 'lucide-vue-next'
import { computed, watchEffect } from 'vue'
import AppBadge from '../components/common/AppBadge.vue'
import AppButton from '../components/common/AppButton.vue'
import AppContainer from '../components/common/AppContainer.vue'
import ContactSection from '../components/home/ContactSection.vue'
import ArchitectureFlow from '../components/projects/ArchitectureFlow.vue'
import ProjectNavigation from '../components/projects/ProjectNavigation.vue'
import ProjectNotice from '../components/projects/ProjectNotice.vue'
import ProjectVisual from '../components/projects/ProjectVisual.vue'
import { profile } from '../data/profile'
import { projects } from '../data/projects'
import { setPageSeo } from '../utils/seo'
import { resolvePublicAsset } from '../utils/assets'
import NotFoundPage from './NotFoundPage.vue'

const props = defineProps({
  slug: {
    type: String,
    required: true,
  },
})

const orderedProjects = [...projects].sort((a, b) => a.sortOrder - b.sortOrder)
const project = computed(() => orderedProjects.find((item) => item.slug === props.slug))
const projectIndex = computed(() =>
  project.value ? orderedProjects.findIndex((item) => item.id === project.value.id) : -1,
)
const previousProject = computed(() =>
  projectIndex.value > 0 ? orderedProjects[projectIndex.value - 1] : null,
)
const nextProject = computed(() =>
  projectIndex.value >= 0 && projectIndex.value < orderedProjects.length - 1
    ? orderedProjects[projectIndex.value + 1]
    : null,
)
const isPrivate = computed(() =>
  project.value
    ? /private|confidential/i.test(project.value.repositoryVisibility)
    : false,
)

function isValidUrl(value) {
  if (!value) return false

  try {
    const url = new URL(value, window.location.origin)
    return ['http:', 'https:'].includes(url.protocol)
  } catch {
    return false
  }
}

const hasLiveUrl = computed(() => isValidUrl(project.value?.liveUrl))
const hasRepositoryUrl = computed(() => isValidUrl(project.value?.repositoryUrl))

watchEffect(() => {
  if (!project.value) {
    setPageSeo({
      title: `Project not found — ${profile.name}`,
      description: 'The requested project case study was not found.',
      path: `/projects/${props.slug}`,
    })
    return
  }

  setPageSeo({
    title: `${project.value.title} — ${profile.name}`,
    description: project.value.shortSummary,
    path: `/projects/${project.value.slug}`,
    image: project.value.image,
    type: 'article',
  })
})
</script>

<template>
  <NotFoundPage v-if="!project" />
  <article
    v-else
    class="case-study"
  >
    <header class="case-study__header">
      <AppContainer>
        <nav
          class="breadcrumb"
          aria-label="Breadcrumb"
        >
          <RouterLink to="/">
            Home
          </RouterLink>
          <span aria-hidden="true">/</span>
          <RouterLink to="/#work">
            Selected work
          </RouterLink>
          <span aria-hidden="true">/</span>
          <span aria-current="page">{{ project.title }}</span>
        </nav>

        <div class="case-study__title-row">
          <div>
            <div class="case-study__labels">
              <AppBadge tone="teal">
                {{ project.category }}
              </AppBadge>
              <AppBadge tone="sky">
                {{ project.status }}
              </AppBadge>
              <AppBadge tone="neutral">
                {{ project.repositoryVisibility }}
              </AppBadge>
            </div>
            <h1>{{ project.title }}</h1>
            <p>{{ project.fullDescription }}</p>
          </div>
          <div
            v-if="hasLiveUrl || hasRepositoryUrl"
            class="case-study__links"
          >
            <AppButton
              v-if="hasLiveUrl"
              :href="project.liveUrl"
              target="_blank"
              rel="noreferrer"
            >
              View live system <ExternalLink
                :size="16"
                aria-hidden="true"
              />
            </AppButton>
            <AppButton
              v-if="hasRepositoryUrl"
              :href="project.repositoryUrl"
              variant="secondary"
              target="_blank"
              rel="noreferrer"
            >
              View repository <Github
                :size="16"
                aria-hidden="true"
              />
            </AppButton>
          </div>
        </div>

        <dl class="project-overview">
          <div><dt>Role</dt><dd>{{ project.role }}</dd></div>
          <div>
            <dt>Technology overview</dt>
            <dd>
              {{
                project.technologies?.length
                  ? project.technologies.join(' · ')
                  : 'Technology overview not published'
              }}
            </dd>
          </div>
        </dl>
      </AppContainer>
    </header>

    <AppContainer class="case-study__visual-wrap">
      <ProjectVisual :project="project" />
    </AppContainer>

    <AppContainer class="case-study__body">
      <div class="case-study__narrative">
        <section aria-labelledby="challenge-title">
          <p class="case-study__eyebrow">
            01 · Context
          </p>
          <h2 id="challenge-title">
            The challenge
          </h2>
          <p>{{ project.challenge || 'Challenge details are being documented.' }}</p>
        </section>
        <section aria-labelledby="solution-title">
          <p class="case-study__eyebrow">
            02 · Engineering response
          </p>
          <h2 id="solution-title">
            The solution
          </h2>
          <p>{{ project.solution || 'Solution details are being documented.' }}</p>
        </section>
      </div>

      <div class="case-study__lists">
        <section aria-labelledby="responsibilities-title">
          <h2 id="responsibilities-title">
            My responsibilities
          </h2>
          <ul class="case-study__checklist">
            <li
              v-for="item in project.responsibilities || []"
              :key="item"
            >
              <ListChecks
                :size="17"
                aria-hidden="true"
              />{{ item }}
            </li>
          </ul>
        </section>
        <section aria-labelledby="capabilities-title">
          <h2 id="capabilities-title">
            Key capabilities
          </h2>
          <ul class="case-study__capabilities">
            <li
              v-for="item in project.capabilities || []"
              :key="item"
            >
              {{ item }}
            </li>
          </ul>
        </section>
      </div>

      <section
        class="case-study__architecture"
        aria-labelledby="architecture-title"
      >
        <p class="case-study__eyebrow">
          03 · System design
        </p>
        <h2 id="architecture-title">
          Technical architecture
        </h2>
        <p>
          A simplified view of the principal application boundaries and integrations used for
          this project. Sensitive infrastructure details are intentionally excluded.
        </p>
        <ArchitectureFlow :nodes="project.architecture || []" />
      </section>

      <section
        class="case-study__outcome"
        aria-labelledby="outcome-title"
      >
        <p class="case-study__eyebrow">
          04 · Delivery state
        </p>
        <h2 id="outcome-title">
          Outcome
        </h2>
        <p>{{ project.outcome || 'Outcome details are being documented.' }}</p>
      </section>

      <section
        class="case-study__gallery"
        aria-labelledby="gallery-title"
      >
        <div>
          <p class="case-study__eyebrow">
            05 · Project visuals
          </p>
          <h2 id="gallery-title">
            Screenshots and interface views
          </h2>
        </div>
        <div
          v-if="project.gallery?.length"
          class="project-gallery-grid"
        >
          <figure
            v-for="(image, index) in project.gallery || []"
            :key="image.src"
          >
            <img
              :src="resolvePublicAsset(image.src)"
              :alt="image.alt || `${project.title} screenshot ${index + 1}`"
              :width="image.width || 1600"
              :height="image.height || 900"
              loading="lazy"
              decoding="async"
            >
            <figcaption v-if="image.caption">
              {{ image.caption }}
            </figcaption>
          </figure>
        </div>
        <div
          v-else
          class="project-gallery-empty"
        >
          <ImageOff
            :size="23"
            aria-hidden="true"
          />
          <div>
            <h3>Interface imagery is not published</h3>
            <p>
              The case study documents the verified engineering work without exposing private
              operational screens or data.
            </p>
          </div>
        </div>
      </section>

      <ProjectNotice
        :visibility="project.repositoryVisibility"
        :is-private="isPrivate"
      />

      <RouterLink
        to="/#work"
        class="case-study__back-link"
      >
        <ArrowLeft
          :size="16"
          aria-hidden="true"
        /> Back to selected work
      </RouterLink>
      <ProjectNavigation
        :previous-project="previousProject"
        :next-project="nextProject"
      />
    </AppContainer>

    <ContactSection />
  </article>
</template>
