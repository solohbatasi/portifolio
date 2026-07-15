import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  fetchCoffeePaymentStatus,
  initiateCoffeePayment,
} from './supportPaymentService'

afterEach(() => vi.unstubAllGlobals())

describe('supportPaymentService', () => {
  it('posts only the expected payment payload to the portfolio backend', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({ payment_id: '550e8400-e29b-41d4-a716-446655440000', status: 'pending' }),
    })
    vi.stubGlobal('fetch', fetchMock)
    const payload = { phone: '0712345678', amount: 250, request_id: 'request-uuid' }

    await initiateCoffeePayment(payload)

    expect(fetchMock).toHaveBeenCalledOnce()
    expect(fetchMock.mock.calls[0][0]).toBe('/api/coffee-payments')
    expect(JSON.parse(fetchMock.mock.calls[0][1].body)).toEqual(payload)
    expect(fetchMock.mock.calls[0][1].headers).not.toHaveProperty('x-api-key')
  })

  it('checks status through the portfolio backend and never calls Daraja directly', async () => {
    const fetchMock = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ status: 'success' }) })
    vi.stubGlobal('fetch', fetchMock)

    await fetchCoffeePaymentStatus('txn_123')

    expect(fetchMock.mock.calls[0][0]).toBe('/api/coffee-payments/txn_123')
    expect(fetchMock.mock.calls[0][0]).not.toContain('safaricom')
    expect(fetchMock.mock.calls[0][0]).not.toContain('kadi')
  })
})
