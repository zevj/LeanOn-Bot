import { ref } from 'vue'

const mobileToggleCount = ref(0)

export function useSidebarToggle() {
  const triggerMobileToggle = () => {
    mobileToggleCount.value++
  }

  return { mobileToggleCount, triggerMobileToggle }
}