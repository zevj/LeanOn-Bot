<template>
  <div class="layout">
    <SidebarAdmin
      :open="sidebarOpen"
      @toggle="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)"
    />

    <main class="main-area">
      <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)" />

      <div class="main-container">
        <!-- Page Header -->
        <div class="page-header-wrapper">
          <div class="header-title">
            <h1 class="title">AI Analytics & Insights</h1>
            <p class="subtext">Anonymized wellness statistics, patterns, and AI-generated school-wide insights.</p>
          </div>

          <div class="header-actions">
            <!-- Period Tabs Selector -->
            <div class="period-selector">
              <label>Reporting Period:</label>
              <div class="period-tabs">
                <button
                  v-for="p in periods"
                  :key="p.value"
                  class="period-tab"
                  :class="{ active: selectedPeriod === p.value }"
                  @click="changePeriod(p.value)"
                >
                  {{ p.label }}
                </button>
              </div>
            </div>

            <!-- Export Button -->
            <button class="export-btn" @click="openExportModal" :disabled="loadingDashboard">
              <i class="bx bx-download"></i>
              Export Report
            </button>
          </div>
        </div>

        <!-- Global Page Loading State -->
        <div v-if="loadingDashboard" class="loading-overlay">
          <div class="spinner"></div>
          <p>Analyzing and aggregating data snapshots...</p>
        </div>

        <div v-else>
          <!-- Stat Cards Grid -->
          <div class="stat-cards-grid">
            <!-- Card 1: Department With Most Crisis Alerts -->
            <div class="stat-card red">
              <div class="stat-card-content">
                <h4 class="stat-label">Dept. With Most Alerts</h4>
                <p class="stat-value dept-value" :style="{ color: stats.top_department_alerts !== 'N/A' ? '#b91c1c' : '#111827' }">
                  {{ stats.top_department_alerts || 'N/A' }}
                </p>
                <span style="font-size:11px;color:#6b7280;margin-top:2px;">
                  {{ stats.top_department_alerts_count || 0 }} classified alerts
                </span>
              </div>
              <div class="stat-icon-wrapper icon-red"><i class="bx bx-shield-alt-2"></i></div>
            </div>

            <!-- Card 2: Total Conversations -->
            <div class="stat-card green">
              <div class="stat-card-content">
                <h4 class="stat-label">Total Conversations</h4>
                <p class="stat-value">{{ stats.total_conversations || 0 }}</p>
                <span class="stat-growth" :class="getGrowthClass(stats.conversation_growth)">
                  <i :class="getGrowthIcon(stats.conversation_growth)"></i>
                  {{ Math.abs(stats.conversation_growth || 0) }}%
                </span>
              </div>
              <div class="stat-icon-wrapper icon-green"><i class="bx bx-chat"></i></div>
            </div>

            <!-- Card 3: Peak Usage Hour -->
            <div class="stat-card cyan">
              <div class="stat-card-content">
                <h4 class="stat-label">Peak Usage Hour</h4>
                <p class="stat-value unit-suffix">{{ formatPeakHour(stats.peak_hour) }}</p>
                <span style="font-size:11px;color:#6b7280;margin-top:2px;">Highest interaction volume</span>
              </div>
              <div class="stat-icon-wrapper icon-cyan"><i class="bx bx-bell"></i></div>
            </div>

            <!-- Card 4: Crisis Alerts -->
            <div class="stat-card amber">
              <div class="stat-card-content">
                <h4 class="stat-label">Crisis Alerts</h4>
                <p class="stat-value" :style="{ color: stats.crisis_alert_count > 0 ? '#b91c1c' : '#111827' }">
                  {{ stats.crisis_alert_count || 0 }}
                </p>
                <span style="font-size:11px;color:#6b7280;margin-top:2px;">Flagged messages</span>
              </div>
              <div class="stat-icon-wrapper icon-amber"><i class="bx bx-shield"></i></div>
            </div>

            <!-- Card 5: Top Age Range -->
            <div class="stat-card purple">
              <div class="stat-card-content">
                <h4 class="stat-label">Users Age Range</h4>
                <p class="stat-value unit-suffix" style="font-size:22px;font-weight:700;">
                  {{ stats.top_age_range || 'N/A' }}
                </p>
                <span style="font-size:11px;color:#6b7280;margin-top:2px;">
                  {{ stats.top_age_range_count || 0 }} registered students
                </span>
              </div>
              <div class="stat-icon-wrapper icon-purple"><i class="bx bx-group"></i></div>
            </div>
          </div>

          <!-- Charts Row 1 -->
          <div class="charts-section">
            <MoodDistributionChart :data="trendData.emotion_distribution || {}" />
            <PeakUsageChart :data="trendData.peak_usage_hours || []" />
          </div>

          <!-- Charts Row 2 -->
          <div class="charts-section-bottom">
            <SentimentTrendChart :data="trendData.sentiment_over_time || []" />

            <div class="stat-card" style="height:auto;flex-direction:column;align-items:flex-start;justify-content:flex-start;padding:1.5rem;gap:1.25rem;border-left:4px solid #16a34a;background:var(--card-bg,#fff);">
              <div style="display:flex;align-items:center;gap:10px;width:100%;">
                <div class="stat-icon-wrapper icon-green" style="width:36px;height:36px;font-size:1.1rem;">
                  <i class="bx bx-info-circle"></i>
                </div>
                <h3 style="font-size:15px;font-weight:600;color:#111827;margin:0;">System Performance & Privacy</h3>
              </div>
              <div style="display:flex;flex-direction:column;gap:12px;width:100%;font-size:13.5px;color:#4b5563;">
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid #f3f4f6;padding-bottom:8px;">
                  <span>Off-topic Fallbacks:</span>
                  <strong style="color:#111827;">{{ stats.fallback_count || 0 }} times</strong>
                </div>
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid #f3f4f6;padding-bottom:8px;">
                  <span>Active Student Engagement Rate:</span>
                  <strong style="color:#111827;">
                    {{ stats.total_registered_users > 0 ? ((stats.active_users_in_period / stats.total_registered_users) * 100).toFixed(1) : 0 }}%
                  </strong>
                </div>
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid #f3f4f6;padding-bottom:8px;">
                  <span>Total Registered Students:</span>
                  <strong style="color:#111827;">{{ stats.total_registered_users || 0 }}</strong>
                </div>
                <div style="margin-top:8px;font-size:12px;line-height:1.5;color:#6b7280;background:#f9fafb;padding:10px;border-radius:8px;border:1px solid #f3f4f6;">
                  <i class="bx bx-lock-alt" style="margin-right:4px;color:#16a34a;"></i>
                  <strong>Privacy Notice:</strong> All text contents, raw chat sessions, names, and emails are excluded from AI analyses. Only aggregated statistics are shared with AI APIs for school-wide insight extraction.
                </div>
              </div>
            </div>
          </div>

          <!-- AI Insights Section -->
          <div class="ai-insights-section">
            <AIInsightsPanel
              :insights="insightsData.insights"
              :recommendations="insightsData.recommendations"
              :trends="insightsData.trends"
              :wellnessSummary="insightsData.wellness_summary"
              :anomalies="insightsData.anomalies"
              :generatedAt="insightsData.generated_at"
              :loading="loadingInsights"
              :isStale="insightsData.is_stale"
              :isFallback="insightsData.is_fallback"
              :staleMessage="insightsData.stale_message"
              @insights-generated="onInsightsGenerated"
            />
          </div>
        </div>
      </div>
    </main>

    <!-- ── Export Report Modal ── -->
    <Transition name="modal-fade">
      <div v-if="showExportModal" class="export-modal-overlay" @click.self="closeExportModal">
        <div class="export-modal">
          <div class="export-modal-header">
            <div class="export-modal-header-left">
              <div class="export-modal-icon">
                <i class="bx bxs-file-export"></i>
              </div>
              <div>
                <h3 class="export-modal-title">Export Analytics Report</h3>
                <p class="export-modal-subtitle">Choose format, date range, and sections to include</p>
              </div>
            </div>
            <button class="export-modal-close" @click="closeExportModal">
              <i class="bx bx-x"></i>
            </button>
          </div>

          <div class="export-modal-body">
            <!-- Export Format -->
            <div class="export-field-group">
              <label class="export-field-label">Export Format</label>
              <div class="export-format-tabs">
                <button
                  class="export-format-tab"
                  :class="{ active: exportOptions.format === 'pdf' }"
                  @click="exportOptions.format = 'pdf'"
                >
                  <i class="bx bxs-file-pdf"></i> PDF
                </button>
                <button
                  class="export-format-tab"
                  :class="{ active: exportOptions.format === 'csv' }"
                  @click="exportOptions.format = 'csv'"
                >
                  <i class="bx bxs-file-txt"></i> CSV
                </button>
              </div>
            </div>

            <!-- Date Range -->
            <div class="export-field-group">
              <label class="export-field-label">Date Range</label>
              <div class="export-date-mode-tabs">
                <button
                  class="export-period-tab"
                  :class="{ active: exportOptions.dateMode === 'preset' }"
                  @click="exportOptions.dateMode = 'preset'"
                >Preset Period</button>
                <button
                  class="export-period-tab"
                  :class="{ active: exportOptions.dateMode === 'custom' }"
                  @click="exportOptions.dateMode = 'custom'"
                >Custom Range</button>
              </div>

              <!-- Preset period tabs -->
              <div v-if="exportOptions.dateMode === 'preset'" class="export-period-tabs" style="margin-top:8px;">
                <button
                  v-for="p in periods"
                  :key="p.value"
                  class="export-period-tab"
                  :class="{ active: exportOptions.period === p.value }"
                  @click="exportOptions.period = p.value"
                >
                  {{ p.label }}
                </button>
              </div>

              <!-- Custom date inputs -->
              <div v-if="exportOptions.dateMode === 'custom'" class="export-date-range" style="margin-top:8px;">
                <div class="export-date-field">
                  <label class="export-date-label">From</label>
                  <input type="date" v-model="exportOptions.startDate" class="export-date-input" :max="exportOptions.endDate || today" />
                </div>
                <div class="export-date-field">
                  <label class="export-date-label">To</label>
                  <input type="date" v-model="exportOptions.endDate" class="export-date-input" :min="exportOptions.startDate" :max="today" />
                </div>
              </div>
            </div>

            <!-- Sections (PDF only) -->
            <div class="export-field-group" v-if="exportOptions.format === 'pdf'">
              <label class="export-field-label">Include Sections</label>
              <div class="export-sections">
                <label v-for="s in exportSections" :key="s.value" class="export-section-item">
                  <input type="checkbox" v-model="exportOptions.sections" :value="s.value" class="export-checkbox" />
                  <div class="export-section-info">
                    <i :class="s.icon" class="export-section-icon"></i>
                    <div>
                      <span class="export-section-name">{{ s.label }}</span>
                      <span class="export-section-desc">{{ s.desc }}</span>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <div class="export-modal-footer">
            <button class="export-cancel-btn" @click="closeExportModal">Cancel</button>
            <button
              class="export-confirm-btn"
              @click="exportOptions.format === 'csv' ? generateCSV() : generatePDF()"
              :disabled="exportLoading || (exportOptions.format === 'pdf' && exportOptions.sections.length === 0) || (exportOptions.dateMode === 'custom' && (!exportOptions.startDate || !exportOptions.endDate))"
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
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import SidebarAdmin from '@/components/sidebarAdmin.vue'
import HeaderAdmin from '@/components/headerAdmin.vue'
import MoodDistributionChart from '@/components/MoodDistributionChart.vue'
import PeakUsageChart from '@/components/PeakUsageChart.vue'
import SentimentTrendChart from '@/components/SentimentTrendChart.vue'
import AIInsightsPanel from '@/components/AIInsightsPanel.vue'

