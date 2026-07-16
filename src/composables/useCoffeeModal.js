import { nextTick, ref } from 'vue'

const isCoffeeModalOpen = ref(false)
let triggerElement = null

export function useCoffeeModal() {
  function openCoffeeModal(trigger = null) {
    triggerElement = trigger instanceof HTMLElement ? trigger : document.activeElement
    isCoffeeModalOpen.value = true
  }
  async function closeCoffeeModal() {
    isCoffeeModalOpen.value = false
    await nextTick()
    triggerElement?.focus?.()
    triggerElement = null
  }
  return { isCoffeeModalOpen, openCoffeeModal, closeCoffeeModal }
}
