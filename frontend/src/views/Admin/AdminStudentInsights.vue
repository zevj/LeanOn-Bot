<template>
  <div class="layout">
    <SidebarAdmin
      :open="sidebarOpen"
      @toggle="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)"
    />

    <main class="main-area">
      <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)" />

      <div class="main-container">
        <div class="page-header-wrapper">
          <div class="header-title">
            <h1 class="title">Student Insights</h1>
            <p class="subtext">Anonymized wellness metrics for an individual student case. AI Insights stay on the school-wide Analytics page.</p>
          </div>

          <div class="header-actions">
            <div class="period-selector">
              <label>Reporting Period:</label>
              <select
                class="period-dropdown"
                :value="selectedPeriod"
                :disabled="!selectedStudent"
                @change="changePeriod($event.target.value)"
              >
                <option v-for="p in periods" :key="p.value" :value="p.value">
                  {{ p.label }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Student search -->
        <div class="student-search-card">
          <div class="student-search-wrap"> <!-- BINAGO -->
  <div class="search-input-group">
    <i class="bx bx-search search-icon"></i>
    <input
      v-model="searchQuery"
      type="text"
      class="student-search-input"
      placeholder="Search by Anonymous # or email..."
      @input="onSearchInput"
      @focus="onSearchFocus"
    />
  </div>
  <button
    v-if="selectedStudent"
    class="clear-student-btn"
    type="button"
    title="Clear student"
    @click="clearStudent"
  >
    <i class="bx bx-x"></i> Clear
  </button>
</div>

<div v-if="showDropdown" class="student-dropdown">
  <div v-if="searching" class="student-dropdown-empty">
    <div class="dropdown-spinner"></div>
    Loading…
  </div>

  <template v-else>
    <div v-if="!searchQuery.trim() && searchResults.length > 0" class="student-dropdown-heading">
      <i class="bx bx-flag"></i> Flagged Students
    </div>

    <button
      v-for="s in searchResults"
      :key="s.id"
      type="button"
      class="student-dropdown-item"
      :class="{ 'is-flagged': s.has_crisis_flag }"
      @click="selectStudent(s)"
    >
      <span class="student-item-top">
        <span class="student-item-display">{{ s.display }}</span>
        <span v-if="s.has_crisis_flag" class="student-flag-badge" title="Recent crisis alert">
          <i class="bx bxs-flag"></i> Flagged
        </span>
      </span>
      <span class="student-item-meta">{{ s.masked_email }} · {{ s.department || 'No department' }}</span>
    </button>

    <div v-if="searchQuery.trim() && searchResults.length === 0" class="student-dropdown-empty">
      No students found
    </div>
    <div v-if="!searchQuery.trim() && searchResults.length === 0" class="student-dropdown-empty">
      No flagged students right now
    </div>
  </template>
</div> <!-- END -->

          <!-- <div v-if="showDropdown && (searchResults.length > 0 || searching)" class="student-dropdown">
            <div v-if="searching" class="student-dropdown-empty">Searching…</div>
            <button
              v-for="s in searchResults"
              :key="s.id"
              type="button"
              class="student-dropdown-item"
              @click="selectStudent(s)"
            >
              <span class="student-item-display">{{ s.display }}</span>
              <span class="student-item-meta">{{ s.masked_email }} · {{ s.department || 'No department' }}</span>
            </button>
            <div v-if="!searching && searchQuery.trim() && searchResults.length === 0" class="student-dropdown-empty">
              No students found
            </div>
          </div> -->

          <div v-if="selectedStudent" class="selected-student-banner">
            <div class="selected-student-left">
              <i class="bx bx-user-circle"></i>
              <div>
                <strong>{{ selectedStudent.display }}</strong>
                <span>{{ selectedStudent.masked_email }} · {{ selectedStudent.department || 'No department' }}</span>
              </div>
            </div>
            <p class="privacy-chip">
              <i class="bx bx-lock-alt"></i>
              Case view — anonymized metrics only
            </p>
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="!selectedStudent" class="empty-state-card">
          <i class="bx bx-user-pin"></i>
          <h3>Select a student to begin</h3>
          <p>Search by Anonymous # (e.g. 1123) or masked email to view conversations, mood, usage hours, and crisis alerts for that student.</p>
        </div>

        <!-- Loading -->
        <div v-else-if="loading" class="loading-overlay">
          <div class="spinner"></div>
          <p>Loading student wellness metrics…</p>
        </div>

        <!-- Metrics -->
        <template v-else>
          <div v-if="!stats.had_activity" class="no-activity-banner">
            <i class="bx bx-info-circle"></i>
            No activity found for this student in the selected period.
          </div>

          <div class="stat-cards-grid">
            <div class="stat-card green">
              <div class="stat-card-content">
                <h4 class="stat-label">Conversations</h4>
                <p class="stat-value">{{ stats.total_conversations || 0 }}</p>
                <span class="stat-growth" :class="getGrowthClass(stats.conversation_growth)">
                  <i :class="getGrowthIcon(stats.conversation_growth)"></i>
                  {{ Math.abs(stats.conversation_growth || 0) }}%
                </span>
              </div>
              <div class="stat-icon-wrapper icon-green"><i class="bx bx-chat"></i></div>
            </div>

            <div class="stat-card amber">
              <div class="stat-card-content">
                <h4 class="stat-label">Crisis Alerts</h4>
                <p class="stat-value" :class="{ 'stat-value--alert': stats.crisis_alert_count > 0 }">
                  {{ stats.crisis_alert_count || 0 }}
                </p>
                <span class="stat-meta">{{ formatSeverityBreakdown(stats.crisis_by_severity) }}</span>
              </div>
              <div class="stat-icon-wrapper icon-amber"><i class="bx bx-shield"></i></div>
            </div>

            <div class="stat-card cyan">
              <div class="stat-card-content">
                <h4 class="stat-label">Peak Usage Hour</h4>
                <p class="stat-value unit-suffix">{{ formatPeakHour(stats.peak_hour) }}</p>
                <span class="stat-meta">Most active hour</span>
              </div>
              <div class="stat-icon-wrapper icon-cyan"><i class="bx bx-time-five"></i></div>
            </div>

            <div class="stat-card purple">
              <div class="stat-card-content">
                <h4 class="stat-label">Messages</h4>
                <p class="stat-value">{{ stats.message_count || 0 }}</p>
                <span class="stat-meta">{{ stats.session_count || 0 }} sessions · {{ stats.fallback_count || 0 }} fallbacks</span>
              </div>
              <div class="stat-icon-wrapper icon-purple"><i class="bx bx-message-rounded-dots"></i></div>
            </div>
          </div>

          <div class="charts-section">
            <MoodDistributionChart :data="trendData.emotion_distribution || {}" />
            <PeakUsageChart :data="trendData.peak_usage_hours || []" />
          </div>

          <div class="charts-section-bottom">
            <SentimentTrendChart :data="trendData.sentiment_over_time || []" />

            <div class="stat-card perf-card student-context-card">
              <div class="context-header">
                <div class="stat-icon-wrapper icon-green"><i class="bx bx-info-circle"></i></div>
                <h3 class="perf-card-title">Case Context</h3>
              </div>
              <div class="perf-card-body">
                <div class="perf-row">
                  <span>Subject</span>
                  <strong>{{ selectedStudent.display }}</strong>
                </div>
                <div class="perf-row">
                  <span>Period</span>
                  <strong>{{ stats.period_start }} → {{ stats.period_end }}</strong>
                </div>
                <div class="perf-row">
                  <span>Department</span>
                  <strong>{{ selectedStudent.department || 'N/A' }}</strong>
                </div>
                <div class="perf-privacy-notice">
                  <i class="bx bx-lock-alt"></i>
                  <strong>Privacy:</strong> This page shows anonymized individual metrics for counseling case review. School-wide AI Insights are not generated here.
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import SidebarAdmin from '@/components/sidebarAdmin.vue'
import HeaderAdmin from '@/components/headerAdmin.vue'
import MoodDistributionChart from '@/components/MoodDistributionChart.vue'
import PeakUsageChart from '@/components/PeakUsageChart.vue'
import SentimentTrendChart from '@/components/SentimentTrendChart.vue'

const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false')

const periods = [
  { label: 'Today', value: '1d' },
  { label: 'Last 7 Days', value: '7d' },
  { label: 'Last 14 Days', value: '14d' },
  { label: 'Last 30 Days', value: '30d' },
  { label: 'Last 90 Days', value: '90d' },
]

const selectedPeriod = ref('7d')
const selectedStudent = ref(null)
const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)
const showDropdown = ref(false)
const loading = ref(false)
const fetching = ref(false)

