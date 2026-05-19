import { ref, watch, onMounted, computed } from 'vue'

const theme = ref('light')

export function useTheme() {
  const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
  }

  const applyTheme = (newTheme) => {
    document.documentElement.setAttribute('data-theme', newTheme)
    localStorage.setItem('theme', newTheme)
  }

  // Initialize theme
  const initTheme = () => {
    const savedTheme = localStorage.getItem('theme')
    if (savedTheme === 'light' || savedTheme === 'dark') {
      theme.value = savedTheme
    } else {
      // Check system preference
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      theme.value = prefersDark ? 'dark' : 'light'
    }
    applyTheme(theme.value)
  }

  // Watch for changes to apply theme attributes
  watch(theme, (newTheme) => {
    applyTheme(newTheme)
  })

  // Set up system preference listener on mount
  onMounted(() => {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    const handler = (e) => {
      if (!localStorage.getItem('theme')) {
        theme.value = e.matches ? 'dark' : 'light'
      }
    }
    
    // Support modern browsers and legacy ones
    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener('change', handler)
    } else if (mediaQuery.addListener) {
      mediaQuery.addListener(handler)
    }
  })

  return {
    theme,
    toggleTheme,
    initTheme,
    isDark: computed(() => theme.value === 'dark')
  }
}
