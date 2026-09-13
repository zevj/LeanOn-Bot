<template>
  <div class="notif-wrapper" ref="wrapperRef">
    <div class="icon-btn" @click="togglePanel" aria-label="Notifications">
      <i class='bx bx-bell'></i>
      <span class="notif-dot" v-if="unreadCount > 0"></span>
    </div>

    <!-- Backdrop (mobile) -->
    <Teleport to="body">
      <div v-if="showPanel" class="notif-backdrop" @click="showPanel = false" />
    </Teleport>

    <transition name="notif-slide">
      <div class="notif-panel" v-if="showPanel">

        <div class="notif-header">
          <span class="notif-title">Notifications</span>
          <div class="notif-header-actions">
            <button class="refresh-btn" @click="refreshNotifications" :disabled="loading" title="Refresh">
              <i class="bx bx-refresh" :class="{ spinning: loading }"></i>
            </button>
            <button class="mark-all" @click="dismissAll" :disabled="notifications.length === 0 || allDismissed">
              Clear all
            </button>
          </div>
        </div>

        <!-- Unread filter -->
        <div class="notif-filter-tabs" v-if="!loading">
          <button
            class="filter-tab"
            :class="{ active: filterMode === 'all' }"
            @click="setFilter('all')"
          >
            All
          </button>
          <button
            class="filter-tab"
            :class="{ active: filterMode === 'unread' }"
            @click="setFilter('unread')"
          >
            Unread
            <span class="filter-count" v-if="unreadCount > 0">{{ unreadCount }}</span>
          </button>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="notif-list">
          <div class="notif-skeleton" v-for="i in 2" :key="i">
            <div class="skel-icon"></div>
            <div class="skel-body">
              <div class="skel-line wide"></div>
              <div class="skel-line short"></div>
            </div>
          </div>
        </div>

        <div v-else class="notif-list">
          <template v-if="filteredNotifications.length > 0">
            <div
              class="notif-item"
              :class="{ unread: !notif.dismissed }"
              v-for="notif in filteredNotifications"
              :key="notif.alert_id + '-' + (notif.appointment_status || 'email')"
            >
              <div class="notif-icon" :class="notif.appointment_status ? 'green' : 'blue'">
                <i :class="notif.appointment_status ? 'bx bx-calendar' : 'bx bx-envelope'"></i>
              </div>
              <div class="notif-body">
                <div class="notif-msg">
                  <strong v-if="notif.appointment_status === 'scheduled'">Appointment Scheduled</strong>
                  <strong v-else-if="notif.appointment_status === 'rescheduled'">Appointment Rescheduled</strong>
                  <strong v-else>Message from Guidance Office</strong>
                </div>
                <div class="notif-detail-text">
                  <template v-if="notif.appointment_status === 'scheduled'">
                    Your wellness appointment has been scheduled for {{ formatDate(notif.appointment_date) }} at {{ formatAppointmentTime(notif.appointment_time) }}. Please check your email.
                  </template>
                  <template v-else-if="notif.appointment_status === 'rescheduled'">
                    Your wellness appointment has been rescheduled to {{ formatDate(notif.appointment_date) }} at {{ formatAppointmentTime(notif.appointment_time) }}. Please check your email.
                  </template>
                  <template v-else>
                    A support email was sent to your registered email address. Please check your inbox.
                  </template>
                </div>
                <div class="notif-time">{{ formatTime(notif.sent_at) }}</div>
              </div>
              <div class="notif-right">
                <span class="unread-dot" v-if="!notif.dismissed"></span>
                <button class="dismiss-btn" @click.stop="dismiss(notif)" title="Dismiss">
                  <i class="bx bx-x"></i>
                </button>
              </div>
            </div>
          </template>

          <div class="notif-empty" v-else>
            <i class='bx bx-bell-off'></i>
            <p>{{ filterMode === 'unread' ? 'No unread notifications' : 'No notifications yet' }}</p>
          </div>
        </div>

      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const showPanel  = ref(false)
const wrapperRef = ref(null)
const loading    = ref(false)
const notifications = ref([]) // array of { alert_id, sent_at, dismissed }

/* NOTIF FILTER */
const unreadCount = computed(() => notifications.value.filter(n => !n.dismissed).length)
const allDismissed = computed(() => notifications.value.every(n => n.dismissed))

