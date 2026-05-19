<template>
  <button 
    class="theme-toggle-fab" 
    @click="toggleTheme" 
    :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
  >
    <transition name="icon-fade" mode="out-in">
      <i :key="isDark" :class="['bx', isDark ? 'bx-sun' : 'bx-moon']"></i>
    </transition>
  </button>
</template>

<script setup>
import { useTheme } from '@/composables/useTheme'

const { isDark, toggleTheme } = useTheme()
</script>

<style scoped>
.theme-toggle-fab {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: var(--surface-color);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
  box-shadow: 0 4px 12px var(--shadow-color);
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  z-index: 9999;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(8px);
}

.theme-toggle-fab:hover {
  transform: scale(1.08) rotate(15deg);
  box-shadow: 0 6px 16px var(--shadow-color);
  background: var(--surface-hover);
}

.theme-toggle-fab:active {
  transform: scale(0.95);
}

.theme-toggle-fab i {
  font-size: 22px;
}

/* Smooth toggle transition */
.icon-fade-enter-active,
.icon-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.icon-fade-enter-from {
  opacity: 0;
  transform: scale(0.6) rotate(-45deg);
}

.icon-fade-leave-to {
  opacity: 0;
  transform: scale(0.6) rotate(45deg);
}
</style>
