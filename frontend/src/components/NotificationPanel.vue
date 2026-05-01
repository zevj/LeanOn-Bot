<template>
  <div class="notif-wrapper" ref="wrapperRef">
    <div class="icon-btn" @click="togglePanel">
      <i class='bx bx-bell'></i>
      <span class="notif-dot" v-if="unreadCount > 0"></span>
    </div>

    <!-- Backdrop (mobile only, rendered via portal so it sits behind panel) -->
    <Teleport to="body">
      <div
        v-if="showPanel"
        class="notif-backdrop"
        @click="showPanel = false"
      />
    </Teleport>

    <transition name="notif-slide">
      <div class="notif-panel" v-if="showPanel">

        <div class="notif-header">
          <span class="notif-title">Notifications</span>
          <div class="header-actions">
            <button class="mark-all" @click="markAllRead">Mark all read</button>
          </div>
        </div>

        <div class="filter-tabs">
          <button :class="['filter-tab', { active: activeFilter === 'all' }]" @click="activeFilter = 'all'">
            All
            <span class="tab-count">{{ notifications.length }}</span>
          </button>
          <button :class="['filter-tab', { active: activeFilter === 'unread' }]" @click="activeFilter = 'unread'">
            Unread
            <span class="tab-count unread-count" v-if="unreadCount > 0">{{ unreadCount }}</span>
          </button>
        </div>

        <div class="notif-list">
          <template v-if="filteredNotifications.length > 0">
            <div
              class="notif-item"
              :class="{ unread: notif.unread, expanded: expandedId === notif.id }"
              v-for="notif in filteredNotifications"
              :key="notif.id"
              @click="toggleExpand(notif)"
            >
              <div class="notif-icon" :class="notif.color">
                <i :class="notif.icon"></i>
              </div>
              <div class="notif-body">
                <div class="notif-msg" v-html="notif.message"></div>
                <transition name="expand">
                  <div class="notif-detail" v-if="expandedId === notif.id && notif.detail">
                    {{ notif.detail }}
                  </div>
                </transition>
                <div class="notif-time">{{ notif.time }}</div>
              </div>
              <div class="notif-right">
                <span class="unread-dot" v-if="notif.unread"></span>
                <i class="bx chevron" :class="expandedId === notif.id ? 'bx-chevron-up' : 'bx-chevron-down'"></i>
              </div>
            </div>
          </template>

          <div class="notif-empty" v-else>
            <i class='bx bx-bell-off'></i>
            <p>No unread notifications</p>
          </div>
        </div>

      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const showPanel = ref(false)
const wrapperRef = ref(null)
const activeFilter = ref('all')
const expandedId = ref(null)

const notifications = ref([
  {
    id: 1, unread: true, color: 'green', icon: 'bx bx-user',
    message: '<strong>New user registered</strong> — Maria Santos joined as a regular user.',
    detail: 'Maria Santos signed up using a school email. Account is pending verification.',
    time: '2 minutes ago'
  },
  {
    id: 2, unread: true, color: 'amber', icon: 'bx bx-error-circle',
    message: '<strong>Bot response flagged</strong> — A user reported an inaccurate response in Session #4821.',
    detail: 'The response was related to anxiety management. Review the session log for details.',
    time: '15 minutes ago'
  },
  {
    id: 3, unread: true, color: 'blue', icon: 'bx bx-pulse',
    message: '<strong>System update available</strong> — LeanOnBot v2.4.1 is ready to deploy.',
    detail: 'This update includes bug fixes and improved response accuracy. Deployment takes ~2 minutes.',
    time: '1 hour ago'
  },
  {
    id: 4, unread: false, color: 'red', icon: 'bx bx-x-circle',
    message: '<strong>Session limit reached</strong> — Daily session cap hit for free-tier users.',
    detail: 'Free-tier users have reached their 10 sessions/day limit. Consider upgrading limits.',
    time: 'Yesterday, 4:30 PM'
  },
  {
    id: 5, unread: false, color: 'green', icon: 'bx bx-check-circle',
    message: '<strong>Backup completed</strong> — Database snapshot saved successfully.',
    detail: 'Snapshot size: 1.2 GB. Stored in primary backup server. Next backup in 24 hours.',
    time: 'Yesterday, 1:00 AM'
  }
])

const unreadCount = computed(() => notifications.value.filter(n => n.unread).length)

const filteredNotifications = computed(() =>
  activeFilter.value === 'unread'
    ? notifications.value.filter(n => n.unread)
    : notifications.value
)

function togglePanel() {
  showPanel.value = !showPanel.value
}

function toggleExpand(notif) {
  notif.unread = false
  expandedId.value = expandedId.value === notif.id ? null : notif.id
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
  transition: background 0.15s, color 0.15s;
  color: #0A9569;
  position: relative;
  font-size: 20px;
}
.icon-btn:hover { background-color: #0A9569; color: #ECFDF5; }

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
  border: 1px solid #ebebeb;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
  z-index: 200;
  overflow: hidden;
}

