<template>
  <div class="layout">
    <!-- Sidebar -->
    <SidebarAdmin
      :open="sidebarOpen"
      @toggle="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)"
    />

    <main class="main-area">
      <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)" />

      <div class="main-container">
        <!-- HEADER -->
        <div class="page-header-wrapper fade-in">
          <div class="header-title">
            <h1 class="title">Appointments</h1>
            <p class="subtext">Track and manage counseling appointments scheduled from student crisis alerts.</p>
          </div>
          <div class="header-datetime">
            <span class="header-day">{{ currentDay }}</span>
            <span class="header-date"><i class="bx bx-calendar"></i> {{ currentDate }}</span>
            <span class="header-time"><i class="bx bx-time-five"></i> {{ currentTime }}</span>
          </div>
        </div>

        <!-- STATS CARDS -->
        <div class="audit-stats fade-in stagger-1">
          <div
            class="stat-card sc-depts animate-card stagger-1"
            :class="{ 'stat-active': !statusFilter }"
            style="cursor: pointer;"
            @click="toggleStatusFilter('')"
          >
            <div class="sc-left">
              <div class="sc-label">Total Scheduled</div>
              <div class="sc-val">{{ totalScheduled }}</div>
            </div>
            <div class="sc-icon">
              <i class="bx bx-calendar"></i>
            </div>
          </div>
          <div
            class="stat-card sc-total animate-card stagger-2"
            :class="{ 'stat-active': statusFilter === 'new' }"
            style="cursor: pointer;"
            @click="toggleStatusFilter('new')"
          >
            <div class="sc-left">
              <div class="sc-label">New</div>
              <div class="sc-val">{{ newCount }}</div>
            </div>
            <div class="sc-icon">
              <i class="bx bx-error-circle"></i>
            </div>
          </div>
          <div
            class="stat-card sc-active animate-card stagger-3"
            :class="{ 'stat-active': statusFilter === 'reviewed' }"
            style="cursor: pointer;"
            @click="toggleStatusFilter('reviewed')"
          >
            <div class="sc-left">
              <div class="sc-label">Under Review</div>
              <div class="sc-val">{{ reviewedCount }}</div>
            </div>
            <div class="sc-icon">
              <i class="bx bx-search-alt"></i>
            </div>
          </div>
          <div
            class="stat-card sc-closed animate-card stagger-4"
            :class="{ 'stat-active': statusFilter === 'resolved' }"
            style="cursor: pointer;"
            @click="toggleStatusFilter('resolved')"
          >
            <div class="sc-left">
              <div class="sc-label">Resolved</div>
              <div class="sc-val">{{ resolvedCount }}</div>
            </div>
            <div class="sc-icon">
              <i class="bx bx-check-circle"></i>
            </div>
          </div>
        </div>

        <!-- CONTROLS / FILTERS -->
        <div class="log-controls animate-card stagger-5">
          <div class="search-wrapper">
            <i class="bx bx-search search-icon"></i>
            <input
              v-model="search"
              type="text"
              placeholder="Search by ID, name, email, or message..."
            />
          </div>
          <div class="filters-wrapper">
            <div class="select-box">
              <select v-model="severityFilter">
                <option value="">All Severities</option>
                <option value="severe">Severe</option>
                <option value="moderate">Moderate</option>
                <option value="low">Low</option>
              </select>
            </div>
            <div class="select-box">
              <select v-model="statusFilter">
                <option value="">All Statuses</option>
                <option value="new">New</option>
                <option value="reviewed">Under review</option>
                <option value="resolved">Resolved</option>
              </select>
            </div>
          </div>
        </div>

        <!-- APPOINTMENTS DATA TABLE -->
        <div class="log-table-wrap animate-card stagger-5">
          <table class="log-table" v-if="filteredAppointments.length > 0 && !loading">
            <thead>
              <tr>
                <th style="width: 15%">Date & Time</th>
                <th style="width: 25%">Student Details</th>
                <th style="width: 25%">Flagged Message</th>
                <th style="width: 10%">Severity</th>
                <th style="width: 10%">Status</th>
                <th style="width: 15%">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(appt, index) in filteredAppointments"
                :key="appt.id"
                class="fade-in-row alert-row"
                :class="`row-${appt.severity}`"
                :style="{ animationDelay: `${0.1 + (index * 0.04)}s` }"
              >
                <!-- Date & Time -->
                <td data-label="Date & Time">
                  <div class="appt-time-cell">
                    <span class="appt-date"><i class="bx bx-calendar"></i> {{ formatDate(appt.appointment_date) }}</span>
                    <span class="appt-time"><i class="bx bx-time-five"></i> {{ formatTime(appt.appointment_time) }}</span>
                  </div>
                </td>

                <!-- Student Details -->
                <td data-label="Student Details">
                  <div class="student-details-cell">
                    <span class="student-id">{{ appt.user_display }}</span>
                    <span class="student-name">
                      {{ revealedDetails.has(appt.id) ? appt.student_name : 'Anonymized Student' }}
                    </span>
                    <span class="student-email">
                      {{ revealedDetails.has(appt.id) ? appt.real_email : appt.masked_email }}
                      <button
                        class="reveal-btn"
                        @click="toggleDetails(appt.id)"
                        :title="revealedDetails.has(appt.id) ? 'Hide details' : 'Show details'"
                      >
                        <i :class="revealedDetails.has(appt.id) ? 'bx bx-hide' : 'bx bx-show'"></i>
                      </button>
                    </span>
                  </div>
                </td>

                <!-- Flagged Message -->
                <td data-label="Flagged Message">
                  <div class="flagged-message-cell">
                    <span class="message-text">"{{ appt.message }}"</span>
                    <span v-if="appt.flag_reason" class="flag-reason-badge">
                      <i class="bx bx-flag"></i> {{ appt.flag_reason }}
                    </span>
                  </div>
                </td>

                <!-- Severity -->
                <td data-label="Severity">
                  <span class="badge" :class="`b-${appt.severity}`">
                    <i v-if="appt.severity === 'severe'" class="bx bxs-bell-ring"></i>
                    <i v-else-if="appt.severity === 'moderate'" class="bx bx-info-circle"></i>
                    <i v-else class="bx bx-check-shield"></i>
                    {{ capitalize(appt.severity) }}
                  </span>
                </td>

                <!-- Status -->
                <td data-label="Status">
                  <span class="badge" :class="`b-${appt.status}`">
                    <i v-if="appt.status === 'new'" class="bx bx-error-circle"></i>
                    <i v-else-if="appt.status === 'reviewed'" class="bx bx-search-alt"></i>
                    <i v-else-if="appt.status === 'resolved'" class="bx bx-check-circle"></i>
                    {{ appt.status === 'reviewed' ? 'Under review' : capitalize(appt.status) }}
                  </span>
                </td>

                <!-- Actions -->
                <td data-label="Actions">
                  <div class="actions-cell">
                    <button class="action-btn action-btn--edit" title="Reschedule" @click="openRescheduleModal(appt)" :disabled="appt.status === 'resolved'">
                      <i class="bx bx-edit-alt"></i> Reschedule
                    </button>
                    <button class="action-btn action-btn--resolve" title="Resolve Alert" @click="openResolveModal(appt)" :disabled="appt.status === 'resolved'">
                      <i class="bx bx-check-shield"></i> Resolve
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-else-if="loading" class="log-empty">
            <div class="spinner"></div> <span>Syncing appointments...</span>
          </div>

          <div v-else class="log-empty">
            <div class="empty-state-content">
              <i class="bx bx-calendar-event empty-icon"></i>
              <h3>No appointments scheduled</h3>
              <p v-if="search || severityFilter || statusFilter">Try adjusting your filters or search queries.</p>
              <p v-else>Students with scheduled appointments will show up here.</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Reschedule Modal -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div v-if="rescheduleModal.visible" class="email-modal-overlay" @click.self="closeRescheduleModal">
          <div class="email-modal">
            <div class="email-modal-header">
              <div class="email-modal-header-left">
                <div class="email-modal-icon"><i class="bx bx-calendar"></i></div>
                <div>
                  <p class="email-modal-title">Reschedule Appointment</p>
                  <p class="email-modal-subtitle">Pick a new date and time for {{ rescheduleModal.appt?.user_display }}</p>
                </div>
              </div>
              <button class="email-modal-close" @click="closeRescheduleModal">
                <i class="bx bx-x"></i>
              </button>
            </div>
            <div class="email-modal-body">
              <div class="email-field-group">
                <span class="email-field-label">Date</span>
                <input type="date" v-model="rescheduleModal.date" class="modal-input" :min="todayDate" />
              </div>
              <div class="email-field-group">
                <span class="email-field-label">Time</span>
                <input type="time" v-model="rescheduleModal.time" class="modal-input" />
              </div>
            </div>
            <div class="email-modal-footer">
              <button class="action-btn" @click="closeRescheduleModal">Cancel</button>
              <button class="action-btn action-btn--confirm-reschedule" @click="saveReschedule" :disabled="saving">
                <i class="bx bx-save"></i> {{ saving ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Resolve Confirmation Modal -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div v-if="resolveModal.visible" class="email-modal-overlay" @click.self="closeResolveModal">
          <div class="email-modal resolve-modal">
            <div class="email-modal-header">
              <div class="email-modal-header-left">
                <div class="email-modal-icon icon-resolve"><i class="bx bx-check-shield"></i></div>
                <div>
                  <p class="email-modal-title">Resolve Alert</p>
                  <p class="email-modal-subtitle">Confirm resolution status</p>
                </div>
              </div>
              <button class="email-modal-close" @click="closeResolveModal">
                <i class="bx bx-x"></i>
              </button>
            </div>
            <div class="email-modal-body resolve-body">
              <div class="resolve-icon-large"><i class="bx bx-check-circle"></i></div>
              <p class="resolve-text">
                Are you sure you want to mark the alert for
                <strong>{{ resolveModal.appt?.user_display }}</strong> as resolved?
              </p>
              <p class="resolve-subtext">This action indicates that the crisis has been properly addressed and handled.</p>
            </div>
            <div class="email-modal-footer">
              <button class="action-btn" @click="closeResolveModal">Cancel</button>
              <button class="action-btn action-btn--confirm-resolve" @click="executeResolve" :disabled="saving">
                <i class="bx bx-check"></i> {{ saving ? 'Resolving...' : 'Confirm Resolution' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import SidebarAdmin from '@/components/sidebarAdmin.vue';
import HeaderAdmin from '@/components/headerAdmin.vue';

const toast = useToast();
const router = useRouter();

const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false');
const loading = ref(false);
const saving = ref(false);

const now = ref(new Date());
let clockInterval = null;

const currentDay = computed(() =>
  now.value.toLocaleDateString('en-PH', { weekday: 'long' })
);
const currentDate = computed(() =>
  now.value.toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' })
);
const currentTime = computed(() =>
  now.value.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true })
);

const appointments = ref([]);
const search = ref('');
const severityFilter = ref('');
const statusFilter = ref('');

const revealedDetails = ref(new Set());
const toggleDetails = (id) => {
  const updated = new Set(revealedDetails.value);
  updated.has(id) ? updated.delete(id) : updated.add(id);
  revealedDetails.value = updated;
};

const fetchAppointments = async () => {
  loading.value = true;
  try {
    const token = localStorage.getItem('token');
    const res = await axios.get('/api/admin/appointments', {
      headers: { Authorization: `Bearer ${token}` }
    });
    appointments.value = res.data;
  } catch (err) {
    console.error('Failed to fetch appointments:', err);
    toast.error('Failed to load appointments.');
  } finally {
    loading.value = false;
  }
};

const totalScheduled = computed(() => appointments.value.length);
const newCount = computed(() => appointments.value.filter(a => a.status === 'new').length);
const reviewedCount = computed(() => appointments.value.filter(a => a.status === 'reviewed').length);
const resolvedCount = computed(() => appointments.value.filter(a => a.status === 'resolved').length);

const toggleStatusFilter = (status) => {
  statusFilter.value = statusFilter.value === status ? '' : status;
};

const filteredAppointments = computed(() => {
  const q = search.value.trim().toLowerCase();
  return appointments.value.filter(appt => {
    // Search filter
    const matchesSearch = !q ||
      (appt.user_display ?? '').toLowerCase().includes(q) ||
      (appt.student_name ?? '').toLowerCase().includes(q) ||
      (appt.real_email ?? '').toLowerCase().includes(q) ||
      (appt.masked_email ?? '').toLowerCase().includes(q) ||
      (appt.message ?? '').toLowerCase().includes(q) ||
      (appt.flag_reason ?? '').toLowerCase().includes(q);

    // Severity filter
    const matchesSeverity = !severityFilter.value || appt.severity === severityFilter.value;

    // Status filter
    const matchesStatus = !statusFilter.value || appt.status === statusFilter.value;

    return matchesSearch && matchesSeverity && matchesStatus;
  });
});

// Format functions
const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' });
};

const formatTime = (timeStr) => {
  if (!timeStr) return 'N/A';
  try {
    const [hours, minutes] = timeStr.split(':');
    const h = parseInt(hours, 10);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${minutes} ${ampm}`;
  } catch (e) {
    return timeStr;
  }
};

const capitalize = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1) : '';

// Actions
const goToCrisisAlert = (appt) => {
  router.push('/AdminCrisisAlerts');
};

// Reschedule
const rescheduleModal = ref({ visible: false, appt: null, date: '', time: '' });
const todayDate = computed(() => new Date().toISOString().split('T')[0]);

const openRescheduleModal = (appt) => {
  rescheduleModal.value = {
    visible: true,
    appt,
    date: appt.appointment_date || '',
    time: appt.appointment_time || ''
  };
};

const closeRescheduleModal = () => {
  rescheduleModal.value = { visible: false, appt: null, date: '', time: '' };
};

const saveReschedule = async () => {
  if (!rescheduleModal.value.date || !rescheduleModal.value.time) {
    toast.warning('Please select both date and time.');
    return;
  }
  saving.value = true;
  try {
    const token = localStorage.getItem('token');
    await axios.patch(`/api/admin/crisis-alerts/${rescheduleModal.value.appt.id}`, {
      appointment_date: rescheduleModal.value.date,
      appointment_time: rescheduleModal.value.time
    }, {
      headers: { Authorization: `Bearer ${token}` }
    });
    toast.success(`Rescheduled appointment for ${rescheduleModal.value.appt.user_display}`);
    closeRescheduleModal();
    fetchAppointments();
  } catch (err) {
    console.error('Failed to reschedule:', err);
    toast.error('Failed to reschedule appointment.');
  } finally {
    saving.value = false;
  }
};

// Resolve
const resolveModal = ref({ visible: false, appt: null });

const openResolveModal = (appt) => {
  resolveModal.value = { visible: true, appt };
};

const closeResolveModal = () => {
  resolveModal.value.visible = false;
  setTimeout(() => { if (!resolveModal.value.visible) resolveModal.value.appt = null; }, 200);
};

const executeResolve = async () => {
  if (!resolveModal.value.appt) return;
  saving.value = true;
  try {
    const token = localStorage.getItem('token');
    await axios.patch(`/api/admin/crisis-alerts/${resolveModal.value.appt.id}`, {
      status: 'resolved'
    }, {
      headers: { Authorization: `Bearer ${token}` }
    });
    toast.success(`Alert resolved for ${resolveModal.value.appt.user_display}`);
    closeResolveModal();
    fetchAppointments();
  } catch (err) {
    console.error('Failed to resolve alert:', err);
    toast.error('Failed to resolve alert.');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchAppointments();
  clockInterval = setInterval(() => {
    now.value = new Date();
  }, 1000);
});

onUnmounted(() => {
  if (clockInterval) clearInterval(clockInterval);
});
</script>

<style scoped src="@/assets/admin/AdminLogRecords.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>

<style scoped>
.header-datetime {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
  padding: 10px 16px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 12px;
  min-width: 180px;
}

.header-day {
  font-size: 15px;
  font-weight: 700;
  color: #0e6008;
  letter-spacing: 0.02em;
}

.header-date,
.header-time {
  font-size: 13px;
  font-weight: 500;
  color: #374151;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.header-date i,
.header-time i {
  color: #0e6008;
  font-size: 15px;
}

.stat-card.stat-active {
  border-color: #86efac;
  box-shadow: 0 0 0 2px rgba(14, 96, 8, 0.12), 0 8px 16px -6px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

[data-theme="dark"] .header-datetime {
  background: #15231c;
  border-color: #1f3d2a;
}

[data-theme="dark"] .header-day {
  color: #4ade80;
}

[data-theme="dark"] .header-date,
[data-theme="dark"] .header-time {
  color: #d1d5db;
}

[data-theme="dark"] .header-date i,
[data-theme="dark"] .header-time i {
  color: #4ade80;
}

[data-theme="dark"] .stat-card.stat-active {
  border-color: #166534;
  box-shadow: 0 0 0 2px rgba(74, 222, 128, 0.15), 0 8px 16px -6px rgba(0, 0, 0, 0.35);
}

/* ─────────────────────────────────────────────────────────────
   Modal Overlay & Box
───────────────────────────────────────────────────────────── */
.email-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.email-modal {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  width: 520px;
  max-width: 90vw;
  max-height: 88vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
}

.email-modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
  background: #fafafa;
}

.email-modal-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.email-modal-icon {
  width: 38px;
  height: 38px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0e6008;
  font-size: 18px;
  flex-shrink: 0;
}

.email-modal-title    { font-size: 16px; font-weight: 600; color: #111827; margin: 0; }
.email-modal-subtitle { font-size: 12.5px; color: #6b7280; margin: 2px 0 0; }

.email-modal-close {
  width: 32px;
  height: 32px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  font-size: 20px;
  flex-shrink: 0;
  transition: all 0.2s;
}
.email-modal-close:hover { background: #f3f4f6; color: #111827; }

.email-modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 16px;
  overflow-y: auto;
  flex: 1;
}

.email-field-group  { display: flex; flex-direction: column; gap: 6px; }
.email-field-label  {
  font-size: 11.5px;
  font-weight: 600;
  color: #4b5563;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.email-modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-shrink: 0;
  background: #fafafa;
}

/* ─────────────────────────────────────────────────────────────
   Base action-btn (needed here because AdminLogRecords.css
   may not define all variants we use in the modals)
───────────────────────────────────────────────────────────── */
.action-btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border: 1px solid #d1d5db;
  background: #ffffff;
  color: #374151;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: 'DM Sans', system-ui, sans-serif;
}
.action-btn:hover {
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  transform: translateY(-1px);
}
.action-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* ─────────────────────────────────────────────────────────────
   Modal Animations
───────────────────────────────────────────────────────────── */
.modal-fade-enter-active { transition: opacity 0.22s ease; }
.modal-fade-leave-active { transition: opacity 0.18s ease; }
.modal-fade-enter-from,
.modal-fade-leave-to     { opacity: 0; }

.modal-fade-enter-active .email-modal {
  animation: modal-pop-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
.modal-fade-leave-active .email-modal {
  animation: modal-pop-out 0.2s ease forwards;
}

@keyframes modal-pop-in {
  from { opacity: 0; transform: scale(0.95) translateY(15px); }
  to   { opacity: 1; transform: scale(1)    translateY(0); }
}
@keyframes modal-pop-out {
  from { opacity: 1; transform: scale(1)    translateY(0); }
  to   { opacity: 0; transform: scale(0.97) translateY(10px); }
}

/* ─────────────────────────────────────────────────────────────
   Dark mode modal
───────────────────────────────────────────────────────────── */
[data-theme="dark"] .email-modal {
  background: #1e2533;
  border-color: #374151;
}
[data-theme="dark"] .email-modal-header,
[data-theme="dark"] .email-modal-footer {
  background: #161d2b;
  border-color: #374151;
}
[data-theme="dark"] .email-modal-title  { color: #f3f4f6; }
[data-theme="dark"] .email-modal-subtitle { color: #9ca3af; }
[data-theme="dark"] .email-modal-close {
  background: #1e2533;
  border-color: #374151;
  color: #9ca3af;
}
[data-theme="dark"] .email-modal-close:hover { background: #2d3748; color: #f3f4f6; }
[data-theme="dark"] .email-field-label { color: #9ca3af; }
[data-theme="dark"] .modal-input {
  background: #2d3748;
  border-color: #4b5563;
  color: #f3f4f6;
}
[data-theme="dark"] .modal-input:focus {
  border-color: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.15);
}
.appt-time-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.appt-date {
  font-weight: 700;
  color: #111827;
  font-size: 13.5px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.appt-date i {
  color: #0e6008;
  font-size: 16px;
}

.appt-time {
  color: #6b7280;
  font-size: 12.5px;
  font-weight: 550;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.appt-time i {
  color: #9ca3af;
  font-size: 16px;
}

.student-details-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.student-id {
  font-family: monospace;
  font-size: 12.5px;
  font-weight: 600;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  padding: 2px 8px;
  border-radius: 6px;
  color: #4b5563;
  width: fit-content;
}

.student-name {
  font-weight: 700;
  color: #111827;
  font-size: 14px;
}

.student-email {
  color: #6b7280;
  font-size: 12.5px;
  display: inline-flex;
  align-items: center;
}

.flagged-message-cell {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.message-text {
  font-style: italic;
  font-size: 13.5px;
  color: #374151;
  line-height: 1.5;
  background: #fcfdfc;
  border-left: 3px solid #16a34a;
  padding: 6px 12px;
  border-radius: 0 8px 8px 0;
}

.flag-reason-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #fffbeb;
  border: 1px solid #fef3c7;
  color: #d97706;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 600;
  width: fit-content;
}

.severity-status-cell {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.actions-cell {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 130px;
}

.actions-cell .action-btn {
  width: 100%;
  justify-content: center;
  font-size: 12px;
  padding: 7px 10px;
}

.action-btn--edit {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
}

.action-btn--edit:hover {
  background: #dbeafe;
}

.action-btn--cancel {
  background: #fff1f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.action-btn--cancel:hover {
  background: #fee2e2;
}

.action-btn--manage {
  background: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
}

.action-btn--manage:hover {
  background: #dcfce7;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 600;
  width: fit-content;
}

.b-severe   { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }
.b-moderate { background: #fffbeb; color: #9f7a00; border: 1px solid #fde047; }
.b-low      { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
.b-new      { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.b-reviewed { background: #fffbeb; color: #854d0e; border: 1px solid #fef3c7; }
.b-resolved { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }

/* Modal Custom Input */
.modal-input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  font-family: 'DM Sans', sans-serif;
  color: #111827;
  outline: none;
  background: #fff;
  transition: border-color 0.2s;
}

.modal-input:focus {
  border-color: #0e6008;
  box-shadow: 0 0 0 3px rgba(14, 96, 8, 0.15);
}

.action-btn--confirm-reschedule {
  background: #0e6008;
  color: #fff;
  border: 1px solid #0e6008;
}

.action-btn--confirm-reschedule:hover {
  background: #0b4e06;
}

.action-btn--resolve {
  background: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
}

.action-btn--resolve:hover:not(:disabled) {
  background: #dcfce7;
}

.action-btn--resolve:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.icon-resolve {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #16a34a;
}

.resolve-modal {
  max-width: 450px !important;
}

.resolve-body {
  text-align: center;
  padding: 24px 20px;
}

.resolve-icon-large {
  font-size: 56px;
  color: #16a34a;
  margin-bottom: 16px;
}

.resolve-text {
  font-size: 15px;
  font-weight: 550;
  color: #1f2937;
  line-height: 1.5;
  margin-bottom: 8px;
}

.resolve-subtext {
  font-size: 13px;
  color: #6b7280;
  line-height: 1.5;
}

.action-btn--confirm-resolve {
  background: #16a34a;
  color: #fff;
  border: 1px solid #16a34a;
}

.action-btn--confirm-resolve:hover {
  background: #15803d;
}

.text-danger {
  color: #dc2626;
}

/* Dark Mode Overrides */
[data-theme="dark"] .action-btn--resolve {
  background: #14532d;
  color: #4ade80;
  border-color: #166534;
}

[data-theme="dark"] .action-btn--resolve:hover:not(:disabled) {
  background: #166534;
}

[data-theme="dark"] .icon-resolve {
  background: #14532d;
  border-color: #166534;
  color: #4ade80;
}

[data-theme="dark"] .resolve-text {
  color: #f3f4f6;
}

[data-theme="dark"] .resolve-subtext {
  color: #9ca3af;
}

/* Date & Time cells */
[data-theme="dark"] .appt-date {
  color: #f3f4f6;
}

[data-theme="dark"] .appt-date i {
  color: #4ade80;
}

[data-theme="dark"] .appt-time {
  color: #9ca3af;
}

[data-theme="dark"] .appt-time i {
  color: #4b5563;
}

/* Student details */
[data-theme="dark"] .student-id {
  background: #2d3748;
  border-color: #374151;
  color: #9ca3af;
}

[data-theme="dark"] .student-name {
  color: #f3f4f6;
}

[data-theme="dark"] .student-email {
  color: #6b7280;
}

/* Flagged message */
[data-theme="dark"] .message-text {
  background: #161b27;
  border-left-color: #16a34a;
  color: #9ca3af;
}

[data-theme="dark"] .flag-reason-badge {
  background: #2d2410;
  border-color: #78500a;
  color: #fde68a;
}

/* Action buttons */
[data-theme="dark"] .action-btn--edit {
  background: #1e2d4d;
  color: #93c5fd;
  border-color: #1e3a5f;
}

[data-theme="dark"] .action-btn--edit:hover:not(:disabled) {
  background: #1e3a5f;
}

[data-theme="dark"] .action-btn--cancel {
  background: #3b1010;
  color: #fca5a5;
  border-color: #7f1d1d;
}

[data-theme="dark"] .action-btn--cancel:hover:not(:disabled) {
  background: #4d1515;
}

[data-theme="dark"] .action-btn--manage {
  background: #0d2818;
  color: #4ade80;
  border-color: #14532d;
}

[data-theme="dark"] .action-btn--manage:hover:not(:disabled) {
  background: #14532d;
}

/* Severity & Status badges */
[data-theme="dark"] .b-severe   { background: #3b1010; color: #fca5a5; border-color: #7f1d1d; }
[data-theme="dark"] .b-moderate { background: #2d2410; color: #fde68a; border-color: #78500a; }
[data-theme="dark"] .b-low      { background: #0d2818; color: #86efac; border-color: #14532d; }
[data-theme="dark"] .b-new      { background: #172554; color: #93c5fd; border-color: #1e3a5f; }
[data-theme="dark"] .b-reviewed { background: #2d2010; color: #fcd34d; border-color: #78500a; }
[data-theme="dark"] .b-resolved { background: #1a1f2e; color: #6b7280; border-color: #374151; }

/* Card Glow Overrides for appointments */
.sc-severe::before   { background: #ef4444; }
.sc-severe:hover     { box-shadow: 0 12px 20px -8px rgba(239, 68, 68, 0.4); }
.sc-severe .sc-icon  { background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; }

.sc-moderate::before   { background: #f59e0b; }
.sc-moderate:hover     { box-shadow: 0 12px 20px -8px rgba(245, 158, 11, 0.4); }
.sc-moderate .sc-icon  { background: #fffbeb; color: #f59e0b; border: 1px solid #fde68a; }

.sc-low::before   { background: #10b981; }
.sc-low:hover     { box-shadow: 0 12px 20px -8px rgba(16, 185, 129, 0.4); }
.sc-low .sc-icon  { background: #f0fdf4; color: #10b981; border: 1px solid #a7f3d0; }


/* ─────────────────────────────────────────────────────────────
   RESPONSIVENESS — header datetime + table
───────────────────────────────────────────────────────────── */

@media (max-width: 1024px) {
  .page-header-wrapper {
    flex-direction: column;
    align-items: flex-start;
    gap: 14px;
  }

  .header-datetime {
    align-items: flex-start;
    width: 100%;
    min-width: 0;
  }

  .log-controls {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .filters-wrapper {
    width: 100%;
  }

  .select-box,
  .select-box select {
    width: 100%;
  }
}

@media (max-width: 768px) {
  /* ── Header datetime: compact horizontal chip row ── */
  .header-datetime {
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
    gap: 8px 14px;
    padding: 10px 14px;
  }

  .header-day {
    width: 100%;
    font-size: 14px;
  }

  .header-date,
  .header-time {
    font-size: 12.5px;
  }

  .filters-wrapper {
    flex-direction: column;
    gap: 8px;
  }

  /* ── Table → stacked cards, driven by existing data-label attrs ── */
  .log-table thead { display: none; }

  .log-table,
  .log-table tbody,
  .log-table tr,
  .log-table td {
    display: block;
    width: 100%;
  }

  .log-table { min-width: 0; }

  .log-table tbody tr {
    margin-bottom: 14px;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 14px 16px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    position: relative;
  }

  /* Keep severity indicator, now as a left border on the whole card */
  .log-table tbody tr.row-severe   { border-left: 4px solid #ef4444; }
  .log-table tbody tr.row-moderate { border-left: 4px solid #9F7A00; }
  .log-table tbody tr.row-low      { border-left: 4px solid #0A9569; }

  /* Neutralize the desktop color-bar ::before instead of hiding it —
     that pseudo-element is reused below for the mobile field label,
     so it must keep rendering content, just without the bar background */
  .log-table tbody tr td:first-child::before {
    background: none !important;
    width: auto !important;
    height: auto !important;
    position: static !important;
  }

  .log-table td {
    padding: 8px 0;
    border: none;
    border-bottom: 1px dashed #f3f4f6;
    text-align: left !important;
  }
  .log-table td:last-child { border-bottom: none; padding-top: 12px; }

  .log-table td::before {
    content: attr(data-label);
    display: block;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #9ca3af;
    margin-bottom: 4px;
  }

  /* ── Force every field's content to sit flush-left, evenly stacked ── */
  .appt-time-cell,
  .student-details-cell,
  .flagged-message-cell {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
    width: 100%;
    text-align: left;
  }

  .appt-time-cell > *,
  .student-details-cell > *,
  .flagged-message-cell > * {
    align-self: flex-start !important;
    margin: 0 !important;
    float: none !important;
    text-align: left !important;
  }

  .log-table td > .badge {
    align-self: flex-start;
    margin: 0;
  }

  /* Actions become a full-width row of two buttons instead of a narrow column */
  .actions-cell {
    width: 100%;
    flex-direction: row;
    gap: 8px;
  }
  .actions-cell .action-btn {
    flex: 1;
  }

  .message-text {
    font-size: 13px;
    text-align: left;
  }
}

@media (max-width: 480px) {
  .header-datetime {
    width: 100%;
  }

  .actions-cell {
    flex-direction: column;
  }

  .email-modal-footer {
    flex-direction: column-reverse;
  }
  .email-modal-footer .action-btn {
    width: 100%;
    justify-content: center;
  }
}

/* ── Dark mode: card rows on mobile ── */
[data-theme="dark"] .log-table tbody tr {
  background: #1e2533;
  border-color: #2d3748;
  box-shadow: 0 4px 10px rgba(0,0,0,0.25);
}
[data-theme="dark"] .log-table td {
  border-bottom-color: #2d3748;
}
[data-theme="dark"] .log-table td::before {
  color: #6b7280;
}

/* Table Left Border Indicators matching severity */
.log-table tbody tr.row-severe td:first-child::before { background-color: #ef4444; }
.log-table tbody tr.row-moderate td:first-child::before { background-color: #f59e0b; }
.log-table tbody tr.row-low td:first-child::before { background-color: #10b981; }
</style>