const stats = ref({
  total_conversations: 0,
  conversation_growth: 0,
  peak_hour: null,
  crisis_alert_count: 0,
  crisis_by_severity: {},
  fallback_count: 0,
  session_count: 0,
  message_count: 0,
  had_activity: false,
  period_start: '',
  period_end: '',
})

const trendData = ref({
  emotion_distribution: {},
  sentiment_over_time: [],
  peak_usage_hours: [],
})

let searchTimer = null

const authConfig = () => ({
  headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
})

const onSearchInput = () => {
  showDropdown.value = true
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(runSearch, 300)
}

/* DINAGDAG */
const onSearchFocus = () => {
  showDropdown.value = true
  // wala pang laman ang search? kunin yung default/flagged list
  if (!searchQuery.value.trim() && searchResults.value.length === 0) {
    runSearch()
  }
}

const runSearch = async () => {
  const q = searchQuery.value.trim()

  searching.value = true
  try {
    // walang q -> default list (e.g. flagged students)
    const url = q
      ? `/api/admin/analytics/students?q=${encodeURIComponent(q)}`
      : `/api/admin/analytics/students?flagged=true`

    const res = await axios.get(url, authConfig())
    searchResults.value = res.data.students || []
  } catch (err) {
    console.error('Student search failed:', err)
    searchResults.value = []
  } finally {
    searching.value = false
  }
}

