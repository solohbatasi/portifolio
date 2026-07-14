<script setup>
defineProps({
  modelValue: { type: [Number, String], required: true },
  presets: { type: Array, required: true },
  currency: { type: String, default: 'KES' },
  minimum: { type: Number, required: true },
  maximum: { type: Number, required: true },
  error: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

function selectPreset(value) {
  emit('update:modelValue', value)
}
</script>

<template>
  <fieldset class="amount-selector">
    <legend>Choose an amount</legend>
    <div class="amount-selector__presets">
      <button
        v-for="preset in presets"
        :key="preset"
        type="button"
        :class="{ 'is-selected': Number(modelValue) === preset }"
        :aria-pressed="Number(modelValue) === preset"
        @click="selectPreset(preset)"
      >
        {{ currency }} {{ preset.toLocaleString() }}
      </button>
    </div>
    <label for="coffee-custom-amount">Custom amount</label>
    <div class="amount-selector__input">
      <span aria-hidden="true">{{ currency }}</span>
      <input
        id="coffee-custom-amount"
        :value="modelValue"
        type="number"
        inputmode="numeric"
        step="1"
        :min="minimum"
        :max="maximum"
        required
        :aria-invalid="Boolean(error)"
        :aria-describedby="error ? 'coffee-amount-error' : 'coffee-amount-help'"
        @input="emit('update:modelValue', $event.target.value)"
      >
    </div>
    <small id="coffee-amount-help">Whole amounts from {{ currency }} {{ minimum }} to {{ currency }} {{ maximum.toLocaleString() }}.</small>
    <small
      v-if="error"
      id="coffee-amount-error"
      class="coffee-field-error"
    >{{ error }}</small>
  </fieldset>
</template>