const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false')

const periods = [
  { label: 'Today', value: '1d' },
  { label: 'Last 7 Days', value: '7d' },
  { label: 'Last 14 Days', value: '14d' },
  { label: 'Last 30 Days', value: '30d' },
  { label: 'Last 90 Days', value: '90d' },
]

const exportSections = [
  { value: 'dashboard', label: 'Dashboard Stats', desc: 'Top dept, top gender, crisis alerts, conversations', icon: 'bx bx-bar-chart-alt-2' },
  { value: 'trends', label: 'Emotion & Sentiment Trends', desc: 'Mood distribution, sentiment over time, peak hours', icon: 'bx bx-trending-up' },
  { value: 'insights', label: 'AI Insights & Recommendations', desc: 'AI-generated observations, trends, and recommendations', icon: 'bx bx-brain' },
  { value: 'snapshots', label: 'Historical Snapshots', desc: 'Daily data snapshots for the selected period', icon: 'bx bx-calendar' },
]

const selectedPeriod = ref('7d')
const loadingDashboard = ref(true)
const loadingInsights = ref(false)
const fetchingDashboard = ref(false)
const fetchingInsights = ref(false)

// Export modal state
const showExportModal = ref(false)
const exportLoading = ref(false)
const today = new Date().toISOString().slice(0, 10)
const exportOptions = ref({
  period: '7d',
  sections: ['dashboard', 'trends', 'insights'],
  format: 'pdf',
  dateMode: 'preset',
  startDate: '',
  endDate: today,
})

