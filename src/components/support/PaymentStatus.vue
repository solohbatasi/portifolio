<script setup>
import { CheckCircle2, CircleX, Clock3, LoaderCircle, Smartphone } from 'lucide-vue-next'

defineProps({
  state: { type: String, required: true },
  message: { type: String, default: '' },
})

const content = {
  initiating: { title: 'Sending the payment request…', icon: LoaderCircle },
  'prompt-sent': { title: 'Check your phone', icon: Smartphone },
  processing: { title: 'Confirming your payment', icon: LoaderCircle },
  'status-unavailable': { title: 'Temporarily unable to check status', icon: Clock3 },
  success: { title: 'Thank you for the coffee!', icon: CheckCircle2 },
  cancelled: { title: 'Payment cancelled', icon: CircleX },
  failed: { title: 'Payment not completed', icon: CircleX },
  reversed: { title: 'Payment reversed', icon: CircleX },
  timeout: { title: 'Confirmation timed out', icon: Clock3 },
}
</script>

<template>
  <div
    class="payment-status"
    role="status"
    aria-live="polite"
  >
    <component
      :is="content[state]?.icon"
      class="payment-status__icon"
      :class="{ 'is-spinning': ['initiating', 'processing'].includes(state) }"
      :size="30"
      aria-hidden="true"
    />
    <h3>{{ content[state]?.title }}</h3>
    <p v-if="state === 'prompt-sent'">
      An M-PESA prompt has been sent to the number you provided. Enter your M-PESA PIN securely on your phone to complete the payment.
    </p>
    <p v-else-if="state === 'success'">
      Your support helps me continue building and sharing useful software solutions.
    </p>
    <p v-else-if="message">
      {{ message }}
    </p>
    <p
      v-if="state === 'prompt-sent'"
      class="payment-status__note"
    >
      For your security, never enter your M-PESA PIN on this website.
    </p>
  </div>
</template>
