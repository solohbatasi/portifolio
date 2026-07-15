import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import CoffeeModal from './CoffeeModal.vue'
import SupportSection from '../home/SupportSection.vue'
import * as paymentService from '../../services/supportPaymentService'

beforeEach(() => {
  document.body.innerHTML = ''
  vi.spyOn(paymentService, 'initiateCoffeePayment').mockResolvedValue({
    payment_id: 'txn_1234',
    status: 'pending',
    message: 'Prompt sent',
  })
  vi.spyOn(paymentService, 'fetchCoffeePaymentStatus').mockResolvedValue({ status: 'processing' })
})

afterEach(() => {
  document.body.classList.remove('coffee-modal-open')
})

describe('CoffeeModal', () => {
  it('opens from the section, moves focus inside and closes with Escape', async () => {
    const wrapper = mount(SupportSection, { attachTo: document.body })
    await wrapper.get('button').trigger('click')
    const dialog = document.querySelector('[role="dialog"]')

    expect(dialog).not.toBeNull()
    expect(dialog.contains(document.activeElement)).toBe(true)
    dialog.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))
    await wrapper.vm.$nextTick()
    expect(document.querySelector('[role="dialog"]')).toBeNull()
    expect(document.activeElement).toBe(wrapper.get('button').element)
    wrapper.unmount()
  })

  it('selects presets and accepts a custom amount', async () => {
    const wrapper = mount(CoffeeModal, { props: { open: true }, attachTo: document.body })
    const buttons = [...document.querySelectorAll('.amount-selector__presets button')]
    buttons[2].click()
    await wrapper.vm.$nextTick()
    expect(document.querySelector('#coffee-custom-amount').value).toBe('500')

    const custom = document.querySelector('#coffee-custom-amount')
    custom.value = '725'
    custom.dispatchEvent(new Event('input', { bubbles: true }))
    await wrapper.vm.$nextTick()
    expect(custom.value).toBe('725')
    wrapper.unmount()
  })

  it('keeps Tab focus within the modal', async () => {
    const wrapper = mount(CoffeeModal, { props: { open: true }, attachTo: document.body })
    const dialog = document.querySelector('[role="dialog"]')
    const focusable = [...dialog.querySelectorAll('button:not([disabled]), input:not([disabled])')]
    const first = focusable[0]
    const last = focusable[focusable.length - 1]
    last.focus()
    last.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true }))
    expect(document.activeElement).toBe(first)
    wrapper.unmount()
  })

  it('shows inline phone validation and does not submit invalid details', async () => {
    const wrapper = mount(CoffeeModal, { props: { open: true }, attachTo: document.body })
    const phone = document.querySelector('#coffee-phone')
    phone.value = '123'
    phone.dispatchEvent(new Event('input', { bubbles: true }))
    document.querySelector('.coffee-modal__form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))
    await wrapper.vm.$nextTick()

    expect(document.querySelector('#coffee-phone-error').textContent).toContain('Kenyan')
    expect(paymentService.initiateCoffeePayment).not.toHaveBeenCalled()
    wrapper.unmount()
  })

  it('shows the prompt-sent state after valid submission', async () => {
    const wrapper = mount(CoffeeModal, { props: { open: true }, attachTo: document.body })
    const phone = document.querySelector('#coffee-phone')
    phone.value = '0712 345 678'
    phone.dispatchEvent(new Event('input', { bubbles: true }))
    document.querySelector('.coffee-modal__form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))
    await vi.waitFor(() => expect(document.body.textContent).toContain('Check your phone'))
    expect(document.body.textContent).not.toContain('M-Pesa PIN input')
    wrapper.unmount()
  })

  it('clears the phone after closing and reopening', async () => {
    const wrapper = mount(SupportSection, { attachTo: document.body })
    await wrapper.get('button').trigger('click')
    const phone = document.querySelector('#coffee-phone')
    phone.value = '0712345678'
    phone.dispatchEvent(new Event('input', { bubbles: true }))
    document.querySelector('[aria-label="Close support dialog"]').click()
    await wrapper.vm.$nextTick()
    await wrapper.get('button').trigger('click')
    expect(document.querySelector('#coffee-phone').value).toBe('')
    wrapper.unmount()
  })
})
