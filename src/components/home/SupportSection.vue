<script setup>
import { Coffee, CreditCard, ShieldCheck } from 'lucide-vue-next'
import { nextTick, ref } from 'vue'
import AppButton from '../common/AppButton.vue'
import AppContainer from '../common/AppContainer.vue'
import CoffeeModal from '../support/CoffeeModal.vue'
import { profile } from '../../data/profile'

const modalOpen = ref(false)
const triggerButton = ref(null)

async function closeModal() {
  modalOpen.value = false
  await nextTick()
  triggerButton.value?.$el?.focus()
}
</script>

<template>
  <section
    class="support-section"
    aria-labelledby="support-title"
  >
    <AppContainer
      class="support-section__panel"
      data-reveal
    >
      <div class="support-section__copy">
        <p class="support-section__eyebrow">
          Support My Work
        </p>
        <h2 id="support-title">
          Buy Me a Coffee
        </h2>
        <p>{{ profile.support.description }}</p>
      </div>
      <div class="support-section__action">
        <ul aria-label="Payment details">
          <li>
            <ShieldCheck
              :size="17"
              aria-hidden="true"
            />Pay securely via M-PESA.
          </li>
          <li>
            <CreditCard
              :size="17"
              aria-hidden="true"
            />No card details required
          </li>
        </ul>
        <AppButton
          ref="triggerButton"
          type="button"
          @click="modalOpen = true"
        >
          <Coffee
            :size="18"
            aria-hidden="true"
          /> Buy Me a Coffee
        </AppButton>
      </div>
    </AppContainer>
    <CoffeeModal
      v-if="modalOpen"
      :open="modalOpen"
      @close="closeModal"
    />
  </section>
</template>
