<template>
  <router-view />
  <ThemeToggleFAB v-if="!hideThemeToggle" />
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useTheme } from '@/composables/useTheme'
import ThemeToggleFAB from '@/components/ThemeToggleFAB.vue'

const route = useRoute()
const hideThemeToggle = computed(() => route.meta.hideThemeToggle === true)
const { initTheme } = useTheme()

onMounted(() => {
  initTheme()
})
</script>

<style >
:root {
  /* Brand Colors */
  --primary-color: #0E6008;
  --secondary-color: #049516;
  --tertiary-color: #9bc44c;
  --yellow-color: #ffd700;
  
  /* Light Theme Colors */
  --bg-color: #faf9f6;
  --bg-secondary: #f3f2ed;
  --surface-color: #ffffff;
  --surface-hover: #f1f5f9;
  --text-primary: #242424;
  --text-secondary: #475569;
  --border-color: #e2e8f0;
  --shadow-color: rgba(0, 0, 0, 0.05);
  
  /* Constant Helper Colors (always stay same) */
  --white-const: #ffffff;
  --black-const: #111827;
}

[data-theme="dark"] {
  /* Brand Colors - adjusted for a calm, premium mental wellness feel */
  --primary-color: #16a34a; /* Soft emerald */
  --secondary-color: #22c55e; /* Vibrant soft emerald */
  --tertiary-color: #86efac; /* Muted sage */
  --yellow-color: #fde047; /* Soft pastel yellow */
  
  /* Sleek Modern Dark Palette */
  --bg-color: #0b1120; /* Deep slate blue background */
  --bg-secondary: #111827; /* Rich dark surface */
  --surface-color: #1e293b; /* Slate-800 container surface */
  --surface-hover: #334155; /* Slate-700 hover state */
  --text-primary: #f8fafc; /* Slate-50 high contrast text */
  --text-secondary: #cbd5e1; /* Slate-300 helper text */
  --border-color: #334155; /* Slate-700 soft borders */
  --shadow-color: rgba(0, 0, 0, 0.4);
  
  /* Keep helper variables */
  --white-const: #ffffff;
  --black-const: #111827;
}

body {
  background-color: var(--bg-color);
  color: var(--text-primary);
  transition: background-color 0.3s ease, color 0.3s ease;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: Poppins, sans-serif;
}
</style>