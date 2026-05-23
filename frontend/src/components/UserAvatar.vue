<template>
  <div class="user-avatar-wrapper" :style="wrapperStyle" @click="$emit('click')">
    <!-- Actual image — hidden until it loads; on error shows initials -->
    <img
      v-if="src && !imgError"
      :src="src"
      :alt="name"
      class="user-avatar-img"
      :style="imgStyle"
      @error="imgError = true"
      @load="imgLoaded = true"
      crossorigin="anonymous"
    />
    <!-- Initials fallback -->
    <span v-if="!src || imgError" class="user-avatar-initials" :style="initialsStyle">
      {{ initials }}
    </span>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  /** Full URL of the profile image (optional) */
  src: {
    type: String,
    default: null
  },
  /** Full name used to derive initials and background color */
  name: {
    type: String,
    default: ''
  },
  /** Size in pixels */
  size: {
    type: Number,
    default: 84
  },
  /** Border color (CSS color string) */
  borderColor: {
    type: String,
    default: '#0E6008'
  },
  /** Border width in pixels */
  borderWidth: {
    type: Number,
    default: 2
  },
  /** Extra CSS class forwarded to the wrapper */
  rounded: {
    type: Boolean,
    default: true
  }
})

defineEmits(['click'])

const imgError = ref(false)
const imgLoaded = ref(false)

// ── Initials ──────────────────────────────────────────────────────────────────
const initials = computed(() => {
  const parts = (props.name || '').trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0][0].toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

// ── Deterministic color from name (same palette Google uses) ─────────────────
const PALETTE = [
  '#1a73e8', // blue
  '#e91e63', // pink
  '#9c27b0', // purple
  '#673ab7', // deep purple
  '#3f51b5', // indigo
  '#0097a7', // cyan
  '#00897b', // teal
  '#43a047', // green
  '#f57c00', // orange
  '#e53935', // red
  '#6d4c41', // brown
  '#546e7a', // blue-grey
]

const bgColor = computed(() => {
  if (!props.name) return '#9e9e9e'
  let hash = 0
  for (let i = 0; i < props.name.length; i++) {
    hash = props.name.charCodeAt(i) + ((hash << 5) - hash)
  }
  return PALETTE[Math.abs(hash) % PALETTE.length]
})

// ── Styles ────────────────────────────────────────────────────────────────────
const wrapperStyle = computed(() => ({
  width: `${props.size}px`,
  height: `${props.size}px`,
  borderRadius: props.rounded ? '50%' : '8px',
  overflow: 'hidden',
  display: 'inline-flex',
  alignItems: 'center',
  justifyContent: 'center',
  flexShrink: '0',
  backgroundColor: (!props.src || imgError.value) ? bgColor.value : 'transparent',
  cursor: 'inherit',
  position: 'relative',
  border: `${props.borderWidth}px solid ${props.borderColor}`,
  boxShadow: `0 0 0 5px ${props.borderColor}12`,
}))

const imgStyle = computed(() => ({
  width: '100%',
  height: '100%',
  objectFit: 'cover',
  display: 'block',
}))

const initialsStyle = computed(() => ({
  fontSize: `${Math.round(props.size * 0.38)}px`,
  fontWeight: '700',
  color: '#ffffff',
  userSelect: 'none',
  lineHeight: '1',
  letterSpacing: '0.5px',
}))
</script>

<style scoped>
.user-avatar-wrapper {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.user-avatar-wrapper:hover {
  opacity: 0.78;
  transform: scale(1.04);
}

.user-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