const stats = ref({
  total_conversations: 0, conversation_growth: 0,
  peak_hour: null, peak_usage_hours: [],
  crisis_alert_count: 0, crisis_by_severity: {},
  fallback_count: 0, total_registered_users: 0,
  // New replacement cards
  top_department_users: 'N/A', top_department_users_count: 0,
  top_department_alerts: 'N/A', top_department_alerts_count: 0,
  top_gender: 'N/A', top_gender_count: 0,
  // Age range card
  top_age_range: 'N/A', top_age_range_count: 0,
})

const trendData = ref({
  emotion_distribution: {}, sentiment_over_time: [],
  peak_usage_hours: [], weekly_comparison: [],
})

const insightsData = ref({
  insights: [], recommendations: [], trends: [],
  wellness_summary: '', anomalies: [],
  generated_at: '', is_stale: false,
  is_fallback: false, stale_message: '',
})

const changePeriod = (period) => {
  if (selectedPeriod.value === period || fetchingDashboard.value) return
  selectedPeriod.value = period
  fetchData()
}

const formatPeakHour = (hour) => {
  if (hour === null || hour === undefined) return 'N/A'
  if (hour === 0) return '12 AM'
  if (hour === 12) return '12 PM'
  return hour < 12 ? `${hour} AM` : `${hour - 12} PM`
}

const formatGender = (gender) => {
  if (!gender || gender === 'N/A') return 'N/A'
  return gender.charAt(0).toUpperCase() + gender.slice(1).toLowerCase()
}

const getGrowthClass = (val) => {
  if (!val || val === 0) return 'neutral'
  return val > 0 ? 'positive' : 'negative'
}

const getGrowthIcon = (val) => {
  if (!val || val === 0) return 'bx bx-minus'
  return val > 0 ? 'bx bx-trending-up' : 'bx bx-trending-down'
}

const authConfig = () => ({
  headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
})

const fetchData = async () => {
  if (fetchingDashboard.value) return
  fetchingDashboard.value = true
  loadingDashboard.value = true
  try {
    const trendPeriod = selectedPeriod.value === '1d' ? '7d' : selectedPeriod.value
    const [dashRes, trendsRes] = await Promise.all([
      axios.get(`/api/admin/analytics/dashboard?period=${selectedPeriod.value}`, authConfig()),
      axios.get(`/api/admin/analytics/trends?period=${trendPeriod}`, authConfig()),
    ])
    stats.value = dashRes.data
    trendData.value = trendsRes.data
  } catch (err) {
    console.error('Error fetching analytics:', err)
  } finally {
    loadingDashboard.value = false
    fetchingDashboard.value = false
  }
}

const fetchInsights = async () => {
  if (fetchingInsights.value) return
  fetchingInsights.value = true
  loadingInsights.value = true
  try {
    const res = await axios.get('/api/admin/analytics/insights?period=weekly&days=7', authConfig())
    insightsData.value = res.data
  } catch (err) {
    console.error('Error fetching AI insights:', err)
  } finally {
    loadingInsights.value = false
    fetchingInsights.value = false
  }
}

const openExportModal = () => {
  exportOptions.value.period = selectedPeriod.value
  exportOptions.value.format = 'pdf'
  exportOptions.value.dateMode = 'preset'
  exportOptions.value.startDate = ''
  exportOptions.value.endDate = today
  showExportModal.value = true
}

const onInsightsGenerated = (freshData) => {
  insightsData.value = freshData
}

const closeExportModal = () => {
  if (!exportLoading.value) showExportModal.value = false
}

// ── Build export query params ──────────────────────────────────
const buildExportParams = () => {
  const opts = exportOptions.value
  const params = new URLSearchParams()
  if (opts.dateMode === 'custom' && opts.startDate && opts.endDate) {
    params.set('start_date', opts.startDate)
    params.set('end_date', opts.endDate)
  } else {
    params.set('period', opts.period)
  }
  return params
}

// ── PDF Generation ──────────────────────────────────────────────
const generatePDF = async () => {
  exportLoading.value = true
  try {
    const params = buildExportParams()
    const sections = exportOptions.value.sections.join(',')
    params.set('sections', sections)
    params.set('format', 'pdf')
    const res = await axios.get(
      `/api/admin/analytics/export?${params.toString()}`,
      authConfig()
    )
    const data = res.data
    await buildPDF(data)
    closeExportModal()
  } catch (err) {
    console.error('Export failed:', err)
    alert('Failed to generate report. Please try again.')
  } finally {
    exportLoading.value = false
  }
}