/* END */

const selectStudent = (student) => {
  selectedStudent.value = student
  searchQuery.value = student.display
  showDropdown.value = false
  searchResults.value = []
  fetchStudentData()
}

const clearStudent = () => {
  selectedStudent.value = null
  searchQuery.value = ''
  searchResults.value = []
  showDropdown.value = false
  stats.value = {
    total_conversations: 0,
    conversation_growth: 0,
    peak_hour: null,
    crisis_alert_count: 0,
    crisis_by_severity: {},
    fallback_count: 0,
    session_count: 0,
    message_count: 0,
    had_activity: false,
    period_start: '',
    period_end: '',
  }
  trendData.value = {
    emotion_distribution: {},
    sentiment_over_time: [],
    peak_usage_hours: [],
  }
}

const changePeriod = (period) => {
  if (!selectedStudent.value || selectedPeriod.value === period || fetching.value) return
  selectedPeriod.value = period
  fetchStudentData()
}

const fetchStudentData = async () => {
  if (!selectedStudent.value || fetching.value) return
  fetching.value = true
  loading.value = true
  try {
    const userId = selectedStudent.value.id
    const trendPeriod = selectedPeriod.value === '1d' ? '7d' : selectedPeriod.value
    const [dashRes, trendsRes] = await Promise.all([
      axios.get(`/api/admin/analytics/student/dashboard?user_id=${userId}&period=${selectedPeriod.value}`, authConfig()),
      axios.get(`/api/admin/analytics/student/trends?user_id=${userId}&period=${trendPeriod}`, authConfig()),
    ])
    stats.value = dashRes.data
    trendData.value = trendsRes.data
    if (dashRes.data.student) {
      selectedStudent.value = dashRes.data.student
    }
  } catch (err) {
    console.error('Error fetching student insights:', err)
  } finally {
    loading.value = false
    fetching.value = false
  }
}

const formatPeakHour = (hour) => {
  if (hour === null || hour === undefined) return 'N/A'
  if (hour === 0) return '12 AM'
  if (hour === 12) return '12 PM'
  return hour < 12 ? `${hour} AM` : `${hour - 12} PM`
}

const formatSeverityBreakdown = (bySeverity) => {
  if (!bySeverity || Object.keys(bySeverity).length === 0) return 'No classified alerts'
  return Object.entries(bySeverity)
    .map(([k, v]) => `${k}: ${v}`)
    .join(' · ')
}

const getGrowthClass = (val) => {
  if (!val || val === 0) return 'neutral'
  return val > 0 ? 'positive' : 'negative'
}

const getGrowthIcon = (val) => {
  if (!val || val === 0) return 'bx bx-minus'
  return val > 0 ? 'bx bx-trending-up' : 'bx bx-trending-down'
}

const onDocClick = (e) => {
  if (!e.target.closest('.student-search-card')) {
    showDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onDocClick)
})

