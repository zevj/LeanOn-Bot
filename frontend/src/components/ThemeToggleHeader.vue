<template>
  <button
    class="theme-toggle-header"
    @click="toggleTheme"
    :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
  >
    <i :class="['bx', isDark ? 'bx-sun' : 'bx-moon']"></i>
  </button>
</template>

<script setup>
import { useTheme } from '@/composables/useTheme'

const { isDark, toggleTheme } = useTheme()
</script>

<style scoped>
.theme-toggle-header {
  background: transparent;
  border: none;
  color: var(--text-primary);
  cursor: pointer;
  padding: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.theme-toggle-header i {
  font-size: 20px;
}

.theme-toggle-header:hover {
  color: var(--secondary-color);
}
</style>
