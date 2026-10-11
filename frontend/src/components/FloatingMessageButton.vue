<template>
  <div v-if="isLoggedIn" class="floating-msg-wrapper">
    <button
      class="floating-msg-btn"
      :class="{ 'has-unread': totalUnreadCount > 0, 'is-active': isOpen }"
      @click="toggleDrawer"
      title="Direct Messages"
      aria-label="Direct Messages"
    >
      <i class="bx bx-message-square-dots icon-main"></i>
      <span v-if="totalUnreadCount > 0" class="msg-unread-badge">
        {{ totalUnreadCount > 99 ? '99+' : totalUnreadCount }}
      </span>
    </button>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { useDirectMessages } from '@/composables/useDirectMessages'

const { isOpen, totalUnreadCount, toggleDrawer, startPolling, stopPolling } = useDirectMessages()

const isLoggedIn = computed(() => !!localStorage.getItem('token'))

onMounted(() => {
  if (isLoggedIn.value) {
    startPolling()
  }
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.floating-msg-wrapper {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9990;
}

.floating-msg-btn {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0e6008 0%, #16a34a 100%);
  color: #ffffff;
  border: none;
  box-shadow: 0 8px 24px rgba(14, 96, 8, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  position: relative;
  transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s;
}

.floating-msg-btn:hover {
  transform: scale(1.08);
  box-shadow: 0 12px 30px rgba(14, 96, 8, 0.45);
}

.floating-msg-btn:active {
  transform: scale(0.96);
}

.icon-main {
  font-size: 26px;
}

.msg-unread-badge {
  position: absolute;
  top: -2px;
  right: -2px;
  background: #ef4444;
  color: #ffffff;
  font-size: 11px;
  font-weight: 700;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
  animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

[data-theme="dark"] .floating-msg-btn {
  background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
  box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
}

[data-theme="dark"] .msg-unread-badge {
  border-color: #1e293b;
}

@media (max-width: 640px) {
  .floating-msg-wrapper {
    bottom: 16px;
    right: 16px;
  }
  .floating-msg-btn {
    width: 50px;
    height: 50px;
  }
  .icon-main {
    font-size: 22px;
  }
}
</style>
