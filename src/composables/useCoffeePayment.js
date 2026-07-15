import { computed, onBeforeUnmount, ref } from 'vue'
import {
  fetchCoffeePaymentStatus,
  initiateCoffeePayment,
} from '../services/supportPaymentService'

const TERMINAL_STATUSES = new Set(['success', 'failed', 'cancelled', 'timeout', 'reversed'])
const POLL_INTERVAL = 4000
const POLL_TIMEOUT = 90000

function createRequestId() {
  if (typeof crypto.randomUUID === 'function') return crypto.randomUUID()

  const bytes = crypto.getRandomValues(new Uint8Array(16))
  bytes[6] = (bytes[6] & 0x0f) | 0x40
  bytes[8] = (bytes[8] & 0x3f) | 0x80
  const value = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('')
  return `${value.slice(0, 8)}-${value.slice(8, 12)}-${value.slice(12, 16)}-${value.slice(16, 20)}-${value.slice(20)}`
}

export function useCoffeePayment(dependencies = {}) {
  const initiateRequest = dependencies.initiate || initiateCoffeePayment
  const statusRequest = dependencies.status || fetchCoffeePaymentStatus
  const state = ref('form')
  const message = ref('')
  const fieldErrors = ref({})
  const paymentId = ref(null)
  const requestId = ref(null)
  const submitting = ref(false)
  let pollTimer = null
  let pollStartedAt = 0
  let requestController = null

  const isTerminal = computed(() => TERMINAL_STATUSES.has(state.value))

  function clearTimer() {
    if (pollTimer !== null) window.clearTimeout(pollTimer)
    pollTimer = null
  }

  function stop({ abort = false } = {}) {
    clearTimer()
    if (abort && requestController) requestController.abort()
    requestController = null
  }

  function schedulePoll() {
    clearTimer()
    pollTimer = window.setTimeout(poll, POLL_INTERVAL)
  }

  async function poll() {
    if (!paymentId.value || TERMINAL_STATUSES.has(state.value)) return
    if (Date.now() - pollStartedAt >= POLL_TIMEOUT) {
      state.value = 'timeout'
      message.value = 'Payment confirmation timed out. Check your M-Pesa messages before trying again.'
      stop()
      return
    }

    requestController = new AbortController()
    try {
      const result = await statusRequest(paymentId.value, { signal: requestController.signal })
      const nextStatus = typeof result.status === 'string' ? result.status.toLowerCase() : 'processing'
      state.value = nextStatus === 'pending' ? 'prompt-sent' : nextStatus
      message.value = typeof result.message === 'string' ? result.message : ''
      if (TERMINAL_STATUSES.has(nextStatus)) stop()
      else schedulePoll()
    } catch (error) {
      if (error.name !== 'AbortError') {
        state.value = 'status-unavailable'
        message.value = 'Payment confirmation is taking longer than expected. We are checking again.'
        schedulePoll()
      }
    }
  }

  async function initiate(payload) {
    if (submitting.value) return false
    submitting.value = true
    state.value = 'initiating'
    message.value = ''
    fieldErrors.value = {}
    if (!requestId.value) requestId.value = createRequestId()
    requestController = new AbortController()

    try {
      const result = await initiateRequest(
        { phone: payload.phone, amount: payload.amount, request_id: requestId.value },
        { signal: requestController.signal },
      )
      if (typeof result.payment_id !== 'string' || !result.payment_id) {
        throw new Error('Missing payment identifier')
      }
      paymentId.value = result.payment_id
      const initialStatus = typeof result.status === 'string' ? result.status.toLowerCase() : 'pending'
      state.value = TERMINAL_STATUSES.has(initialStatus)
        ? initialStatus
        : initialStatus === 'processing' ? 'processing' : 'prompt-sent'
      message.value = typeof result.message === 'string' ? result.message : ''
      if (TERMINAL_STATUSES.has(initialStatus)) return true
      pollStartedAt = Date.now()
      schedulePoll()
      return true
    } catch (error) {
      if (error.name !== 'AbortError') {
        state.value = 'form'
        fieldErrors.value = error.fields || {}
        message.value = error.status === 422
          ? 'Please review the highlighted details.'
          : 'The payment service is temporarily unavailable. Check your connection and try again.'
      }
      return false
    } finally {
      submitting.value = false
      requestController = null
    }
  }

  function resetAttempt() {
    stop({ abort: true })
    state.value = 'form'
    message.value = ''
    fieldErrors.value = {}
    paymentId.value = null
    requestId.value = null
    pollStartedAt = 0
  }

  onBeforeUnmount(() => stop({ abort: true }))

  return {
    fieldErrors,
    initiate,
    isTerminal,
    message,
    requestId,
    resetAttempt,
    state,
    stop,
    submitting,
    paymentId,
  }
}