onUnmounted(() => {
  document.removeEventListener('click', onDocClick)
  if (searchTimer) clearTimeout(searchTimer)
})
</script>

<style scoped src="@/assets/admin/adminAnalytics.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>

<style scoped>
.student-search-card {
  position: relative;
  margin-bottom: 1.5rem;
  background: var(--card-bg, #fff);
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 1rem 1.25rem;
}

.student-search-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 14px;
  color: #9ca3af;
  font-size: 18px;
  pointer-events: none;
}

.student-search-input {
  flex: 1;
  height: 44px;
  padding: 0 14px 0 42px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  font-size: 14px;
  background: #f9fafb;
  color: #111827;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.student-search-input:focus {
  border-color: #86efac;
  box-shadow: 0 0 0 3px rgba(14, 96, 8, 0.12);
  background: #fff;
}

.clear-student-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  height: 36px;
  padding: 0 12px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: #fff;
  color: #6b7280;
  font-size: 13px;
  font-weight: 550;
  cursor: pointer;
}

.clear-student-btn:hover {
  background: #f3f4f6;
  color: #111827;
}

.student-dropdown {
  position: absolute;
  left: 1.25rem;
  right: 1.25rem;
  top: calc(1rem + 48px);
  z-index: 40;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
  max-height: 280px;
  overflow-y: auto;
}

.student-dropdown-item {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
  padding: 12px 14px;
  border: none;
  background: transparent;
  cursor: pointer;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}

.student-dropdown-item:last-child {
  border-bottom: none;
}

.student-dropdown-item:hover {
  background: #f0fdf4;
}

.student-item-display {
  font-size: 14px;
  font-weight: 650;
  color: #111827;
}

.student-item-meta {
  font-size: 12px;
  color: #6b7280;
}

.student-dropdown-empty {
  padding: 16px;
  text-align: center;
  color: #9ca3af;
  font-size: 13px;
}

.selected-student-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #f3f4f6;
}

.selected-student-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.selected-student-left i {
  font-size: 28px;
  color: #0e6008;
}

.selected-student-left strong {
  display: block;
  font-size: 14px;
  color: #111827;
}

.selected-student-left span {
  font-size: 12.5px;
  color: #6b7280;
}

.privacy-chip {
  margin: 0;
  font-size: 12px;
  color: #166534;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 999px;
  padding: 6px 12px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.empty-state-card {
  text-align: center;
  padding: 4rem 2rem;
  background: var(--card-bg, #fff);
  border: 1px dashed #d1d5db;
  border-radius: 16px;
  color: #6b7280;
}

.empty-state-card i {
  font-size: 3rem;
  color: #0e6008;
  margin-bottom: 12px;
}

.empty-state-card h3 {
  margin: 0 0 8px;
  color: #111827;
  font-size: 18px;
}

.empty-state-card p {
  margin: 0 auto;
  max-width: 460px;
  font-size: 13.5px;
  line-height: 1.55;
}

.no-activity-banner {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 1rem;
  padding: 12px 14px;
  border-radius: 10px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
  font-size: 13.5px;
}

.student-context-card {
  height: auto;
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-start;
  padding: 1.5rem;
  gap: 1.25rem;
  border-left: 4px solid #16a34a;
}

.context-header {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
}

.context-header .stat-icon-wrapper {
  width: 36px;
  height: 36px;
  font-size: 1.1rem;
}

.context-header .perf-card-title {
  font-size: 15px;
  font-weight: 600;
  margin: 0;
}

.student-context-card .perf-card-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
  font-size: 13.5px;
}

.student-context-card .perf-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px solid #f3f4f6;
  padding-bottom: 8px;
  color: #6b7280;
}

.student-context-card .perf-row strong {
  color: #111827;
  text-align: right;
}

.student-context-card .perf-privacy-notice {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.5;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #bbf7d0;
  background: #f0fdf4;
  color: #166534;
}

[data-theme="dark"] .student-search-card,
[data-theme="dark"] .empty-state-card {
  background: #161d2b;
  border-color: #374151;
}

[data-theme="dark"] .student-search-input {
  background: #1e2533;
  border-color: #374151;
  color: #f3f4f6;
}

[data-theme="dark"] .student-search-input:focus {
  background: #1e2533;
  border-color: #4ade80;
}

[data-theme="dark"] .clear-student-btn {
  background: #1e2533;
  border-color: #374151;
  color: #9ca3af;
}