.notif-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px 10px;
  border-bottom: 1px solid #f5f5f5;
}
.notif-title { font-size: 13px; font-weight: 700; color: #111; }

.mark-all {
  font-size: 11px; font-weight: 600; color: #0E6008;
  padding: 3px 8px; border-radius: 6px;
  border: none; background: #f0f7ef; cursor: pointer;
  transition: background 0.15s;
}
.mark-all:hover { background: #e0f0dc; }

.filter-tabs {
  display: flex; gap: 4px;
  padding: 8px 12px;
  border-bottom: 1px solid #f5f5f5;
  background: #fafafa;
}

.filter-tab {
  display: flex; align-items: center; gap: 5px;
  padding: 5px 12px; border-radius: 8px;
  border: none; background: transparent;
  font-size: 12px; font-weight: 500; color: #888;
  cursor: pointer; transition: background 0.15s, color 0.15s;
}
.filter-tab:hover { color: #333; background: #f0f0f0; }
.filter-tab.active {
  background: #fff; color: #0E6008; font-weight: 600;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

.tab-count {
  font-size: 10.5px; font-weight: 600;
  background: #efefef; color: #777;
  border-radius: 10px; padding: 1px 6px;
  min-width: 18px; text-align: center;
}
.tab-count.unread-count { background: rgba(14,96,8,0.1); color: #0E6008; }

.notif-list { max-height: 320px; overflow-y: auto; }
.notif-list::-webkit-scrollbar { width: 3px; }
.notif-list::-webkit-scrollbar-thumb { background: #e8e8e8; border-radius: 4px; }

.notif-item {
  display: flex; align-items: flex-start; gap: 11px;
  padding: 12px 16px; cursor: pointer;
  transition: background 0.12s;
  border-bottom: 1px solid #f8f8f8;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: #fafafa; }
.notif-item.unread { background: #f7fbf6; }
.notif-item.expanded { background: #f4f9f3; }

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

.notif-body { flex: 1; min-width: 0; }
.notif-msg { font-size: 12.5px; color: #222; line-height: 1.45; }
.notif-msg :deep(strong) { font-weight: 600; }

.notif-detail {
  font-size: 12px; color: #666; line-height: 1.5;
  margin-top: 6px; padding: 8px 10px;
  background: #fff; border-radius: 8px;
  border: 1px solid #ebebeb; overflow: hidden;
}

.notif-time { font-size: 11px; color: #bbb; margin-top: 4px; }

.notif-right {
  display: flex; flex-direction: column;
  align-items: center; gap: 6px;
  flex-shrink: 0; padding-top: 2px;
}

.unread-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #0E6008;
}

.chevron {
  font-size: 14px; color: #ccc;
  transition: transform 0.2s ease, color 0.15s ease;
}
.notif-item.expanded .chevron { color: #0E6008; }

.notif-empty {
  display: flex; flex-direction: column;
  align-items: center; gap: 8px;
  padding: 32px 16px; color: #ccc;
}
.notif-empty i { font-size: 28px; }
.notif-empty p { font-size: 12.5px; font-weight: 500; color: #bbb; margin: 0; }

/* Backdrop — hidden on desktop, shown on mobile via media query */
.notif-backdrop { display: none; }

.expand-enter-active, .expand-leave-active {
  transition: max-height 0.25s ease, opacity 0.2s ease;
  max-height: 200px;
}
.expand-enter-from, .expand-leave-to { max-height: 0; opacity: 0; }

.notif-slide-enter-active,
.notif-slide-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.notif-slide-enter-from,
.notif-slide-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── Tablet (≤768px) ── */
@media (max-width: 768px) {
  .notif-panel { width: 300px; }

  .icon-btn {
    width: 40px;
    height: 40px;
    font-size: 18px;
  }
}

/* ── Small mobile (≤480px): fixed full-width panel ── */
@media (max-width: 480px) {
  .notif-wrapper { position: static; }
  .notif-panel {
    position: fixed;
    top: 62px;
    left: 12px;
    right: 12px;
    width: auto;
    max-height: calc(100dvh - 80px);
    border-radius: 14px;
    z-index: 99999;
  }

  .icon-btn {
    width: 36px;
    height: 36px;
    font-size: 16px;
    border-radius: 9px;
  }
  .notif-list { max-height: calc(100dvh - 230px); }
  .notif-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.35);
    z-index: 100;
  }
}

/* ── Very small (≤360px) ── */
@media (max-width: 360px) {
  .notif-item { padding: 10px 12px; gap: 8px; }
  .notif-icon { width: 28px; height: 28px; border-radius: 8px; }
  .notif-msg { font-size: 12px; }
  .notif-header { padding: 12px 12px 8px; }
  .filter-tabs { padding: 6px 8px; }
  .icon-btn {
    width: 34px;
    height: 34px;
    font-size: 15px;
    border-radius: 8px;
  }
}
</style>