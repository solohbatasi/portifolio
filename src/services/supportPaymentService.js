const JSON_HEADERS = Object.freeze({
  Accept: 'application/json',
  'Content-Type': 'application/json',
})

async function readResponse(response) {
  const body = await response.json().catch(() => ({}))
  if (!response.ok) {
    const error = new Error('The payment service could not complete the request.')
    error.status = response.status
    error.safeMessage = typeof body.message === 'string' ? body.message : null
    error.fields = body.errors && typeof body.errors === 'object' ? body.errors : {}
    throw error
  }
  return body
}

export async function initiateCoffeePayment(payload, options = {}) {
  const response = await fetch(supportEndpoint, {
    method: 'POST',
    headers: JSON_HEADERS,
    body: JSON.stringify(payload),
    signal: options.signal,
  })
  return readResponse(response)
}

export async function fetchCoffeePaymentStatus(transactionId, options = {}) {
  const response = await fetch(`${supportEndpoint}/${encodeURIComponent(transactionId)}`, {
    headers: { Accept: 'application/json' },
    signal: options.signal,
  })
  return readResponse(response)
}
import { profile } from '../data/profile'

const supportEndpoint = profile.support.endpoint
