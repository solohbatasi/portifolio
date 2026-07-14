<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { ArrowRight, X } from 'lucide-vue-next'
import AppButton from '../common/AppButton.vue'
import AmountSelector from './AmountSelector.vue'
import PaymentStatus from './PaymentStatus.vue'
import { useCoffeePayment } from '../../composables/useCoffeePayment'
import { profile } from '../../data/profile'

defineProps({
  open: { type: Boolean, required: true },
})
const emit = defineEmits(['close'])
const support = profile.support
const dialog = ref(null)
const amount = ref(support.presetAmounts[0])
const phone = ref('')
const localErrors = ref({})

const payment = useCoffeePayment()
const isForm = computed(() => payment.state.value === 'form')
const canClose = computed(() => payment.state.value !== 'initiating')
const errorMessage = computed(() => payment.message.value)

function validate() {
  const errors = {}
  const numericAmount = Number(amount.value)
  if (amount.value === '' || !Number.isInteger(numericAmount)) {
    errors.amount = 'Enter a whole-number amount.'
  } else if (numericAmount < support.minimumAmount || numericAmount > support.maximumAmount) {
    errors.amount = `Enter an amount from KES ${support.minimumAmount} to KES ${support.maximumAmount.toLocaleString()}.`
  }

  const compactPhone = phone.value.replace(/[\s()-]/g, '')
  if (!compactPhone) errors.phone = 'Enter the M-Pesa phone number.'
  else if (!/^(?:0[17]|(?:\+?254)[17])\d{8}$/.test(compactPhone)) {
    errors.phone = 'Enter a supported Kenyan M-Pesa number.'
  }
  localErrors.value = errors
  return Object.keys(errors).length === 0
}

async function submit() {
  if (!validate()) return
  await payment.initiate({ phone: phone.value, amount: Number(amount.value) })
}

function clearSensitiveValues() {
  phone.value = ''
  amount.value = support.presetAmounts[0]
  localErrors.value = {}
}

function close() {
  if (!canClose.value) return
  payment.stop({ abort: true })
  payment.resetAttempt()
  clearSensitiveValues()
  document.body.classList.remove('coffee-modal-open')
  emit('close')
}

function tryAgain() {
  payment.resetAttempt()
  clearSensitiveValues()
  nextTick(() => dialog.value?.querySelector('input')?.focus())
}

function handleKeydown(event) {
  if (event.key === 'Escape' && canClose.value) {
    event.preventDefault()
    close()
    return
  }
  if (event.key !== 'Tab' || !dialog.value) return

  const focusable = [...dialog.value.querySelectorAll(
    'button:not([disabled]), input:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])',
  )]
  if (!focusable.length) return
  const first = focusable[0]
  const last = focusable[focusable.length - 1]
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

function viewProjects() {
  close()
  nextTick(() => document.querySelector('#work')?.scrollIntoView({ behavior: 'smooth' }))
}

onMounted(async () => {
  document.body.classList.add('coffee-modal-open')
  await nextTick()
  dialog.value?.querySelector('input, button')?.focus()
})

watch(payment.state, (nextState) => {
  if (nextState === 'success') {
    phone.value = ''
  }
})

onBeforeUnmount(() => {
  payment.stop({ abort: true })
  clearSensitiveValues()
  document.body.classList.remove('coffee-modal-open')
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="coffee-modal-layer"
    >
      <div
        class="coffee-modal-backdrop"
        aria-hidden="true"
      />
      <section
        ref="dialog"
        class="coffee-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="coffee-modal-title"
        aria-describedby="coffee-modal-description"
        @keydown="handleKeydown"
      >
        <header class="coffee-modal__header">
          <div>
            <p>Buy Me a Coffee</p>
            <h2 id="coffee-modal-title">
              Support My Work
            </h2>
          </div>
          <button
            type="button"
            class="icon-button"
            :disabled="!canClose"
            aria-label="Close support dialog"
            @click="close"
          >
            <X
              :size="20"
              aria-hidden="true"
            />
          </button>
        </header>

        <p
          id="coffee-modal-description"
          class="coffee-modal__description"
        >
          Choose an amount and enter the M-Pesa number that should receive the payment prompt.
        </p>

        <form
          v-if="isForm"
          class="coffee-modal__form"
          novalidate
          @submit.prevent="submit"
        >
          <AmountSelector
            v-model="amount"
            :presets="support.presetAmounts"
            :currency="support.currency"
            :minimum="support.minimumAmount"
            :maximum="support.maximumAmount"
            :error="localErrors.amount || payment.fieldErrors.value.amount?.[0] || ''"
          />

          <label for="coffee-phone">M-Pesa phone number</label>
          <input
            id="coffee-phone"
            v-model.trim="phone"
            type="tel"
            inputmode="tel"
            autocomplete="tel"
            placeholder="0712 345 678"
            required
            :aria-invalid="Boolean(localErrors.phone || payment.fieldErrors.value.phone)"
            :aria-describedby="localErrors.phone || payment.fieldErrors.value.phone ? 'coffee-phone-error' : 'coffee-phone-help'"
          >
          <small id="coffee-phone-help">Your number is used only to send the M-Pesa payment prompt.</small>
          <small
            v-if="localErrors.phone || payment.fieldErrors.value.phone"
            id="coffee-phone-error"
            class="coffee-field-error"
          >{{ localErrors.phone || payment.fieldErrors.value.phone?.[0] }}</small>

          <p
            v-if="errorMessage"
            class="coffee-modal__form-error"
            role="alert"
          >
            {{ errorMessage }}
          </p>
          <div class="coffee-modal__actions">
            <AppButton type="submit">
              Send M-Pesa Prompt
            </AppButton>
            <AppButton
              type="button"
              variant="secondary"
              @click="close"
            >
              Cancel
            </AppButton>
          </div>
        </form>

        <div
          v-else
          class="coffee-modal__status"
        >
          <PaymentStatus
            :state="payment.state.value"
            :message="payment.message.value"
          />
          <div
            v-if="payment.state.value === 'success'"
            class="coffee-modal__actions"
          >
            <AppButton
              type="button"
              @click="close"
            >
              Close
            </AppButton>
            <AppButton
              type="button"
              variant="secondary"
              @click="viewProjects"
            >
              View My Projects <ArrowRight
                :size="17"
                aria-hidden="true"
              />
            </AppButton>
          </div>
          <div
            v-else-if="payment.isTerminal.value"
            class="coffee-modal__actions"
          >
            <AppButton
              type="button"
              @click="tryAgain"
            >
              Try Again
            </AppButton>
            <AppButton
              type="button"
              variant="secondary"
              @click="close"
            >
              Close
            </AppButton>
          </div>
        </div>
      </section>
    </div>
  </Teleport>
</template>
