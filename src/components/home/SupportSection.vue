<script setup>
import { Coffee, CreditCard, ShieldCheck } from 'lucide-vue-next'
import AppButton from '../common/AppButton.vue'
import AppContainer from '../common/AppContainer.vue'
import { profile } from '../../data/profile'
import { useCoffeeModal } from '../../composables/useCoffeeModal'

const coffeeModal = useCoffeeModal()
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
          type="button"
          @click="coffeeModal.openCoffeeModal($event.currentTarget)"
        >
          <Coffee
            :size="18"
            aria-hidden="true"
          /> Buy Me a Coffee
        </AppButton>
      </div>
    </AppContainer>
  </section>
</template>
