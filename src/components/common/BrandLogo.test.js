import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import BrandLogo from './BrandLogo.vue'

function createTestRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/', component: { template: '<div />' } }],
  })
}

describe('BrandLogo', () => {
  it('renders the signature SVG as an eager accessible home link', () => {
    const wrapper = mount(BrandLogo, {
      props: { variant: 'signature', linked: true, loading: 'eager' },
      global: { plugins: [createTestRouter()] },
    })

    expect(wrapper.get('a').attributes('aria-label'))
      .toBe('Solomon Batasi — Full-Stack Software Engineer')
    expect(wrapper.get('img').attributes('src')).toContain('solomon-batasi-signature.svg')
    expect(wrapper.get('img').attributes('loading')).toBe('eager')
    expect(wrapper.get('img').attributes('alt')).toBe('')
  })

  it('uses the lockup when the tagline is requested and falls back to PNG', async () => {
    const wrapper = mount(BrandLogo, {
      props: { variant: 'signature', showTagline: true },
    })

    expect(wrapper.get('img').attributes('src')).toContain('solomon-batasi-lock.svg')
    await wrapper.get('img').trigger('error')
    expect(wrapper.get('img').attributes('src')).toContain('solomon-batasi-lock.png')
  })
})