// ── Unread filter ────────────────────────────────────────────
const filterMode = ref('all') // 'all' | 'unread'

const filteredNotifications = computed(() =>
  filterMode.value === 'unread'
    ? notifications.value.filter(n => !n.dismissed)
    : notifications.value
)

const setFilter = (mode) => {
  filterMode.value = mode
}

// ── Manual refresh ───────────────────────────────────────────
const refreshNotifications = async () => {
  await fetchNotifications()
}

const authHeaders = () => ({
  headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
})

// ── Fetch pending notifications ────────────────────────────────
const fetchNotifications = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/user/admin-email-notification', authHeaders())
    if (res.data.notification) {
      const incoming = res.data.notification
      const existingIndex = notifications.value.findIndex(n => n.alert_id === incoming.alert_id)
      if (existingIndex !== -1) {
        if (notifications.value[existingIndex].sent_at !== incoming.sent_at) {
          notifications.value[existingIndex] = { ...incoming, dismissed: false }
        }
      } else {
        notifications.value.unshift({ ...incoming, dismissed: false })
      }
    }
  } catch {
    // non-critical
  } finally {
    loading.value = false
  }
}

// ── Dismiss single ─────────────────────────────────────────────
const dismiss = async (notif) => {
  notif.dismissed = true // optimistic
  try {
    await axios.post(
      `/api/user/admin-email-notification/${notif.alert_id}/dismiss`,
      {},
      authHeaders()
    )
    // Remove from list after short delay so user sees the change
    setTimeout(() => {
      notifications.value = notifications.value.filter(n => n.alert_id !== notif.alert_id)
    }, 400)
  } catch {
    notif.dismissed = false
  }
}

// ── Dismiss all ────────────────────────────────────────────────
const dismissAll = async () => {
  const pending = notifications.value.filter(n => !n.dismissed)
  pending.forEach(n => (n.dismissed = true))
  await Promise.allSettled(
    pending.map(n =>
      axios.post(`/api/user/admin-email-notification/${n.alert_id}/dismiss`, {}, authHeaders())
    )
  )
  setTimeout(() => { notifications.value = [] }, 400)
}

// ── Toggle ─────────────────────────────────────────────────────
const togglePanel = () => {
  showPanel.value = !showPanel.value
  if (showPanel.value) fetchNotifications()
}

// ── Format time ────────────────────────────────────────────────
const formatTime = (iso) => {
  if (!iso) return ''
  const d = new Date(iso)
  if (isNaN(d)) return ''
  return d.toLocaleDateString('en-PH', {
    month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit', hour12: true,
  })
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  if (isNaN(d)) return dateStr
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatAppointmentTime = (timeStr) => {
  if (!timeStr) return ''
  try {
    const [hours, minutes] = timeStr.split(':')
    const h = parseInt(hours, 10)
    const ampm = h >= 12 ? 'PM' : 'AM'
    const h12 = h % 12 || 12
    return `${h12}:${minutes} ${ampm}`
  } catch {
    return timeStr
  }
}

// ── Outside click ──────────────────────────────────────────────
const handleOutsideClick = (e) => {
  if (wrapperRef.value && !wrapperRef.value.contains(e.target)) {
    showPanel.value = false
  }
}

// ── Poll every 30 s ────────────────────────────────────────────
let pollTimer = null

onMounted(() => {
  document.addEventListener('click', handleOutsideClick)
  fetchNotifications()
  pollTimer = setInterval(fetchNotifications, 30_000)
})

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick)
  clearInterval(pollTimer)
})
</script>

<style scoped>
.notif-wrapper { position: relative; }

/* ── Bell button ── */
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
  background: #2563eb;
  border: 1.5px solid #fff;
}

/* ── Panel ── */
.notif-panel {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 320px;
  background: #fff;
  border: 1px solid #ebebeb;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
  z-index: 200;
  overflow: hidden;
}

