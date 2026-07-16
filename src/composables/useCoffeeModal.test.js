import { describe, expect, it } from 'vitest'
import { useCoffeeModal } from './useCoffeeModal'

describe('useCoffeeModal', () => {
  it('shares one modal state between header and support triggers', async () => {
    const header = useCoffeeModal()
    const support = useCoffeeModal()
    expect(header.isCoffeeModalOpen).toBe(support.isCoffeeModalOpen)
    header.openCoffeeModal(document.createElement('button'))
    expect(support.isCoffeeModalOpen.value).toBe(true)
    await support.closeCoffeeModal()
    expect(header.isCoffeeModalOpen.value).toBe(false)
  })
})
