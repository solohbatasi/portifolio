<script setup>
import AppButton from '../components/common/AppButton.vue'
import AppContainer from '../components/common/AppContainer.vue'
</script>

<template>
  <section class="page-intro page-intro--center">
    <AppContainer>
      <p class="page-intro__eyebrow">
        404
      </p>
      <h1>Page not found</h1>
      <p>The page you requested does not exist or may have moved.</p>
      <div class="not-found-actions">
        <AppButton to="/#work">
          Return to selected work
        </AppButton>
        <AppButton
          to="/"
          variant="secondary"
        >
          Return home
        </AppButton>
      </div>
    </AppContainer>
  </section>
</template>
