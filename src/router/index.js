import { createRouter, createWebHistory } from 'vue-router'
import { profile } from '../data/profile'
import { setPageSeo } from '../utils/seo'

const HomePage = () => import('../pages/HomePage.vue')
const NotFoundPage = () => import('../pages/NotFoundPage.vue')
const ProjectDetailsPage = () => import('../pages/ProjectDetailsPage.vue')

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomePage,
    },
    {
      path: '/projects/:slug',
      name: 'project-details',
      component: ProjectDetailsPage,
      props: true,
    },
    {
      path: '/404',
      name: 'not-found',
      component: NotFoundPage,
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/404',
    },
  ],
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition

    if (to.hash) {
      return {
        el: to.hash,
        top: 96,
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
          ? 'auto'
          : 'smooth',
      }
    }

    return { top: 0 }
  },
})

router.afterEach((to) => {
  if (to.name === 'home') {
    setPageSeo({
      title: `${profile.name} — ${profile.metadataTitle}`,
      description: profile.heroSupportingText,
      path: '/',
    })
  } else if (to.name === 'not-found') {
    setPageSeo({
      title: `Page not found — ${profile.name}`,
      description: 'The requested page could not be found.',
      path: to.fullPath,
    })
  }
})

export default router