/* ── Header ── */
.notif-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px 10px;
  border-bottom: 1px solid #f5f5f5;
}
.notif-title { font-size: 13px; font-weight: 700; color: #111; }

.mark-all {
  font-size: 11px; font-weight: 600; color: #2563eb;
  padding: 3px 8px; border-radius: 6px;
  border: none; background: #eff6ff; cursor: pointer;
  transition: background 0.15s;
}
.mark-all:hover:not(:disabled) { background: #dbeafe; }
.mark-all:disabled { opacity: 0.4; cursor: default; }

/* ── List ── */
.notif-list { max-height: 300px; overflow-y: auto; }
.notif-list::-webkit-scrollbar { width: 3px; }
.notif-list::-webkit-scrollbar-thumb { background: #e8e8e8; border-radius: 4px; }

/* ── Header actions / refresh ── */
.notif-header-actions { display: flex; align-items: center; gap: 8px; }

.refresh-btn {
  display: flex; align-items: center; justify-content: center;
  width: 22px; height: 22px;
  border: none; background: none; cursor: pointer;
  color: #94a3b8; font-size: 15px; padding: 0;
  transition: color 0.15s;
}
.refresh-btn:hover:not(:disabled) { color: #2563eb; }
.refresh-btn:disabled { cursor: default; opacity: 0.6; }
.refresh-btn i.spinning { animation: notif-spin 0.7s linear infinite; }
@keyframes notif-spin { to { transform: rotate(360deg); } }

/* ── Filter tabs ── */
.notif-filter-tabs {
  display: flex; gap: 6px;
  padding: 8px 16px 10px;
  border-bottom: 1px solid #f5f5f5;
}
.filter-tab {
  display: flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 600; color: #888;
  padding: 4px 10px; border-radius: 20px;
  border: none; background: #f5f5f5; cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.filter-tab:hover { background: #ececec; }
.filter-tab.active { background: #eff6ff; color: #2563eb; }
.filter-count {
  font-size: 10px; font-weight: 700;
  background: #2563eb; color: #fff;
  border-radius: 10px; padding: 1px 5px;
  line-height: 1.3;
}
.filter-tab.active .filter-count { background: #2563eb; }

/* ── Dark Mode: header actions / filter tabs ── */
:global([data-theme="dark"]) .refresh-btn { color: #64748b; }
:global([data-theme="dark"]) .refresh-btn:hover:not(:disabled) { color: #60a5fa; }
:global([data-theme="dark"]) .notif-filter-tabs { border-bottom-color: #334155; }
:global([data-theme="dark"]) .filter-tab { background: #263548; color: #94a3b8; }
:global([data-theme="dark"]) .filter-tab:hover { background: #2d3f56; }
:global([data-theme="dark"]) .filter-tab.active { background: #1e3a5f; color: #60a5fa; }

/* ── Skeleton ── */
.notif-skeleton {
  display: flex; align-items: flex-start; gap: 11px;
  padding: 14px 16px;
  border-bottom: 1px solid #f8f8f8;
}
.skel-icon {
  width: 34px; height: 34px; border-radius: 10px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
  flex-shrink: 0;
}
.skel-body { flex: 1; display: flex; flex-direction: column; gap: 8px; }
.skel-line {
  height: 11px; border-radius: 6px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
.skel-line.wide  { width: 85%; }
.skel-line.short { width: 45%; }
@keyframes shimmer {
  0%   { background-position: -200% 0; }
  100% { background-position:  200% 0; }
}

/* ── Item ── */
.notif-item {
  display: flex; align-items: flex-start; gap: 11px;
  padding: 12px 16px;
  border-bottom: 1px solid #f8f8f8;
  transition: background 0.12s;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: #fafafa; }
.notif-item.unread { background: #eff6ff; }

/* ── Icon ── */
.notif-icon {
  width: 34px; height: 34px; border-radius: 10px;
  flex-shrink: 0; display: flex; align-items: center; justify-content: center;
}
.notif-icon i { font-size: 16px; }
.notif-icon.blue { background: #dbeafe; }
.notif-icon.blue i { color: #1d4ed8; }

.notif-icon.green { background: #dcfce7; }
.notif-icon.green i { color: #16a34a; }

/* ── Body ── */
.notif-body { flex: 1; min-width: 0; }
.notif-msg { font-size: 12.5px; color: #222; line-height: 1.45; }
.notif-msg strong { font-weight: 600; }
.notif-detail-text {
  font-size: 12px; color: #555; line-height: 1.45;
  margin-top: 3px;
}
.notif-time { font-size: 11px; color: #bbb; margin-top: 4px; }

/* ── Right ── */
.notif-right {
  display: flex; flex-direction: column;
  align-items: center; gap: 4px;
  flex-shrink: 0; padding-top: 2px;
}
.unread-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: #2563eb;
}
.dismiss-btn {
  background: none; border: none; cursor: pointer;
  color: #bbb; font-size: 18px; padding: 0; line-height: 1;
  transition: color 0.15s;
}
.dismiss-btn:hover { color: #ef4444; }

/* ── Empty ── */
.notif-empty {
  display: flex; flex-direction: column;
  align-items: center; gap: 8px;
  padding: 32px 16px; color: #ccc;
}
.notif-empty i { font-size: 28px; }
.notif-empty p { font-size: 12.5px; font-weight: 500; color: #bbb; margin: 0; }

/* ── Backdrop (mobile) ── */
.notif-backdrop { display: none; }

/* ── Transitions ── */
.notif-slide-enter-active,
.notif-slide-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.notif-slide-enter-from,
.notif-slide-leave-to { opacity: 0; transform: translateY(-6px); }

@media (max-width: 480px) {
  .notif-wrapper { position: static; }
  .notif-panel {
    position: fixed;
    top: 62px; left: 12px; right: 12px;
    width: auto;
    border-radius: 14px;
    z-index: 99999;
  }
  .notif-backdrop {
    display: block;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.35);
    z-index: 100;
  }
}

/* ── Dark Mode ── */
:global([data-theme="dark"]) .icon-btn {
  background: #1a2e1a;
  border-color: #2d6a2d;
  color: #4ade80;
}
:global([data-theme="dark"]) .icon-btn:hover {
  background: #2d6a2d;
  color: #f0fdf4;
}
:global([data-theme="dark"]) .notif-dot {
  background: #3b82f6;
  border-color: #1e293b;
}
:global([data-theme="dark"]) .notif-panel {
  background: #1e293b;
  border-color: #334155;
  box-shadow: 0 8px 32px rgba(0,0,0,0.4);
}
:global([data-theme="dark"]) .notif-header {
  border-bottom-color: #334155;
}
:global([data-theme="dark"]) .notif-title {
  color: #f1f5f9;
}
:global([data-theme="dark"]) .mark-all {
  background: #1e3a5f;
  color: #60a5fa;
}
:global([data-theme="dark"]) .mark-all:hover:not(:disabled) {
  background: #1d4ed8;
  color: #fff;
}
:global([data-theme="dark"]) .notif-list::-webkit-scrollbar-thumb {
  background: #334155;
}
:global([data-theme="dark"]) .notif-skeleton {
  border-bottom-color: #334155;
}
:global([data-theme="dark"]) .skel-icon,
:global([data-theme="dark"]) .skel-line {
  background: linear-gradient(90deg, #334155 25%, #3d4f63 50%, #334155 75%);
  background-size: 200% 100%;
}
:global([data-theme="dark"]) .notif-item {
  border-bottom-color: #334155;
}
:global([data-theme="dark"]) .notif-item:hover {
  background: #263548;
}
:global([data-theme="dark"]) .notif-item.unread {
  background: #1e3a5f;
}
:global([data-theme="dark"]) .notif-icon.blue {
  background: #1e3a5f;
}
:global([data-theme="dark"]) .notif-icon.blue i {
  color: #60a5fa;
}
:global([data-theme="dark"]) .notif-icon.green {
  background: #1a2e1a;
}
:global([data-theme="dark"]) .notif-icon.green i {
  color: #4ade80;
}
:global([data-theme="dark"]) .notif-msg {
  color: #e2e8f0;
}
:global([data-theme="dark"]) .notif-detail-text {
  color: #94a3b8;
}
:global([data-theme="dark"]) .notif-time {
  color: #64748b;
}
:global([data-theme="dark"]) .unread-dot {
  background: #3b82f6;
}
:global([data-theme="dark"]) .dismiss-btn {
  color: #475569;
}
:global([data-theme="dark"]) .dismiss-btn:hover {
  color: #f87171;
}
:global([data-theme="dark"]) .notif-empty {
  color: #475569;
}
:global([data-theme="dark"]) .notif-empty p {
  color: #475569;
}
</style>