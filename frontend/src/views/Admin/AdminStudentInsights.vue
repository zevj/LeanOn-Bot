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
            <p class="subtext">Student-specific wellness metrics for an individual student case.</p>
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

            <!-- Export Button -->
            <button
              class="export-btn"
              type="button"
              :disabled="!selectedStudent || loading"
              @click="openExportModal"
            >
              <i class="bx bx-download"></i> Export Report
            </button>
          </div>
        </div>

        <!-- Student search -->
        <div class="student-search-card">
          <div class="student-search-wrap">
            <i class="bx bx-search search-icon"></i>
            <input
              v-model="searchQuery"
              type="text"
              class="student-search-input"
                placeholder="Search by name or domain email..."
              @input="onSearchInput"
              @focus="showDropdown = searchResults.length > 0"
            />
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
                <span class="student-item-meta">
                  {{ s.domain_email ? '@' + s.domain_email : s.email }} · {{ s.department || 'No department' }}
                </span>
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
                <span>
                  {{ selectedStudent.domain_email ? '@' + selectedStudent.domain_email : selectedStudent.email }} · {{ selectedStudent.department || 'No department' }}
                </span>
              </div>
            </div>
            <div class="selected-student-right">
              <p class="privacy-chip">
                <i class="bx bx-lock-alt"></i>
                Case view — student metrics
              </p>
            </div>
          </div>
        </div>

        <!-- Student Selection Hub (when no student selected) -->
        <div v-if="!selectedStudent" class="insights-welcome-hub">
          <!-- Hero Section -->
          <div class="hub-hero-card">
            <div class="hub-hero-badge">
              <i class="bx bx-user-pin"></i> Counseling Case Review
            </div>
            <h2 class="hub-hero-title">Student Insights Hub</h2>
            <p class="hub-hero-subtext">
              Select a student case from the priority roster below or search by name or email address above to review individual mood trends, peak active hours, and crisis alert history.
            </p>
          </div>

          <!-- Priority Roster / Suggested Students Grid -->
          <div class="hub-section">
            <div class="hub-section-header">
              <div class="section-title-wrap">
                <i class="bx bxs-flag-alt icon-flag"></i>
                <h3 class="hub-section-title">Priority & Active Student Roster</h3>
              </div>
              <span v-if="suggestedStudents.length" class="hub-badge-count">
                {{ suggestedStudents.length }} {{ suggestedStudents.length === 1 ? 'Student' : 'Students' }} Available
              </span>
            </div>

            <div v-if="loadingSuggested" class="hub-students-loading">
              <div class="spinner"></div>
              <span>Loading student roster…</span>
            </div>

            <div v-else-if="suggestedStudents.length === 0" class="hub-empty-roster">
              <i class="bx bx-check-circle icon-ok"></i>
              <div>
                <strong>No active flagged student alerts</strong>
                <p>Use the search bar above to look up any student in the system.</p>
              </div>
            </div>

            <div v-else class="hub-students-grid">
              <div
                v-for="student in suggestedStudents"
                :key="student.id"
                class="student-quick-card"
                :class="{ 'card-flagged': student.has_crisis_flag }"
                @click="selectStudent(student)"
              >
                <div class="student-card-left">
                  <div class="student-avatar" :class="{ 'avatar-flagged': student.has_crisis_flag }">
                    <i class="bx bx-user"></i>
                  </div>
                  <div class="student-info">
                    <div class="student-name-row">
                      <span class="student-name">{{ student.display }}</span>
                      <span v-if="student.has_crisis_flag" class="flag-badge">
                        <i class="bx bxs-flag"></i> Flagged Alert
                      </span>
                    </div>
                    <div class="student-sub-info">
                      <span>{{ student.masked_email }}</span>
                      <span class="dot-divider">•</span>
                      <span>{{ student.department || 'General' }}</span>
                    </div>
                  </div>
                </div>
                <div class="student-card-right">
                  <button type="button" class="select-case-btn">
                    View Case <i class="bx bx-chevron-right"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Feature Overview Grid -->
          <div class="hub-features-grid">
            <div class="hub-feature-card">
              <div class="feature-icon icon-green">
                <i class="bx bx-pie-chart-alt-2"></i>
              </div>
              <div class="feature-content">
                <h4>Mood & Emotion Distribution</h4>
                <p>Track positive, anxious, stressed, or sad emotional logs over selected reporting periods.</p>
              </div>
            </div>

            <div class="hub-feature-card">
              <div class="feature-icon icon-cyan">
                <i class="bx bx-time-five"></i>
              </div>
              <div class="feature-content">
                <h4>Peak Usage Hour Tracking</h4>
                <p>Identify what time of day the student reaches out to LeanOn-Bot for targeted outreach.</p>
              </div>
            </div>

            <div class="hub-feature-card">
              <div class="feature-icon icon-amber">
                <i class="bx bx-shield-quarter"></i>
              </div>
              <div class="feature-content">
                <h4>Crisis Logs & Direct Chat</h4>
                <p>Review severity levels, message volume, and launch a direct chat with the student with one click.</p>
              </div>
            </div>
          </div>
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

    <!-- Export Student Case Modal -->
    <Transition name="modal-fade">
      <div v-if="showExportModal" class="export-modal-overlay" @click.self="closeExportModal">
        <div class="export-modal">
          <div class="export-modal-header">
            <div class="export-modal-header-left">
              <div class="export-modal-icon">
                <i class="bx bxs-file-export"></i>
              </div>
              <div>
                <h3 class="export-modal-title">Export Student Case Report</h3>
                <p class="export-modal-subtitle">Generate report for {{ selectedStudent?.display }}</p>
              </div>
            </div>
            <button class="export-modal-close" type="button" @click="closeExportModal">
              <i class="bx bx-x"></i>
            </button>
          </div>

          <div class="export-modal-body">
            <!-- Export Format -->
            <div class="export-field-group">
              <label class="export-field-label">Export Format</label>
              <div class="export-format-tabs">
                <button
                  type="button"
                  class="export-format-tab"
                  :class="{ active: exportOptions.format === 'pdf' }"
                  @click="exportOptions.format = 'pdf'"
                >
                  <i class="bx bxs-file-pdf"></i> PDF Document
                </button>
                <button
                  type="button"
                  class="export-format-tab"
                  :class="{ active: exportOptions.format === 'csv' }"
                  @click="exportOptions.format = 'csv'"
                >
                  <i class="bx bxs-file-txt"></i> CSV Spreadsheet
                </button>
              </div>
            </div>

            <!-- Filtered Period Details -->
            <div class="export-field-group">
              <label class="export-field-label">Filtered Reporting Period</label>
              <div class="export-period-badge" style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;color:#166534;font-size:13.5px;font-weight:600;">
                <i class="bx bx-calendar" style="font-size:18px;"></i>
                <span>{{ periods.find(p => p.value === selectedPeriod)?.label || selectedPeriod }}</span>
                <span v-if="stats.period_start && stats.period_end" style="color:#4b5563;font-weight:normal;font-size:12.5px;">
                  ({{ stats.period_start }} → {{ stats.period_end }})
                </span>
              </div>
            </div>
          </div>

          <div class="export-modal-footer">
            <button type="button" class="export-cancel-btn" @click="closeExportModal">Cancel</button>
            <button
              type="button"
              class="export-confirm-btn"
              @click="exportOptions.format === 'csv' ? generateCSV() : generatePDF()"
              :disabled="exportLoading"
            >
              <span v-if="exportLoading" class="btn-spinner"></span>
              <i v-else :class="exportOptions.format === 'csv' ? 'bx bx-spreadsheet' : 'bx bx-download'"></i>
              {{ exportLoading ? 'Generating...' : (exportOptions.format === 'csv' ? 'Download CSV' : 'Download PDF') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
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
const suggestedStudents = ref([])
const loadingSuggested = ref(false)

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

const fetchSuggestedStudents = async () => {
  loadingSuggested.value = true
  try {
    const res = await axios.get('/api/admin/analytics/students?flagged=true', authConfig())
    suggestedStudents.value = res.data.students || []
  } catch (err) {
    console.error('Failed to load suggested students:', err)
    suggestedStudents.value = []
  } finally {
    loadingSuggested.value = false
  }
}

// ── Export Modal State & Logic ────────────────────────────────────
const showExportModal = ref(false)
const exportLoading = ref(false)
const exportOptions = ref({
  format: 'pdf',
})

const openExportModal = () => {
  if (!selectedStudent.value) return
  exportOptions.value.format = 'pdf'
  showExportModal.value = true
}

const closeExportModal = () => {
  if (!exportLoading.value) showExportModal.value = false
}

// PDF Styling Helpers
const PDF_GREEN      = [14, 96, 8]
const PDF_GREEN_MID  = [22, 163, 74]
const PDF_GREEN_DARK = [10, 68, 6]
const PDF_TEXT_DARK  = [17, 24, 39]
const PDF_TEXT_MID   = [75, 85, 99]
const PDF_TEXT_LIGHT = [156, 163, 175]
const PDF_ROW_ALT    = [247, 250, 247]
const PDF_BORDER     = [229, 231, 235]

const loadImageAsDataUrl = (path) => new Promise((resolve) => {
  const img = new Image()
  img.crossOrigin = 'anonymous'
  img.onload = () => {
    const canvas = document.createElement('canvas')
    canvas.width  = img.naturalWidth
    canvas.height = img.naturalHeight
    canvas.getContext('2d').drawImage(img, 0, 0)
    resolve(canvas.toDataURL('image/png'))
  }
  img.onerror = () => resolve(null)
  img.src = path
})

const pdfDrawHeader = async (doc, title, periodLabel, generatedAt, refId) => {
  const W = doc.internal.pageSize.getWidth()
  const M = 14
  const BANNER_TOP = 0
  const BANNER_H   = 42

  doc.setFillColor(...PDF_GREEN_DARK)
  doc.rect(0, BANNER_TOP, W, 7, 'F')
  doc.setFillColor(...PDF_GREEN)
  doc.rect(0, 7, W, BANNER_H - 7, 'F')
  doc.setFillColor(...PDF_GREEN_MID)
  doc.rect(W - 38, 7, 38, BANNER_H - 7, 'F')

  const LOGO_SIZE = 22
  const LOGO_Y    = 7 + (35 - LOGO_SIZE) / 2
  const GC_X      = M
  const LB_X      = GC_X + LOGO_SIZE + 3

  const gcLogo = await loadImageAsDataUrl('/gc-logo.png')
  if (gcLogo) {
    doc.addImage(gcLogo, 'PNG', GC_X, LOGO_Y, LOGO_SIZE, LOGO_SIZE)
  }

  doc.setDrawColor(255, 255, 255)
  doc.setLineWidth(0.4)
  doc.line(LB_X - 1.5, LOGO_Y + 2, LB_X - 1.5, LOGO_Y + LOGO_SIZE - 2)

  const lbLogo = await loadImageAsDataUrl('/leanOnBot.png')
  if (lbLogo) {
    doc.addImage(lbLogo, 'PNG', LB_X, LOGO_Y, LOGO_SIZE, LOGO_SIZE)
  }

  const textX = LB_X + LOGO_SIZE + 5

  doc.setFontSize(6.5)
  doc.setFont('helvetica', 'normal')
  doc.setTextColor(187, 247, 208)
  doc.text('GORDON COLLEGE  ·  GUIDANCE & COUNSELING OFFICE  ·  LEANON BOT', textX, 15)

  doc.setFontSize(13)
  doc.setFont('helvetica', 'bold')
  doc.setTextColor(255, 255, 255)
  doc.text(title, textX, 25, { maxWidth: W - textX - 42 })

  doc.setFontSize(7.5)
  doc.setFont('helvetica', 'normal')
  doc.setTextColor(187, 247, 208)
  const metaText = `Period: ${periodLabel}   ·   Generated: ${generatedAt}`
  doc.text(metaText, textX, 34, { maxWidth: W - textX - 42 })

  doc.setFontSize(7)
  doc.setFont('helvetica', 'bold')
  doc.setTextColor(255, 255, 255)
  doc.text('CONFIDENTIAL', W - 19, 19, { align: 'center' })
  doc.setFont('helvetica', 'normal')
  doc.setFontSize(6)
  doc.text('Student Case View', W - 19, 25, { align: 'center' })
  doc.setFontSize(5.5)
  doc.text(`Ref: ${refId}`, W - 19, 30, { align: 'center' })

  doc.setDrawColor(...PDF_BORDER)
  doc.setLineWidth(0.25)
  doc.line(0, BANNER_H + 1, W, BANNER_H + 1)

  doc.setFontSize(7)
  doc.setFont('helvetica', 'italic')
  doc.setTextColor(...PDF_TEXT_MID)
  doc.text('Confidential student case wellness report for counseling records.', M, BANNER_H + 7)

  return BANNER_H + 13
}

const pdfDrawFooter = (doc, refId) => {
  const count = doc.internal.getNumberOfPages()
  const W = doc.internal.pageSize.getWidth()
  const H = doc.internal.pageSize.getHeight()
  for (let i = 1; i <= count; i++) {
    doc.setPage(i)
    doc.setDrawColor(...PDF_BORDER)
    doc.setLineWidth(0.25)
    doc.line(14, H - 14, W - 14, H - 14)
    doc.setFontSize(6.5)
    doc.setFont('helvetica', 'normal')
    doc.setTextColor(...PDF_TEXT_LIGHT)
    doc.text('LeanOn Bot  ·  Gordon College  ·  Confidential — For authorized guidance counselors only', 14, H - 9)
    doc.setFontSize(6)
    doc.text(`Export Ref: ${refId}`, 14, H - 5)
    doc.setFontSize(7)
    doc.setFont('helvetica', 'bold')
    doc.setTextColor(120, 130, 145)
    doc.text(`Page ${i} of ${count}`, W - 14, H - 7, { align: 'right' })
  }
}

const pdfSectionHeading = (doc, text, y, margin) => {
  const W = doc.internal.pageSize.getWidth()
  doc.setFillColor(...PDF_GREEN)
  doc.rect(margin, y - 4.5, 3, 7, 'F')
  doc.setFontSize(10.5)
  doc.setFont('helvetica', 'bold')
  doc.setTextColor(...PDF_TEXT_DARK)
  doc.text(text, margin + 6, y)
  doc.setDrawColor(...PDF_BORDER)
  doc.setLineWidth(0.2)
  doc.line(margin + 6, y + 2, W - margin, y + 2)
  return y + 8
}

const pdfTableStyles = () => ({
  headStyles: {
    fillColor: PDF_GREEN,
    textColor: [255, 255, 255],
    fontStyle: 'bold',
    fontSize: 8.5,
    cellPadding: { top: 4, bottom: 4, left: 5, right: 5 },
  },
  bodyStyles: {
    textColor: PDF_TEXT_DARK,
    fontSize: 8,
    cellPadding: { top: 3.5, bottom: 3.5, left: 5, right: 5 },
  },
  alternateRowStyles: {
    fillColor: PDF_ROW_ALT,
  },
  tableLineColor: PDF_BORDER,
  tableLineWidth: 0.2,
})

// Generate PDF Report for Selected Student
const generatePDF = async () => {
  if (!selectedStudent.value) return
  exportLoading.value = true
  try {
    const student = selectedStudent.value
    const doc = new jsPDF()
    const periodObj = periods.find(p => p.value === selectedPeriod.value)
    const periodLabel = periodObj ? periodObj.label : selectedPeriod.value
    const generatedAt = new Date().toLocaleString('en-PH', {
      month: 'long', day: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit', hour12: true,
    })
    const refId = `STU-RPT-${Date.now().toString(36).toUpperCase()}`

    const W = doc.internal.pageSize.getWidth()
    const M = 14

    let startY = await pdfDrawHeader(
      doc,
      `Student Case: ${student.display}`,
      `${periodLabel} (${stats.value.period_start || ''} → ${stats.value.period_end || ''})`,
      generatedAt,
      refId
    )

    // Student Profile Card
    doc.setFillColor(245, 247, 250)
    doc.setDrawColor(...PDF_BORDER)
    doc.roundedRect(M, startY, W - (M * 2), 24, 3, 3, 'FD')

    doc.setFontSize(11)
    doc.setFont('helvetica', 'bold')
    doc.setTextColor(...PDF_TEXT_DARK)
    doc.text(student.display, M + 6, startY + 8)

    doc.setFontSize(8.5)
    doc.setFont('helvetica', 'normal')
    doc.setTextColor(...PDF_TEXT_MID)
    const emailText = student.domain_email ? `@${student.domain_email}` : student.email
    doc.text(`Email: ${emailText} (${student.email})`, M + 6, startY + 14)
    doc.text(`Department: ${student.department || 'N/A'}  ·  Student ID: #${student.id}`, M + 6, startY + 19)

    startY += 30

    // Wellness Metrics Summary
    startY = pdfSectionHeading(doc, 'Wellness Metrics Summary', startY, M)

    const statsRows = [
      ['Conversations', String(stats.value.total_conversations || 0), 'Total Messages', String(stats.value.message_count || 0)],
      ['Session Count', String(stats.value.session_count || 0), 'Off-Topic Fallbacks', String(stats.value.fallback_count || 0)],
      ['Peak Active Hour', formatPeakHour(stats.value.peak_hour), 'Crisis Alert Count', String(stats.value.crisis_alert_count || 0)],
    ]

    autoTable(doc, {
      startY: startY,
      margin: { left: M, right: M },
      head: [['Metric', 'Value', 'Metric', 'Value']],
      body: statsRows,
      ...pdfTableStyles(),
    })

    startY = doc.lastAutoTable.finalY + 8

    // Crisis Severity Breakdown
    if (stats.value.crisis_alert_count > 0 || (stats.value.crisis_by_severity && Object.keys(stats.value.crisis_by_severity).length > 0)) {
      startY = pdfSectionHeading(doc, 'Crisis Severity Breakdown', startY, M)

      const crisisRows = Object.entries(stats.value.crisis_by_severity || {}).map(([sev, count]) => [
        sev.charAt(0).toUpperCase() + sev.slice(1),
        String(count),
      ])

      if (crisisRows.length === 0) {
        crisisRows.push(['Unclassified / General Alerts', String(stats.value.crisis_alert_count)])
      }

      autoTable(doc, {
        startY: startY,
        margin: { left: M, right: M },
        head: [['Severity Level', 'Alert Count']],
        body: crisisRows,
        ...pdfTableStyles(),
      })

      startY = doc.lastAutoTable.finalY + 8
    }

    // Emotion Distribution
    const emoDist = trendData.value.emotion_distribution || {}
    if (Object.keys(emoDist).length > 0) {
      if (startY > doc.internal.pageSize.getHeight() - 60) {
        doc.addPage()
        startY = 20
      }

      startY = pdfSectionHeading(doc, 'Emotion & Mood Breakdown', startY, M)

      const totalEmotions = Object.values(emoDist).reduce((a, b) => a + b, 0)
      const emoRows = Object.entries(emoDist)
        .sort(([, a], [, b]) => b - a)
        .map(([emo, count], idx) => [
          emo.charAt(0).toUpperCase() + emo.slice(1),
          String(count),
          totalEmotions > 0 ? `${((count / totalEmotions) * 100).toFixed(1)}%` : '0%',
          `#${idx + 1}`,
        ])

      autoTable(doc, {
        startY: startY,
        margin: { left: M, right: M },
        head: [['Emotion', 'Log Count', 'Percentage', 'Rank']],
        body: emoRows,
        ...pdfTableStyles(),
      })

      startY = doc.lastAutoTable.finalY + 8
    }

    // Sentiment Over Time
    const sentWeeks = trendData.value.sentiment_over_time || []
    if (sentWeeks.length > 0) {
      if (startY > doc.internal.pageSize.getHeight() - 60) {
        doc.addPage()
        startY = 20
      }

      startY = pdfSectionHeading(doc, 'Sentiment Trends Over Time', startY, M)

      const sentRows = sentWeeks.map((w, idx) => {
        const pos = w.positive || 0
        const neu = w.neutral || 0
        const neg = w.negative || 0
        const dominant = pos >= neu && pos >= neg ? 'Positive' : (neg >= pos && neg >= neu ? 'Negative' : 'Neutral')
        const weekLabel = w.week_start ? w.week_start : `Week ${idx + 1}`
        return [weekLabel, String(pos), String(neu), String(neg), dominant]
      })

      autoTable(doc, {
        startY: startY,
        margin: { left: M, right: M },
        head: [['Week Starting', 'Positive', 'Neutral', 'Negative', 'Dominant Trend']],
        body: sentRows,
        ...pdfTableStyles(),
      })

      startY = doc.lastAutoTable.finalY + 8
    }

    pdfDrawFooter(doc, refId)

    const fileName = `Student-Insights-${student.display.replace(/\s+/g, '_')}-${selectedPeriod.value}-${new Date().toISOString().slice(0, 10)}.pdf`
    doc.save(fileName)
    closeExportModal()
  } catch (err) {
    console.error('PDF export failed:', err)
    alert('Failed to generate PDF report. Please try again.')
  } finally {
    exportLoading.value = false
  }
}

// Generate CSV Report for Selected Student
const generateCSV = async () => {
  if (!selectedStudent.value) return
  exportLoading.value = true
  try {
    const student = selectedStudent.value
    const periodObj = periods.find(p => p.value === selectedPeriod.value)
    const periodLabel = periodObj ? periodObj.label : selectedPeriod.value
    const generatedAt = new Date().toLocaleString('en-PH', {
      month: 'long', day: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit', hour12: true,
    })
    const refId = `STU-RPT-${Date.now().toString(36).toUpperCase()}`

    const esc = (v) => `"${String(v ?? '').replace(/"/g, '""').replace(/\r?\n/g, ' ')}"`
    const rows = []

    rows.push([esc('LeanOn Bot — Individual Student Wellness Report')])
    rows.push([esc('Gordon College — Guidance & Counseling Office')])
    rows.push([esc(`Student Name: ${student.display}`)])
    rows.push([esc(`Student Email: ${student.email}`)])
    rows.push([esc(`Department: ${student.department || 'N/A'}`)])
    rows.push([esc(`Reporting Period: ${periodLabel} (${stats.value.period_start || ''} to ${stats.value.period_end || ''})`)])
    rows.push([esc(`Generated At: ${generatedAt}`)])
    rows.push([esc(`Export Reference: ${refId}`)])
    rows.push([esc('Privacy Notice: Confidential student case metrics for authorized guidance counselors.')])
    rows.push([])

    rows.push([esc('=== SUMMARY METRICS ===')])
    rows.push([esc('Metric'), esc('Value')])
    rows.push([esc('Total Conversations'), esc(stats.value.total_conversations || 0)])
    rows.push([esc('Conversation Growth (%)'), esc(stats.value.conversation_growth || 0)])
    rows.push([esc('Total Messages'), esc(stats.value.message_count || 0)])
    rows.push([esc('Session Count'), esc(stats.value.session_count || 0)])
    rows.push([esc('Peak Active Hour'), esc(formatPeakHour(stats.value.peak_hour))])
    rows.push([esc('Crisis Alert Count'), esc(stats.value.crisis_alert_count || 0)])
    rows.push([esc('Off-Topic Fallbacks'), esc(stats.value.fallback_count || 0)])
    rows.push([])

    if (stats.value.crisis_by_severity && Object.keys(stats.value.crisis_by_severity).length > 0) {
      rows.push([esc('=== CRISIS SEVERITY BREAKDOWN ===')])
      rows.push([esc('Severity'), esc('Alert Count')])
      Object.entries(stats.value.crisis_by_severity).forEach(([sev, cnt]) => {
        rows.push([esc(sev.charAt(0).toUpperCase() + sev.slice(1)), esc(cnt)])
      })
      rows.push([])
    }

    const emoDist = trendData.value.emotion_distribution || {}
    if (Object.keys(emoDist).length > 0) {
      rows.push([esc('=== EMOTION DISTRIBUTION ===')])
      rows.push([esc('Emotion'), esc('Count'), esc('Percentage'), esc('Rank')])
      const total = Object.values(emoDist).reduce((a, b) => a + b, 0)
      Object.entries(emoDist)
        .sort(([, a], [, b]) => b - a)
        .forEach(([emo, count], idx) => {
          rows.push([
            esc(emo.charAt(0).toUpperCase() + emo.slice(1)),
            esc(count),
            esc(total > 0 ? `${((count / total) * 100).toFixed(1)}%` : '0%'),
            esc(`#${idx + 1}`),
          ])
        })
      rows.push([])
    }

    const sentWeeks = trendData.value.sentiment_over_time || []
    if (sentWeeks.length > 0) {
      rows.push([esc('=== SENTIMENT TREND OVER TIME ===')])
      rows.push([esc('Week Starting'), esc('Positive'), esc('Neutral'), esc('Negative'), esc('Dominant Trend')])
      sentWeeks.forEach((w, idx) => {
        const pos = w.positive || 0
        const neu = w.neutral || 0
        const neg = w.negative || 0
        const dominant = pos >= neu && pos >= neg ? 'Positive' : (neg >= pos && neg >= neu ? 'Negative' : 'Neutral')
        rows.push([
          esc(w.week_start || `Week ${idx + 1}`),
          esc(pos), esc(neu), esc(neg), esc(dominant),
        ])
      })
      rows.push([])
    }

    const peakHours = trendData.value.peak_usage_hours || []
    if (peakHours.length > 0) {
      rows.push([esc('=== PEAK USAGE HOURS ===')])
      rows.push([esc('Hour'), esc('Interaction Volume')])
      peakHours.forEach(h => {
        rows.push([esc(formatPeakHour(h.hour)), esc(h.count || 0)])
      })
      rows.push([])
    }

    rows.push([esc(`End of Report — ${refId}`)])

    const csvContent = '\uFEFF' + rows.map(r => r.join(',')).join('\r\n')
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `Student-Insights-${student.display.replace(/\s+/g, '_')}-${selectedPeriod.value}-${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(url)
    closeExportModal()
  } catch (err) {
    console.error('CSV export failed:', err)
    alert('Failed to generate CSV report. Please try again.')
  } finally {
    exportLoading.value = false
  }
}

const onDocClick = (e) => {
  if (!e.target.closest('.student-search-card')) {
    showDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onDocClick)
  fetchSuggestedStudents()
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

.selected-student-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

.message-student-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: #16a34a;
  color: #ffffff;
  border: none;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, transform 0.15s;
}

.message-student-btn:hover {
  background: #15803d;
  transform: translateY(-1px);
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

/* --- Student Selection Hub (Improved Empty State) --- */
.insights-welcome-hub {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.hub-hero-card {
  background: linear-gradient(135deg, rgba(22, 163, 74, 0.06) 0%, rgba(14, 96, 8, 0.02) 100%), var(--card-bg, #ffffff);
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
}

.hub-hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  border-radius: 999px;
  font-size: 12.5px;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

.hub-hero-title {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  letter-spacing: -0.02em;
}

.hub-hero-subtext {
  margin: 0 0 1.25rem 0;
  font-size: 14px;
  color: #4b5563;
  max-width: 680px;
  line-height: 1.55;
}

.hub-quick-search-wrap {
  position: relative;
  max-width: 540px;
}

.hub-search-input {
  width: 100%;
  height: 46px;
  padding: 0 16px 0 44px;
  border: 1px solid #d1d5db;
  border-radius: 12px;
  font-size: 14px;
  background: #ffffff;
  color: #111827;
  outline: none;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.hub-search-input:focus {
  border-color: #16a34a;
  box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
}

/* Hub Roster Section */
.hub-section {
  background: var(--card-bg, #ffffff);
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
}

.hub-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f3f4f6;
}

.section-title-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-title-wrap .icon-flag {
  font-size: 20px;
  color: #dc2626;
}

.hub-section-title {
  margin: 0;
  font-size: 16px;
  font-weight: 650;
  color: #111827;
}

.hub-badge-count {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  background: #f3f4f6;
  padding: 4px 10px;
  border-radius: 999px;
}

.hub-students-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 2.5rem;
  color: #6b7280;
  font-size: 14px;
}

.hub-empty-roster {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 12px;
  color: #4b5563;
}

.hub-empty-roster .icon-ok {
  font-size: 28px;
  color: #16a34a;
}

.hub-empty-roster strong {
  display: block;
  font-size: 14px;
  color: #111827;
}

.hub-empty-roster p {
  margin: 2px 0 0 0;
  font-size: 13px;
}

/* Grid of quick select student cards */
.hub-students-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1rem;
}

.student-quick-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.student-quick-card:hover {
  border-color: #16a34a;
  transform: translateY(-2px);
  box-shadow: 0 8px 16px -4px rgba(22, 163, 74, 0.12);
  background: #f0fdf4;
}

.student-quick-card.card-flagged {
  border-left: 4px solid #ef4444;
}

.student-card-left {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.student-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #f3f4f6;
  color: #4b5563;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.student-avatar.avatar-flagged {
  background: #fef2f2;
  color: #dc2626;
}

.student-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.student-name-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.student-name {
  font-size: 14px;
  font-weight: 650;
  color: #111827;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.flag-badge {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 2px 7px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}

.student-sub-info {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dot-divider {
  color: #d1d5db;
}

.student-card-right {
  flex-shrink: 0;
}

.select-case-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 12.5px;
  font-weight: 600;
  color: #374151;
  cursor: pointer;
  transition: all 0.15s ease;
}

.student-quick-card:hover .select-case-btn {
  background: #16a34a;
  border-color: #16a34a;
  color: #ffffff;
}

/* Feature grid */
.hub-features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

@media (max-width: 900px) {
  .hub-features-grid {
    grid-template-columns: 1fr;
  }
}

.hub-feature-card {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 1.25rem;
  background: var(--card-bg, #ffffff);
  border: 1px solid #e5e7eb;
  border-radius: 14px;
}

.hub-feature-card .feature-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
}

.icon-green {
  background: #f0fdf4;
  color: #16a34a;
}

.icon-cyan {
  background: #ecfeff;
  color: #0891b2;
}

.icon-amber {
  background: #fffbeb;
  color: #d97706;
}

.feature-content h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 650;
  color: #111827;
}

.feature-content p {
  margin: 0;
  font-size: 12.5px;
  color: #6b7280;
  line-height: 1.5;
}

/* Dark mode overrides for Hub */
[data-theme="dark"] .hub-hero-card {
  background: linear-gradient(135deg, rgba(22, 163, 74, 0.1) 0%, rgba(15, 23, 42, 0) 100%), #161d2b;
  border-color: #374151;
}

[data-theme="dark"] .hub-hero-badge {
  background: #15231c;
  border-color: #1f3d2a;
  color: #86efac;
}

[data-theme="dark"] .hub-hero-title,
[data-theme="dark"] .hub-section-title,
[data-theme="dark"] .student-name,
[data-theme="dark"] .feature-content h4,
[data-theme="dark"] .hub-empty-roster strong {
  color: #f3f4f6;
}

[data-theme="dark"] .hub-hero-subtext,
[data-theme="dark"] .hub-badge-count,
[data-theme="dark"] .student-sub-info,
[data-theme="dark"] .feature-content p,
[data-theme="dark"] .hub-empty-roster p {
  color: #9ca3af;
}

[data-theme="dark"] .hub-badge-count {
  background: #1e2533;
}

[data-theme="dark"] .hub-search-input {
  background: #1e2533;
  border-color: #374151;
  color: #f3f4f6;
}

[data-theme="dark"] .hub-search-input:focus {
  border-color: #4ade80;
}

[data-theme="dark"] .hub-section,
[data-theme="dark"] .hub-feature-card {
  background: #161d2b;
  border-color: #374151;
}

[data-theme="dark"] .hub-section-header {
  border-bottom-color: #2d3748;
}

[data-theme="dark"] .student-quick-card {
  background: #1e2533;
  border-color: #374151;
}

[data-theme="dark"] .student-quick-card:hover {
  background: #15231c;
  border-color: #4ade80;
}

[data-theme="dark"] .student-avatar {
  background: #2a3447;
  color: #9ca3af;
}

[data-theme="dark"] .student-avatar.avatar-flagged {
  background: #3b1d1d;
  color: #f87171;
}

[data-theme="dark"] .select-case-btn {
  background: #2a3447;
  border-color: #374151;
  color: #e5e7eb;
}

[data-theme="dark"] .hub-empty-roster {
  background: #1e2533;
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