// ── CSV Generation ──────────────────────────────────────────────
const generateCSV = async () => {
  exportLoading.value = true
  try {
    const params = buildExportParams()
    params.set('sections', 'dashboard,trends,snapshots')
    params.set('format', 'csv')
    const res = await axios.get(
      `/api/admin/analytics/export?${params.toString()}`,
      authConfig()
    )
    const data = res.data

    // Helper: escape a cell value for CSV (UTF-8 BOM-safe, Excel-compatible)
    const esc = (v) => `"${String(v ?? '').replace(/"/g, '""').replace(/\r?\n/g, ' ')}"`

    // Human-readable date formatter
    const fmtDate = (iso) => {
      if (!iso) return ''
      return new Date(iso).toLocaleDateString('en-PH', {
        month: 'long', day: '2-digit', year: 'numeric',
      })
    }

    const periodLabel = data.period
      ? (periods.find(p => p.value === data.period)?.label || data.period)
      : `${exportOptions.value.startDate} to ${exportOptions.value.endDate}`

    const generatedAt = new Date(data.generated_at).toLocaleString('en-PH', {
      month: 'long', day: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit', hour12: true,
    })

    const refId = `RPT-${Date.now().toString(36).toUpperCase()}`

    const rows = []

    // ── Report header block ──
    rows.push([esc('LeanOn Bot — AI Analytics & Wellness Report')])
    rows.push([esc('Gordon College — Guidance & Counseling Office')])
    rows.push([esc(`Reporting Period: ${periodLabel}`)])
    rows.push([esc(`Generated: ${generatedAt}`)])
    rows.push([esc(`Export Reference: ${refId}`)])
    rows.push([esc('Privacy Notice: All data is anonymized. No student PII is included.')])
    rows.push([])

    // ── Dashboard Statistics ──
    if (data.dashboard) {
      const d = data.dashboard
      rows.push([esc('=== DASHBOARD STATISTICS ===')])
      rows.push([esc('Metric'), esc('Value')])
      rows.push([esc('Department With Most Crisis Alerts'), esc(d.top_department_alerts ?? 'N/A')])
      rows.push([esc('Crisis Alerts in Top Department'),   esc(d.top_department_alerts_count ?? 0)])
      rows.push([esc('Total Conversations'),               esc(d.total_conversations ?? 0)])
      rows.push([esc('Conversation Growth (%)'),           esc(d.conversation_growth ?? 0)])
      rows.push([esc('Peak Usage Hour'),                   esc(d.peak_hour !== null ? formatPeakHour(d.peak_hour) : 'N/A')])
      rows.push([esc('Crisis Alerts (Period)'),            esc(d.crisis_alert_count ?? 0)])
      rows.push([esc('Off-Topic Fallbacks'),               esc(d.fallback_count ?? 0)])
      rows.push([esc('Total Registered Students'),         esc(d.total_registered_users ?? 0)])
      rows.push([esc('Most Active Age Range'),             esc(d.top_age_range ?? 'N/A')])
      rows.push([esc('Students in Top Age Range'),         esc(d.top_age_range_count ?? 0)])
      rows.push([])
    }

    // ── Emotion Distribution ──
    if (data.trends?.emotion_distribution && Object.keys(data.trends.emotion_distribution).length > 0) {
      rows.push([esc('=== EMOTION DISTRIBUTION ===')])
      rows.push([esc('Emotion'), esc('Count'), esc('Percentage of Total'), esc('Relative Rank')])
      const total = Object.values(data.trends.emotion_distribution).reduce((a, b) => a + b, 0)
      const sorted = Object.entries(data.trends.emotion_distribution)
        .sort(([, a], [, b]) => b - a)
      sorted.forEach(([emotion, count], idx) => {
        rows.push([
          esc(emotion.charAt(0).toUpperCase() + emotion.slice(1)),
          esc(count),
          esc(total > 0 ? `${((count / total) * 100).toFixed(1)}%` : '0%'),
          esc(`#${idx + 1}`),
        ])
      })
      rows.push([])
    }

    // ── Sentiment Over Time ──
    if (data.trends?.sentiment_over_time?.length > 0) {
      rows.push([esc('=== SENTIMENT TREND (WEEKLY) ===')])
      rows.push([esc('Week Starting'), esc('Positive'), esc('Neutral'), esc('Negative'), esc('Dominant Sentiment')])
      data.trends.sentiment_over_time.forEach((w, i) => {
        const pos = w.positive ?? 0
        const neu = w.neutral  ?? 0
        const neg = w.negative ?? 0
        const dominant = pos >= neu && pos >= neg ? 'Positive'
                       : neg >= pos && neg >= neu ? 'Negative'
                       : 'Neutral'
        rows.push([
          esc(w.week_start ? fmtDate(w.week_start) : `Week ${i + 1}`),
          esc(pos), esc(neu), esc(neg), esc(dominant),
        ])
      })
      rows.push([])
    }

    // ── Peak Usage Hours ──
    if (data.trends?.peak_usage_hours?.length > 0) {
      rows.push([esc('=== PEAK USAGE HOURS ===')])
      rows.push([esc('Hour'), esc('Session Count')])
      data.trends.peak_usage_hours.forEach(h => {
        rows.push([esc(formatPeakHour(h.hour)), esc(h.count ?? 0)])
      })
      rows.push([])
    }

    // ── Historical Daily Snapshots ──
    if (data.snapshots?.length > 0) {
      rows.push([esc('=== HISTORICAL DAILY SNAPSHOTS ===')])
      rows.push([
        esc('Date'),
        esc('Daily Active Users'),
        esc('Total Conversations'),
        esc('Total Messages'),
        esc('Avg Session Duration (min)'),
        esc('Crisis Alerts'),
      ])
      data.snapshots.forEach(s => {
        rows.push([
          esc(s.snapshot_date ? fmtDate(s.snapshot_date) : ''),
          esc(s.daily_active_users   ?? 0),
          esc(s.total_conversations  ?? 0),
          esc(s.total_messages       ?? 0),
          esc(s.avg_session_minutes  ?? 0),
          esc(s.crisis_alert_count   ?? 0),
        ])
      })
      rows.push([])
    }

    rows.push([esc(`End of Report — ${refId}`)])

    // UTF-8 BOM ensures Excel opens with correct encoding
    const csvContent = '\uFEFF' + rows.map(r => r.join(',')).join('\r\n')
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url  = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href  = url
    const dateSuffix = exportOptions.value.dateMode === 'custom'
      ? `${exportOptions.value.startDate}_${exportOptions.value.endDate}`
      : exportOptions.value.period
    link.download = `LeanOn-Analytics-${dateSuffix}-${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(url)
    closeExportModal()
  } catch (err) {
    console.error('CSV export failed:', err)
    alert('Failed to generate CSV. Please try again.')
  } finally {
    exportLoading.value = false
  }
}

// ── PDF shared helpers ──────────────────────────────────────────
const PDF_GREEN      = [14, 96, 8]
const PDF_GREEN_MID  = [22, 163, 74]
const PDF_GREEN_DARK = [10, 68, 6]
const PDF_TEXT_DARK  = [17, 24, 39]
const PDF_TEXT_MID   = [75, 85, 99]
const PDF_TEXT_LIGHT = [156, 163, 175]
const PDF_ROW_ALT    = [247, 250, 247]
const PDF_BORDER     = [229, 231, 235]

const pdfMakeRefId = () => `RPT-${Date.now().toString(36).toUpperCase()}-${Math.random().toString(36).substring(2,5).toUpperCase()}`

// Load an image from /public as a base64 data URL for jsPDF
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

  // Dark top stripe
  doc.setFillColor(...PDF_GREEN_DARK)
  doc.rect(0, BANNER_TOP, W, 7, 'F')
  // Main green banner
  doc.setFillColor(...PDF_GREEN)
  doc.rect(0, 7, W, BANNER_H - 7, 'F')
  // Right accent block (narrower — leaves room for logos)
  doc.setFillColor(...PDF_GREEN_MID)
  doc.rect(W - 38, 7, 38, BANNER_H - 7, 'F')

  // ── Logos — vertically centered in the banner content area (y7 → y42 = 35mm tall) ──
  const LOGO_SIZE = 22          // both logos same square size
  const LOGO_Y    = 7 + (35 - LOGO_SIZE) / 2   // = 13.5 — perfectly centered
  const GC_X      = M           // Gordon College seal — left margin
  const LB_X      = GC_X + LOGO_SIZE + 3        // LeanOn Bot — 3mm gap after GC

  const gcLogo = await loadImageAsDataUrl('/GordonCollegeLogo.png')
  if (gcLogo) {
    doc.addImage(gcLogo, 'PNG', GC_X, LOGO_Y, LOGO_SIZE, LOGO_SIZE)
  }

  // Thin white vertical divider between logos
  doc.setDrawColor(255, 255, 255)
  doc.setLineWidth(0.4)
  doc.line(LB_X - 1.5, LOGO_Y + 2, LB_X - 1.5, LOGO_Y + LOGO_SIZE - 2)

  const lbLogo = await loadImageAsDataUrl('/leanOnBot.png')
  if (lbLogo) {
    doc.addImage(lbLogo, 'PNG', LB_X, LOGO_Y, LOGO_SIZE, LOGO_SIZE)
  }

  // Text starts after both logos + gap
  const textX = LB_X + LOGO_SIZE + 5

  // Institution label — near top of banner content
  doc.setFontSize(6.5)
  doc.setFont('helvetica', 'normal')
  doc.setTextColor(187, 247, 208)
  doc.text('GORDON COLLEGE  ·  GUIDANCE & COUNSELING OFFICE  ·  LEANON BOT', textX, 15)

  // Report title — vertically centered in banner
  doc.setFontSize(14)
  doc.setFont('helvetica', 'bold')
  doc.setTextColor(255, 255, 255)
  doc.text(title, textX, 25)

  // Period + generated
  doc.setFontSize(7.5)
  doc.setFont('helvetica', 'normal')
  doc.setTextColor(187, 247, 208)
  const metaText = `Period: ${periodLabel}   ·   Generated: ${generatedAt}`
  doc.text(metaText, textX, 34, { maxWidth: W - textX - 42 })

  // Right badge text — vertically centered in banner content (7→42)
  doc.setFontSize(7)
  doc.setFont('helvetica', 'bold')
  doc.setTextColor(255, 255, 255)
  doc.text('CONFIDENTIAL', W - 19, 19, { align: 'center' })
  doc.setFont('helvetica', 'normal')
  doc.setFontSize(6)
  doc.text('Admin Use Only', W - 19, 25, { align: 'center' })
  doc.setFontSize(5.5)
  doc.text(`Ref: ${refId}`, W - 19, 30, { align: 'center' })

  // Separator line
  doc.setDrawColor(...PDF_BORDER)
  doc.setLineWidth(0.25)
  doc.line(0, BANNER_H + 1, W, BANNER_H + 1)

  // Privacy notice below banner
  doc.setFontSize(7)
  doc.setFont('helvetica', 'italic')
  doc.setTextColor(...PDF_TEXT_MID)
  doc.text('All data is anonymized. No student PII is included in this report.', M, BANNER_H + 7)

  return BANNER_H + 13 // starting Y after header
}

const pdfDrawFooter = (doc, refId) => {
  const count = doc.internal.getNumberOfPages()
  const W = doc.internal.pageSize.getWidth()
  const H = doc.internal.pageSize.getHeight()
  for (let i = 1; i <= count; i++) {
    doc.setPage(i)
    // Footer separator line
    doc.setDrawColor(...PDF_BORDER)
    doc.setLineWidth(0.25)
    doc.line(14, H - 14, W - 14, H - 14)
    doc.setFontSize(6.5)
    doc.setFont('helvetica', 'normal')
    doc.setTextColor(...PDF_TEXT_LIGHT)
    // Left: system label
    doc.text('LeanOn Bot  ·  Gordon College  ·  Confidential — For authorized personnel only', 14, H - 9)
    // Left second line: ref ID (below the label, no collision)
    doc.setFontSize(6)
    doc.text(`Export Ref: ${refId}`, 14, H - 5)
    // Right: page number (aligned to bottom-right, vertically centered between the two lines)
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
    fontSize: 8,
    textColor: PDF_TEXT_DARK,
    cellPadding: { top: 3, bottom: 3, left: 5, right: 5 },
  },
  alternateRowStyles: { fillColor: PDF_ROW_ALT },
  styles: { lineColor: PDF_BORDER, lineWidth: 0.2, overflow: 'linebreak', font: 'helvetica' },
  theme: 'grid',
})

const buildPDF = async (data) => {
  const doc   = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })
  const pageW = doc.internal.pageSize.getWidth()
  const margin = 14
  const refId  = pdfMakeRefId()

  const periodLabel = data.period
    ? (periods.find(p => p.value === data.period)?.label || data.period)
    : `${exportOptions.value.startDate} to ${exportOptions.value.endDate}`

  const generatedAt = new Date(data.generated_at).toLocaleString('en-PH', {
    month: 'long', day: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hour12: true,
  })

  let y = await pdfDrawHeader(doc, 'AI Analytics & Wellness Report', periodLabel, generatedAt, refId)

  doc.setTextColor(...PDF_TEXT_DARK)

  // ── Dashboard Stats ──
  if (data.dashboard) {
    const d = data.dashboard
    y = pdfSectionHeading(doc, 'Dashboard Statistics', y, margin)

    // Summary metric cards (2-column layout)
    const metrics = [
      { label: 'Total Conversations',          value: String(d.total_conversations ?? 0),          color: PDF_GREEN },
      { label: 'Crisis Alerts',                value: String(d.crisis_alert_count ?? 0),            color: [185, 28, 28] },
      { label: 'Total Registered Students',    value: String(d.total_registered_users ?? 0),        color: [14, 116, 144] },
      { label: 'Off-Topic Fallbacks',          value: String(d.fallback_count ?? 0),                color: [107, 114, 128] },
    ]
    const cardW = (pageW - margin * 2 - 6) / 4
    metrics.forEach((m, i) => {
      const bx = margin + i * (cardW + 2)
      doc.setFillColor(248, 250, 248)
      doc.setDrawColor(...m.color)
      doc.setLineWidth(0.35)
      doc.roundedRect(bx, y, cardW, 14, 1.5, 1.5, 'FD')
      doc.setFillColor(...m.color)
      doc.roundedRect(bx, y, 2.5, 14, 1, 1, 'F')
      doc.setFontSize(12)
      doc.setFont('helvetica', 'bold')
      doc.setTextColor(...m.color)
      doc.text(m.value, bx + cardW / 2, y + 7, { align: 'center' })
      doc.setFontSize(5.5)
      doc.setFont('helvetica', 'normal')
      doc.setTextColor(...PDF_TEXT_MID)
      doc.text(m.label.toUpperCase(), bx + cardW / 2, y + 11.5, { align: 'center' })
    })
    y += 20

    autoTable(doc, {
      ...pdfTableStyles(),
      startY: y,
      head: [['Metric', 'Value']],
      body: [
        ['Department With Most Crisis Alerts', d.top_department_alerts ?? 'N/A'],
        ['Crisis Alerts in Top Department',    String(d.top_department_alerts_count ?? 0)],
        ['Total Conversations',                String(d.total_conversations ?? 0)],
        ['Conversation Growth',                `${d.conversation_growth ?? 0}%`],
        ['Peak Usage Hour',                    d.peak_hour !== null ? formatPeakHour(d.peak_hour) : 'N/A'],
        ['Crisis Alerts (Period)',             String(d.crisis_alert_count ?? 0)],
        ['Off-Topic Fallbacks',               String(d.fallback_count ?? 0)],
        ['Total Registered Students',         String(d.total_registered_users ?? 0)],
        ['Most Active Age Range',             d.top_age_range ?? 'N/A'],
        ['Students in Top Age Range',         String(d.top_age_range_count ?? 0)],
      ],
      columnStyles: {
        0: { cellWidth: 110, fontStyle: 'bold' },
        1: { cellWidth: 'auto', halign: 'center' },
      },
      margin: { left: margin, right: margin },
    })
    y = doc.lastAutoTable.finalY + 10
  }

  // ── Emotion Distribution ──
  if (data.trends?.emotion_distribution && Object.keys(data.trends.emotion_distribution).length > 0) {
    if (y > 230) { doc.addPage(); y = 20 }
    y = pdfSectionHeading(doc, 'Emotion Distribution', y, margin)

    const total = Object.values(data.trends.emotion_distribution).reduce((a, b) => a + b, 0)
    const emotionColors = {
      happy: [22, 163, 74], sad: [59, 130, 246], angry: [239, 68, 68],
      anxious: [245, 158, 11], neutral: [107, 114, 128], fearful: [168, 85, 247],
      disgusted: [234, 88, 12], surprised: [14, 116, 144],
    }
    const sortedEmotions = Object.entries(data.trends.emotion_distribution).sort(([,a],[,b]) => b - a)

    autoTable(doc, {
      ...pdfTableStyles(),
      startY: y,
      head: [['Emotion', 'Count', 'Percentage', 'Rank']],
      body: sortedEmotions.map(([emotion, count], idx) => [
        emotion.charAt(0).toUpperCase() + emotion.slice(1),
        String(count),
        total > 0 ? `${((count / total) * 100).toFixed(1)}%` : '0%',
        `#${idx + 1}`,
      ]),
      columnStyles: {
        0: { cellWidth: 60, fontStyle: 'bold' },
        1: { cellWidth: 30, halign: 'center' },
        2: { cellWidth: 40, halign: 'center' },
        3: { cellWidth: 25, halign: 'center' },
      },
      didParseCell(data) {
        if (data.section === 'body' && data.column.index === 0) {
          const key = String(data.cell.raw).toLowerCase()
          const c = emotionColors[key]
          if (c) data.cell.styles.textColor = c
        }
      },
      margin: { left: margin, right: margin },
    })
    y = doc.lastAutoTable.finalY + 10
  }

  // ── Sentiment Over Time ──
  if (data.trends?.sentiment_over_time?.length > 0) {
    if (y > 220) { doc.addPage(); y = 20 }
    y = pdfSectionHeading(doc, 'Sentiment Trend (Weekly)', y, margin)

    autoTable(doc, {
      ...pdfTableStyles(),
      startY: y,
      head: [['Week Starting', 'Positive', 'Neutral', 'Negative', 'Dominant']],
      body: data.trends.sentiment_over_time.map((w, i) => {
        const pos = w.positive ?? 0
        const neu = w.neutral  ?? 0
        const neg = w.negative ?? 0
        const dom = pos >= neu && pos >= neg ? 'Positive'
                  : neg >= pos && neg >= neu ? 'Negative' : 'Neutral'
        return [
          w.week_start
            ? new Date(w.week_start).toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' })
            : `Week ${i + 1}`,
          String(pos), String(neu), String(neg), dom,
        ]
      }),
      columnStyles: {
        0: { cellWidth: 45 },
        1: { cellWidth: 28, halign: 'center' },
        2: { cellWidth: 28, halign: 'center' },
        3: { cellWidth: 28, halign: 'center' },
        4: { cellWidth: 35, halign: 'center', fontStyle: 'bold' },
      },
      didParseCell(data) {
        if (data.section === 'body' && data.column.index === 4) {
          const v = String(data.cell.raw)
          data.cell.styles.textColor =
            v === 'Positive' ? PDF_GREEN :
            v === 'Negative' ? [185, 28, 28] : PDF_TEXT_MID
        }
      },
      margin: { left: margin, right: margin },
    })
    y = doc.lastAutoTable.finalY + 10
  }

  // ── AI Insights ──
  if (data.insights) {
    const ins = data.insights
    if (y > 200) { doc.addPage(); y = 20 }
    y = pdfSectionHeading(doc, 'AI-Generated Insights & Recommendations', y, margin)

    // Small italic note — same style as privacy notice
    doc.setFontSize(7.5)
    doc.setFont('helvetica', 'italic')
    doc.setTextColor(...PDF_TEXT_MID)
    doc.text('Note: This report contains AI-generated insights and should be reviewed by authorized personnel.', margin, y)
    y += 7
    if (ins.wellness_summary) {
      doc.setFillColor(240, 253, 244)
      doc.setDrawColor(...PDF_GREEN)
      doc.setLineWidth(0.3)
      doc.roundedRect(margin, y, pageW - margin * 2, 2, 1, 1, 'FD')
      doc.setFillColor(240, 253, 244)
      doc.setDrawColor(...PDF_GREEN)
      const summaryLines = doc.splitTextToSize(ins.wellness_summary, pageW - margin * 2 - 12)
      const boxH = summaryLines.length * 4.5 + 10
      doc.roundedRect(margin, y, pageW - margin * 2, boxH, 2, 2, 'FD')
      doc.setFillColor(...PDF_GREEN)
      doc.roundedRect(margin, y, 3, boxH, 1, 1, 'F')
      doc.setFontSize(7.5)
      doc.setFont('helvetica', 'bold')
      doc.setTextColor(...PDF_GREEN)
      doc.text('WELLNESS SUMMARY', margin + 7, y + 5)
      doc.setFont('helvetica', 'normal')
      doc.setTextColor(...PDF_TEXT_DARK)
      doc.text(summaryLines, margin + 7, y + 10)
      y += boxH + 6
    }

    // Insights table
    if (ins.insights?.length > 0) {
      if (y > 220) { doc.addPage(); y = 20 }
      const severityColors = { severe: [185,28,28], high: [185,28,28], moderate: [180,83,9], medium: [180,83,9], low: [22,163,74], info: [14,116,144] }
      autoTable(doc, {
        ...pdfTableStyles(),
        startY: y,
        head: [['Category', 'Title', 'Observation', 'Severity']],
        body: ins.insights.map(i => [
          (i.category || 'general').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()),
          i.title || '',
          i.text  || '',
          (i.severity || 'info').toUpperCase(),
        ]),
        columnStyles: {
          0: { cellWidth: 28, fontStyle: 'bold' },
          1: { cellWidth: 38, fontStyle: 'bold' },
          2: { cellWidth: 90 },
          3: { cellWidth: 22, halign: 'center', fontStyle: 'bold' },
        },
        didParseCell(data) {
          if (data.section === 'body' && data.column.index === 3) {
            const key = String(data.cell.raw).toLowerCase()
            const c = severityColors[key] || PDF_TEXT_MID
            data.cell.styles.textColor = c
          }
        },
        margin: { left: margin, right: margin },
      })
      y = doc.lastAutoTable.finalY + 8
    }

    // Recommendations
    if (ins.recommendations?.length > 0) {
      if (y > 220) { doc.addPage(); y = 20 }
      y = pdfSectionHeading(doc, 'Recommendations', y, margin)
      const priorityColors = { high: [185,28,28], medium: [180,83,9], low: [22,163,74] }
      autoTable(doc, {
        ...pdfTableStyles(),
        startY: y,
        head: [['#', 'Priority', 'Recommendation']],
        body: ins.recommendations.map((r, i) => [
          String(i + 1),
          (r.priority || 'medium').toUpperCase(),
          r.text || '',
        ]),
        columnStyles: {
          0: { cellWidth: 10, halign: 'center' },
          1: { cellWidth: 22, halign: 'center', fontStyle: 'bold' },
          2: { cellWidth: 'auto' },
        },
        didParseCell(data) {
          if (data.section === 'body' && data.column.index === 1) {
            const key = String(data.cell.raw).toLowerCase()
            const c = priorityColors[key] || PDF_TEXT_MID
            data.cell.styles.textColor = c
          }
        },
        margin: { left: margin, right: margin },
      })
      y = doc.lastAutoTable.finalY + 8
    }

    // Observed Trends
    if (ins.trends?.length > 0) {
      if (y > 220) { doc.addPage(); y = 20 }
      y = pdfSectionHeading(doc, 'Observed Trends', y, margin)
      const dirColors = { up: PDF_GREEN, increasing: PDF_GREEN, down: [185,28,28], decreasing: [185,28,28], stable: PDF_TEXT_MID }
      autoTable(doc, {
        ...pdfTableStyles(),
        startY: y,
        head: [['Metric', 'Direction', 'Description']],
        body: ins.trends.map(t => [t.metric || '', (t.direction || '').toUpperCase(), t.description || '']),
        columnStyles: {
          0: { cellWidth: 40, fontStyle: 'bold' },
          1: { cellWidth: 28, halign: 'center', fontStyle: 'bold' },
          2: { cellWidth: 'auto' },
        },
        didParseCell(data) {
          if (data.section === 'body' && data.column.index === 1) {
            const key = String(data.cell.raw).toLowerCase()
            const c = dirColors[key] || PDF_TEXT_MID
            data.cell.styles.textColor = c
          }
        },
        margin: { left: margin, right: margin },
      })
      y = doc.lastAutoTable.finalY + 8
    }
  }

  // ── Snapshots ──
  if (data.snapshots?.length > 0) {
    if (y > 200) { doc.addPage(); y = 20 }
    y = pdfSectionHeading(doc, 'Historical Daily Snapshots', y, margin)

    autoTable(doc, {
      ...pdfTableStyles(),
      startY: y,
      head: [['Date', 'Active Users', 'Conversations', 'Messages', 'Avg Session (min)', 'Crisis Alerts']],
      body: data.snapshots.map(s => [
        s.snapshot_date
          ? new Date(s.snapshot_date).toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' })
          : '',
        String(s.daily_active_users  ?? 0),
        String(s.total_conversations ?? 0),
        String(s.total_messages      ?? 0),
        String(s.avg_session_minutes ?? 0),
        String(s.crisis_alert_count  ?? 0),
      ]),
      columnStyles: {
        0: { cellWidth: 32 },
        1: { cellWidth: 26, halign: 'center' },
        2: { cellWidth: 30, halign: 'center' },
        3: { cellWidth: 24, halign: 'center' },
        4: { cellWidth: 34, halign: 'center' },
        5: { cellWidth: 26, halign: 'center' },
      },
      didParseCell(data) {
        if (data.section === 'body' && data.column.index === 5) {
          const v = parseInt(data.cell.raw, 10)
          if (v > 0) data.cell.styles.textColor = [185, 28, 28]
        }
      },
      margin: { left: margin, right: margin },
    })
  }

  // ── Footer on all pages ──
  pdfDrawFooter(doc, refId)

  const dateSuffix = exportOptions.value.dateMode === 'custom'
    ? `${exportOptions.value.startDate}_${exportOptions.value.endDate}`
    : (exportOptions.value.period || 'custom')
  doc.save(`LeanOn-Analytics-${dateSuffix}-${new Date().toISOString().slice(0, 10)}.pdf`)
}

