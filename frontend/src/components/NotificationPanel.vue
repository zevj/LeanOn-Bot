<template>
  <div class="notif-wrapper" ref="wrapperRef">
    <div class="icon-btn" @click="togglePanel">
      <i class='bx bx-bell'></i>
      <span class="notif-dot" v-if="unreadCount > 0"></span>
    </div>

    <transition name="notif-slide">
      <div class="notif-panel" v-if="showPanel">
        <div class="notif-header">
          <span class="notif-title">Notifications</span>
          <button class="mark-all" @click="markAllRead">Mark all read</button>
        </div>

        <div class="notif-list">
          <div
            class="notif-item"
            :class="{ unread: notif.unread }"
            v-for="notif in notifications"
            :key="notif.id"
            @click="markRead(notif)"
          >
            <div class="notif-icon" :class="notif.color">
              <i :class="notif.icon"></i>
            </div>
            <div class="notif-body">
              <div class="notif-msg" v-html="notif.message"></div>
              <div class="notif-time">{{ notif.time }}</div>
            </div>
            <span class="unread-dot" v-if="notif.unread"></span>
          </div>
        </div>

        <div class="notif-footer" @click="$emit('view-all')">
          View all notifications →
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const showPanel = ref(false)
const wrapperRef = ref(null)

const notifications = ref([
  {
    id: 1, unread: true, color: 'green', icon: 'bx bx-user',
    message: '<strong>New user registered</strong> — Maria Santos joined as a regular user.',
    time: '2 minutes ago'
  },
  {
    id: 2, unread: true, color: 'amber', icon: 'bx bx-error-circle',
    message: '<strong>Bot response flagged</strong> — A user reported an inaccurate response in Session #4821.',
    time: '15 minutes ago'
  },
  {
    id: 3, unread: true, color: 'blue', icon: 'bx bx-pulse',
    message: '<strong>System update available</strong> — LeanOnBot v2.4.1 is ready to deploy.',
    time: '1 hour ago'
  },
  {
    id: 4, unread: false, color: 'red', icon: 'bx bx-x-circle',
    message: '<strong>Session limit reached</strong> — Daily session cap hit for free-tier users.',
    time: 'Yesterday, 4:30 PM'
  },
  {
    id: 5, unread: false, color: 'green', icon: 'bx bx-check-circle',
    message: '<strong>Backup completed</strong> — Database snapshot saved successfully.',
    time: 'Yesterday, 1:00 AM'
  }
])

const unreadCount = computed(() => notifications.value.filter(n => n.unread).length)

function togglePanel() {
  showPanel.value = !showPanel.value
}

function markRead(notif) {
  notif.unread = false
}

function markAllRead() {
  notifications.value.forEach(n => (n.unread = false))
}

function handleOutsideClick(e) {
  if (wrapperRef.value && !wrapperRef.value.contains(e.target)) {
    showPanel.value = false
  }
}

onMounted(() => document.addEventListener('click', handleOutsideClick))
onUnmounted(() => document.removeEventListener('click', handleOutsideClick))

defineEmits(['view-all'])
</script>

<style scoped>
.notif-wrapper { position: relative; }

.icon-btn {
  width: 45px; height: 45px;
  border-radius: 10px;
  border: 2px solid #0A9569;
  background: #ECFDF5;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: background 0.15s;
  color: #0A9569;
  position: relative;
  font-size: 20px;
}
.icon-btn:hover { 
    background-color: #0A9569; 
    color: #ECFDF5;
    }

.notif-dot {
  position: absolute;
  top: 7px; right: 7px;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #e24b4a;
  border: 1.5px solid #fff;
}

.notif-panel {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 340px;
  background: #fff;
  border: 0.5px solid #e0e0e0;
  border-radius: 14px;
  box-shadow: 0 8px 28px rgba(0,0,0,0.09);
  z-index: 200;
  overflow: hidden;
}

.notif-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px 10px;
  border-bottom: 0.5px solid #f0f0f0;
}
.notif-title { font-size: 13px; font-weight: 700; color: #111; }

.mark-all {
  font-size: 11px; font-weight: 600; color: #0E6008;
  padding: 3px 8px; border-radius: 6px;
  border: none; background: #f0f7ef; cursor: pointer;
  transition: background 0.15s;
}
.mark-all:hover { background: #e0f0dc; }

.notif-list { max-height: 300px; overflow-y: auto; }
.notif-list::-webkit-scrollbar { width: 4px; }
.notif-list::-webkit-scrollbar-thumb { background: #e0e0e0; border-radius: 4px; }

.notif-item {
  display: flex; align-items: flex-start; gap: 11px;
  padding: 12px 16px;
  cursor: pointer;
  transition: background 0.12s;
  border-bottom: 0.5px solid #f5f5f5;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: #fafafa; }
.notif-item.unread { background: #f7fbf6; }

.notif-icon {
  width: 34px; height: 34px; border-radius: 10px;
  flex-shrink: 0; display: flex; align-items: center; justify-content: center;
}
.notif-icon i { font-size: 16px; }
.notif-icon.green { background: #e8f5e6; }
.notif-icon.green i { color: #0E6008; }
.notif-icon.amber { background: #fff8e6; }
.notif-icon.amber i { color: #b97a00; }
.notif-icon.red { background: #fff0f0; }
.notif-icon.red i { color: #c0392b; }
.notif-icon.blue { background: #eaf3ff; }
.notif-icon.blue i { color: #1565c0; }

.notif-body { flex: 1; }
.notif-msg { font-size: 12.5px; color: #222; line-height: 1.45; }
.notif-msg :deep(strong) { font-weight: 600; }
.notif-time { font-size: 11px; color: #aaa; margin-top: 3px; }

.unread-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #0E6008; flex-shrink: 0; margin-top: 6px;
}

.notif-footer {
  padding: 10px 16px; text-align: center;
  border-top: 0.5px solid #f0f0f0;
  font-size: 12px; font-weight: 600; color: #0E6008;
  cursor: pointer; transition: background 0.12s;
}
.notif-footer:hover { background: #f7fbf6; }

.notif-slide-enter-active,
.notif-slide-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.notif-slide-enter-from,
.notif-slide-leave-to { opacity: 0; transform: translateY(-6px); }
</style>