[data-theme="dark"] .student-dropdown {
  background: #1e2533;
  border-color: #374151;
}

[data-theme="dark"] .student-dropdown-item {
  border-bottom-color: #2d3748;
}

[data-theme="dark"] .student-dropdown-item:hover {
  background: #15231c;
}

[data-theme="dark"] .student-item-display,
[data-theme="dark"] .empty-state-card h3,
[data-theme="dark"] .selected-student-left strong,
[data-theme="dark"] .student-context-card .perf-row strong {
  color: #f3f4f6;
}

[data-theme="dark"] .selected-student-banner {
  border-top-color: #374151;
}

[data-theme="dark"] .privacy-chip,
[data-theme="dark"] .student-context-card .perf-privacy-notice {
  background: #15231c;
  border-color: #1f3d2a;
  color: #86efac;
}

[data-theme="dark"] .no-activity-banner {
  background: #2d2010;
  border-color: #78500a;
  color: #fcd34d;
}

[data-theme="dark"] .student-context-card .perf-row {
  border-bottom-color: #374151;
  color: #9ca3af;
}

/* --- Responsive stat cards + layout fix (equal width, no right gap) --- */
.stat-cards-grid {
  display: grid !important;
  grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
  gap: 1rem !important;
  width: 100%;
}

.stat-cards-grid .stat-card {
  width: 100% !important;
  min-width: 0; /* prevents flex/grid overflow from long numbers */
}

.charts-section,
.charts-section-bottom {
  display: grid !important;
  grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  gap: 1rem !important;
  width: 100%;
}

/* Tablet */
@media (max-width: 1024px) {
  .stat-cards-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }
}

/* Mobile */
@media (max-width: 640px) {
  .stat-cards-grid {
    grid-template-columns: 1fr !important;
  }

  .charts-section,
  .charts-section-bottom {
    grid-template-columns: 1fr !important;
  }

  .page-header-wrapper {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .header-actions,
  .period-selector {
    width: 100%;
  }

  .period-dropdown {
    width: 100%;
  }

  .student-search-wrap {
    flex-wrap: wrap;
  }

  .clear-student-btn {
    width: 100%;
    justify-content: center;
  }

  .selected-student-banner {
    flex-direction: column;
    align-items: flex-start;
  }

  .privacy-chip {
    align-self: flex-start;
  }

  .student-context-card .perf-row {
    flex-direction: column;
    gap: 4px;
  }

  .student-context-card .perf-row strong {
    text-align: left;
  }
}

.search-input-group {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
  min-width: 0; /* para hindi mag-overflow sa mobile pag naka-wrap */
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 18px;
  pointer-events: none;
}

.search-input-group .student-search-input {
  width: 100%;
}


/* DINAGDAG */
.student-dropdown {
  scrollbar-width: thin;
  scrollbar-color: #a7f3d0 transparent;
}

.student-dropdown::-webkit-scrollbar {
  width: 6px;
}

.student-dropdown::-webkit-scrollbar-thumb {
  background: #a7f3d0;
  border-radius: 999px;
}

.student-dropdown::-webkit-scrollbar-thumb:hover {
  background: #86efac;
}

.student-dropdown-heading {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 14px 6px;
  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #b45309;
}

.student-item-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
}

.student-flag-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 650;
  color: #b45309;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 999px;
  padding: 2px 8px;
  flex-shrink: 0;
}

.student-dropdown-item.is-flagged {
  background: #fffbeb;
}

.student-dropdown-item.is-flagged:hover {
  background: #fef3c7;
}

.dropdown-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid #e5e7eb;
  border-top-color: #16a34a;
  border-radius: 50%;
  display: inline-block;
  margin-right: 6px;
  animation: dropdown-spin 0.6s linear infinite;
  vertical-align: middle;
}

@keyframes dropdown-spin {
  to { transform: rotate(360deg); }
}

/* dark mode */
[data-theme="dark"] .student-dropdown-heading {
  color: #fcd34d;
}

[data-theme="dark"] .student-flag-badge {
  background: #2d2010;
  border-color: #78500a;
  color: #fcd34d;
}

[data-theme="dark"] .student-dropdown-item.is-flagged {
  background: #2d2010;
}

[data-theme="dark"] .student-dropdown-item.is-flagged:hover {
  background: #3a2812;
}
</style>