onMounted(() => {
  fetchData()
  fetchInsights()
})
</script>

<style scoped src="@/assets/admin/adminAnalytics.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>

<style scoped>
/* ── Export Format Tabs ── */
.export-format-tabs {
  display: flex;
  gap: 8px;
}

.export-format-tab {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 9px 20px;
  border-radius: 9px;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  color: #6b7280;
  font-family: 'DM Sans', system-ui, sans-serif;
  transition: all 0.18s ease;
}

.export-format-tab:hover:not(.active) { background: #e5e7eb; color: #374151; }

.export-format-tab.active {
  background: linear-gradient(135deg, #0E6008 0%, #16a34a 100%);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 2px 8px rgba(14, 96, 8, 0.25);
}

.export-format-tab i { font-size: 16px; }

/* ── Date Mode Tabs ── */
.export-date-mode-tabs {
  display: flex;
  gap: 6px;
  margin-bottom: 2px;
}

/* ── Custom Date Range ── */
.export-date-range {
  display: flex;
  gap: 12px;
}

.export-date-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.export-date-label {
  font-size: 11px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.export-date-input {
  padding: 8px 10px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 13px;
  color: #111827;
  background: #fff;
  font-family: 'DM Sans', system-ui, sans-serif;
  transition: border-color 0.18s;
  width: 100%;
}

.export-date-input:focus {
  outline: none;
  border-color: #0E6008;
  box-shadow: 0 0 0 3px rgba(14, 96, 8, 0.1);
}

/* ── Header Actions Row ── */
.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* ── Export Button ── */
.export-btn {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 9px 18px;
  background: linear-gradient(135deg, #0E6008 0%, #16a34a 100%);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  font-family: 'DM Sans', system-ui, sans-serif;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 3px 10px rgba(14, 96, 8, 0.22);
  white-space: nowrap;
}

.export-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(14, 96, 8, 0.32);
  background: linear-gradient(135deg, #0b4e06 0%, #15803d 100%);
}

.export-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
}

.export-btn i { font-size: 17px; }

/* ── Export Modal Overlay ── */
.export-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(3px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1rem;
}

.export-modal {
  background: #ffffff;
  border-radius: 18px;
  border: 1px solid #e5e7eb;
  width: 520px;
  max-width: 95vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 24px 40px -8px rgba(0, 0, 0, 0.14), 0 8px 16px -4px rgba(0, 0, 0, 0.06);
}

/* ── Modal Header ── */
.export-modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fafafa;
  flex-shrink: 0;
}

.export-modal-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.export-modal-icon {
  width: 42px;
  height: 42px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #dc2626;
  font-size: 22px;
}

.export-modal-title {
  font-size: 16px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.export-modal-subtitle {
  font-size: 12.5px;
  color: #6b7280;
  margin: 2px 0 0;
}

.export-modal-close {
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
  transition: all 0.2s;
}

.export-modal-close:hover { background: #f3f4f6; color: #111827; }

/* ── Modal Body ── */
.export-modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 20px;
  overflow-y: auto;
  flex: 1;
}

.export-field-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.export-field-label {
  font-size: 11.5px;
  font-weight: 700;
  color: #4b5563;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

/* Period tabs inside modal */
.export-period-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.export-period-tab {
  padding: 7px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  color: #6b7280;
  cursor: pointer;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  font-family: 'DM Sans', system-ui, sans-serif;
  transition: all 0.18s ease;
}

.export-period-tab:hover:not(.active) { background: #e5e7eb; color: #374151; }

.export-period-tab.active {
  background: linear-gradient(135deg, #0E6008 0%, #16a34a 100%);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 2px 8px rgba(14, 96, 8, 0.25);
}

/* Section checkboxes */
.export-sections {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.export-section-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.18s ease;
  background: #fafafa;
}

.export-section-item:hover { background: #f0fdf4; border-color: #bbf7d0; }

.export-checkbox {
  width: 16px;
  height: 16px;
  accent-color: #0E6008;
  cursor: pointer;
  flex-shrink: 0;
}

.export-section-info {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}

.export-section-icon {
  font-size: 20px;
  color: #0E6008;
  flex-shrink: 0;
}

.export-section-name {
  display: block;
  font-size: 13.5px;
  font-weight: 600;
  color: #111827;
}

.export-section-desc {
  display: block;
  font-size: 12px;
  color: #6b7280;
  margin-top: 1px;
}

/* ── Modal Footer ── */
.export-modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-shrink: 0;
  background: #fafafa;
}

.export-cancel-btn {
  padding: 10px 20px;
  border: 1px solid #d1d5db;
  border-radius: 9px;
  background: #fff;
  color: #374151;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  font-family: 'DM Sans', system-ui, sans-serif;
  transition: all 0.18s ease;
}

.export-cancel-btn:hover { background: #f3f4f6; border-color: #9ca3af; }

.export-confirm-btn {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 10px 22px;
  background: linear-gradient(135deg, #0E6008 0%, #16a34a 100%);
  color: #fff;
  border: none;
  border-radius: 9px;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  font-family: 'DM Sans', system-ui, sans-serif;
  transition: all 0.25s ease;
  box-shadow: 0 3px 10px rgba(14, 96, 8, 0.22);
}

.export-confirm-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 14px rgba(14, 96, 8, 0.3);
}

.export-confirm-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-spinner {
  width: 15px;
  height: 15px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-left-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}

@keyframes spin { 100% { transform: rotate(360deg); } }

/* ── Modal Transition ── */
.modal-fade-enter-active { transition: opacity 0.22s ease; }
.modal-fade-leave-active { transition: opacity 0.18s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

.modal-fade-enter-active .export-modal {
  animation: modal-pop 0.28s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

@keyframes modal-pop {
  from { opacity: 0; transform: scale(0.94) translateY(12px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}

@media (max-width: 600px) {
  .header-actions { flex-direction: column; align-items: flex-start; }
  .export-btn { width: 100%; justify-content: center; }
}
</style>
