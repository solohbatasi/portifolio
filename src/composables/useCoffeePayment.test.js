import { defineComponent, h, nextTick } from 'vue'
import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useCoffeePayment } from './useCoffeePayment'

function mountPayment(dependencies) {
  let payment
  const wrapper = mount(defineComponent({
    setup() {
      payment = useCoffeePayment(dependencies)
      return () => h('div')
    },
  }))
  return { payment, wrapper }
}

beforeEach(() => {
  vi.useFakeTimers()
  vi.setSystemTime(new Date('2026-07-14T12:00:00Z'))
  vi.stubGlobal('crypto', {
    randomUUID: vi.fn(() => '550e8400-e29b-41d4-a716-446655440000'),
    getRandomValues: globalThis.crypto?.getRandomValues,
  })
})

afterEach(() => {
  vi.useRealTimers()
  vi.unstubAllGlobals()
})

describe('useCoffeePayment', () => {
  it('submits the expected payload and enters the prompt-sent state', async () => {
    const initiate = vi.fn().mockResolvedValue({ payment_id: 'txn_1234', status: 'pending' })
    const { payment, wrapper } = mountPayment({ initiate, status: vi.fn() })

    await payment.initiate({ phone: '0712345678', amount: 250 })

    expect(initiate.mock.calls[0][0]).toEqual({
      phone: '0712345678',
      amount: 250,
      request_id: '550e8400-e29b-41d4-a716-446655440000',
    })
    expect(payment.state.value).toBe('prompt-sent')
    wrapper.unmount()
  })

  it('prevents duplicate submissions while initiation is pending', async () => {
    let resolveRequest
    const initiate = vi.fn(() => new Promise((resolve) => { resolveRequest = resolve }))
    const { payment, wrapper } = mountPayment({ initiate, status: vi.fn() })

    const first = payment.initiate({ phone: '0712345678', amount: 100 })
    const second = await payment.initiate({ phone: '0712345678', amount: 100 })

    expect(second).toBe(false)
    expect(initiate).toHaveBeenCalledOnce()
    resolveRequest({ payment_id: 'txn_1234', status: 'pending' })
    await first
    wrapper.unmount()
  })

  it('reuses its UUID after a temporary initiation failure', async () => {
    const initiate = vi.fn()
      .mockRejectedValueOnce(new TypeError('offline'))
      .mockResolvedValueOnce({ payment_id: 'txn_1234', status: 'pending' })
    const { payment, wrapper } = mountPayment({ initiate, status: vi.fn() })

    await payment.initiate({ phone: '0712345678', amount: 100 })
    await payment.initiate({ phone: '0712345678', amount: 100 })

    expect(initiate.mock.calls[0][0].request_id).toBe(initiate.mock.calls[1][0].request_id)
    expect(crypto.randomUUID).toHaveBeenCalledOnce()
    wrapper.unmount()
  })

  it('polls to success and cleans up the timer', async () => {
    const status = vi.fn().mockResolvedValue({ status: 'success', message: 'Paid' })
    const { payment, wrapper } = mountPayment({
      initiate: vi.fn().mockResolvedValue({ payment_id: 'txn_1234', status: 'pending' }),
      status,
    })
    await payment.initiate({ phone: '0712345678', amount: 100 })

    await vi.advanceTimersByTimeAsync(4000)

    expect(payment.state.value).toBe('success')
    expect(status).toHaveBeenCalledOnce()
    await vi.advanceTimersByTimeAsync(8000)
    expect(status).toHaveBeenCalledOnce()
    wrapper.unmount()
  })

  it.each(['cancelled', 'failed', 'reversed', 'timeout'])('handles the %s terminal state', async (terminalState) => {
    const { payment, wrapper } = mountPayment({
      initiate: vi.fn().mockResolvedValue({ payment_id: 'txn_1234', status: 'pending' }),
      status: vi.fn().mockResolvedValue({ status: terminalState, message: 'Safe provider message' }),
    })
    await payment.initiate({ phone: '0712345678', amount: 100 })
    await vi.advanceTimersByTimeAsync(4000)
    await nextTick()

    expect(payment.state.value).toBe(terminalState)
    expect(payment.message.value).toBe('Safe provider message')
    wrapper.unmount()
  })

  it('retries a temporary status failure without creating a second STK push', async () => {
    const initiate = vi.fn().mockResolvedValue({ payment_id: 'txn_1234', status: 'pending' })
    const status = vi.fn()
      .mockRejectedValueOnce(new TypeError('offline'))
      .mockResolvedValueOnce({ status: 'success' })
    const { payment, wrapper } = mountPayment({ initiate, status })
    await payment.initiate({ phone: '0712345678', amount: 100 })

    await vi.advanceTimersByTimeAsync(4000)
    expect(payment.state.value).toBe('status-unavailable')
    await vi.advanceTimersByTimeAsync(4000)

    expect(initiate).toHaveBeenCalledOnce()
    expect(status).toHaveBeenCalledTimes(2)
    expect(payment.state.value).toBe('success')
    wrapper.unmount()
  })

  it('cleans polling up when the component unmounts', async () => {
    const status = vi.fn().mockResolvedValue({ status: 'processing' })
    const { payment, wrapper } = mountPayment({
      initiate: vi.fn().mockResolvedValue({ payment_id: 'txn_1234', status: 'pending' }),
      status,
    })
    await payment.initiate({ phone: '0712345678', amount: 100 })
    wrapper.unmount()
    await vi.advanceTimersByTimeAsync(8000)
    expect(status).not.toHaveBeenCalled()
  })

  it('stops with a safe timeout after the overall polling window', async () => {
    const status = vi.fn().mockResolvedValue({ status: 'processing' })
    const { payment, wrapper } = mountPayment({
      initiate: vi.fn().mockResolvedValue({ payment_id: 'txn_1234', status: 'pending' }),
      status,
    })
    await payment.initiate({ phone: '0712345678', amount: 100 })
    await vi.advanceTimersByTimeAsync(92000)

    expect(payment.state.value).toBe('timeout')
    const checksAtTimeout = status.mock.calls.length
    await vi.advanceTimersByTimeAsync(8000)
    expect(status).toHaveBeenCalledTimes(checksAtTimeout)
    wrapper.unmount()
  })
})
