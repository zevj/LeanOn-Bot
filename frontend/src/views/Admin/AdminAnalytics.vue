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
            <!-- Card 1: Department With Most Users -->
            <div class="stat-card blue">
              <div class="stat-card-content">
                <h4 class="stat-label">Dept. With Most Users</h4>
                <p class="stat-value dept-value">{{ stats.top_department_users || 'N/A' }}</p>
                <span style="font-size:11px;color:#6b7280;margin-top:2px;">
                  {{ stats.top_department_users_count || 0 }} registered students
                </span>
              </div>
              <div class="stat-icon-wrapper icon-blue"><i class="bx bx-buildings"></i></div>
            </div>

            <!-- Card 2: Department With Most Crisis Alerts -->
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

            <!-- Card 3: Gender That Uses System Most -->
            <div class="stat-card purple">
              <div class="stat-card-content">
                <h4 class="stat-label">Top Gender Using System</h4>
                <p class="stat-value">{{ formatGender(stats.top_gender) }}</p>
                <span style="font-size:11px;color:#6b7280;margin-top:2px;">
                  {{ stats.top_gender_count || 0 }} students
                </span>
              </div>
              <div class="stat-icon-wrapper icon-purple"><i class="bx bx-user-circle"></i></div>
            </div>

            <!-- Card 4: Total Conversations (kept) -->
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

            <!-- Card 5: Peak Usage Hour (kept) -->
            <div class="stat-card cyan">
              <div class="stat-card-content">
                <h4 class="stat-label">Peak Usage Hour</h4>
                <p class="stat-value unit-suffix">{{ formatPeakHour(stats.peak_hour) }}</p>
                <span style="font-size:11px;color:#6b7280;margin-top:2px;">Highest interaction volume</span>
              </div>
              <div class="stat-icon-wrapper icon-cyan"><i class="bx bx-bell"></i></div>
            </div>

            <!-- Card 6: Crisis Alerts (kept) -->
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
                    {{ stats.total_registered_users > 0 ? ((stats.daily_active_users / stats.total_registered_users) * 100).toFixed(1) : 0 }}%
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
                <i class="bx bxs-file-pdf"></i>
              </div>
              <div>
                <h3 class="export-modal-title">Export Analytics Report</h3>
                <p class="export-modal-subtitle">Choose what to include in your PDF report</p>
              </div>
            </div>
            <button class="export-modal-close" @click="closeExportModal">
              <i class="bx bx-x"></i>
            </button>
          </div>

          <div class="export-modal-body">
            <!-- Period -->
            <div class="export-field-group">
              <label class="export-field-label">Reporting Period</label>
              <div class="export-period-tabs">
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
            </div>

            <!-- Sections -->
            <div class="export-field-group">
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
              @click="generatePDF"
              :disabled="exportLoading || exportOptions.sections.length === 0"
            >
              <span v-if="exportLoading" class="btn-spinner"></span>
              <i v-else class="bx bx-download"></i>
              {{ exportLoading ? 'Generating...' : 'Download PDF' }}
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
const exportOptions = ref({
  period: '7d',
  sections: ['dashboard', 'trends', 'insights'],
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
    const res = await axios.get('/api/admin/analytics/insights?period=weekly', authConfig())
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
  showExportModal.value = true
}

const onInsightsGenerated = (freshData) => {
  insightsData.value = freshData
}

const closeExportModal = () => {
  if (!exportLoading.value) showExportModal.value = false
}

// ── PDF Generation ──────────────────────────────────────────────
const generatePDF = async () => {
  exportLoading.value = true
  try {
    const sections = exportOptions.value.sections.join(',')
    const res = await axios.get(
      `/api/admin/analytics/export?period=${exportOptions.value.period}&sections=${sections}`,
      authConfig()
    )
    const data = res.data
    buildPDF(data)
    closeExportModal()
  } catch (err) {
    console.error('Export failed:', err)
    alert('Failed to generate report. Please try again.')
  } finally {
    exportLoading.value = false
  }
}

const buildPDF = (data) => {
  const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })
  const pageW = doc.internal.pageSize.getWidth()
  const margin = 14
  let y = 0

  // ── Header ──
  doc.setFillColor(14, 96, 8)
  doc.rect(0, 0, pageW, 28, 'F')
  doc.setTextColor(255, 255, 255)
  doc.setFontSize(18)
  doc.setFont('helvetica', 'bold')
  doc.text('LeanOn Bot — Analytics Report', margin, 12)
  doc.setFontSize(9)
  doc.setFont('helvetica', 'normal')
  const periodLabel = periods.find(p => p.value === data.period)?.label || data.period
  doc.text(`Period: ${periodLabel}   |   Generated: ${new Date(data.generated_at).toLocaleString('en-PH')}`, margin, 20)
  doc.text('All data is anonymized. No student PII is included.', margin, 25)
  y = 36

  doc.setTextColor(17, 24, 39)

  // ── Dashboard Stats ──
  if (data.dashboard) {
    const d = data.dashboard
    doc.setFontSize(13)
    doc.setFont('helvetica', 'bold')
    doc.text('Dashboard Statistics', margin, y)
    y += 6

    autoTable(doc, {
      startY: y,
      head: [['Metric', 'Value']],
      body: [
        ['Dept. With Most Users',        d.top_department_users        ?? 'N/A'],
        ['Users in Top Dept.',           String(d.top_department_users_count ?? 0)],
        ['Dept. With Most Crisis Alerts', d.top_department_alerts      ?? 'N/A'],
        ['Alerts in Top Dept.',          String(d.top_department_alerts_count ?? 0)],
        ['Top Gender Using System',      d.top_gender                  ?? 'N/A'],
        ['Students (Top Gender)',        String(d.top_gender_count     ?? 0)],
        ['Total Conversations',          String(d.total_conversations  ?? 0)],
        ['Peak Usage Hour',              d.peak_hour !== null ? formatPeakHour(d.peak_hour) : 'N/A'],
        ['Crisis Alerts (Period)',        String(d.crisis_alert_count  ?? 0)],
        ['Off-topic Fallbacks',          String(d.fallback_count       ?? 0)],
        ['Total Registered Students',    String(d.total_registered_users ?? 0)],
      ],
      headStyles: { fillColor: [14, 96, 8], textColor: 255, fontStyle: 'bold', fontSize: 10 },
      bodyStyles: { fontSize: 9, textColor: [31, 41, 55] },
      alternateRowStyles: { fillColor: [247, 248, 250] },
      margin: { left: margin, right: margin },
      theme: 'grid',
    })
    y = doc.lastAutoTable.finalY + 10
  }

  // ── Emotion Distribution ──
  if (data.trends?.emotion_distribution && Object.keys(data.trends.emotion_distribution).length > 0) {
    if (y > 230) { doc.addPage(); y = 20 }
    doc.setFontSize(13)
    doc.setFont('helvetica', 'bold')
    doc.text('Emotion Distribution', margin, y)
    y += 6

    const total = Object.values(data.trends.emotion_distribution).reduce((a, b) => a + b, 0)
    const rows = Object.entries(data.trends.emotion_distribution).map(([emotion, count]) => [
      emotion.charAt(0).toUpperCase() + emotion.slice(1),
      String(count),
      total > 0 ? `${((count / total) * 100).toFixed(1)}%` : '0%',
    ])

    autoTable(doc, {
      startY: y,
      head: [['Emotion', 'Count', 'Percentage']],
      body: rows,
      headStyles: { fillColor: [14, 96, 8], textColor: 255, fontStyle: 'bold', fontSize: 10 },
      bodyStyles: { fontSize: 9, textColor: [31, 41, 55] },
      alternateRowStyles: { fillColor: [247, 248, 250] },
      margin: { left: margin, right: margin },
      theme: 'grid',
    })
    y = doc.lastAutoTable.finalY + 10
  }

  // ── Sentiment Over Time ──
  if (data.trends?.sentiment_over_time?.length > 0) {
    if (y > 220) { doc.addPage(); y = 20 }
    doc.setFontSize(13)
    doc.setFont('helvetica', 'bold')
    doc.text('Sentiment Over Time (Weekly)', margin, y)
    y += 6

    autoTable(doc, {
      startY: y,
      head: [['Week Starting', 'Positive', 'Neutral', 'Negative']],
      body: data.trends.sentiment_over_time.map((w, i) => [
        w.week_start || `Week ${i + 1}`,
        String(w.positive ?? 0),
        String(w.neutral ?? 0),
        String(w.negative ?? 0),
      ]),
      headStyles: { fillColor: [14, 96, 8], textColor: 255, fontStyle: 'bold', fontSize: 10 },
      bodyStyles: { fontSize: 9, textColor: [31, 41, 55] },
      alternateRowStyles: { fillColor: [247, 248, 250] },
      margin: { left: margin, right: margin },
      theme: 'grid',
    })
    y = doc.lastAutoTable.finalY + 10
  }

  // ── AI Insights ──
  if (data.insights) {
    const ins = data.insights
    if (y > 200) { doc.addPage(); y = 20 }

    doc.setFontSize(13)
    doc.setFont('helvetica', 'bold')
    doc.text('AI-Generated Insights', margin, y)
    y += 4

    if (ins.wellness_summary) {
      doc.setFontSize(9)
      doc.setFont('helvetica', 'italic')
      doc.setTextColor(21, 128, 61)
      const lines = doc.splitTextToSize(`Wellness Summary: ${ins.wellness_summary}`, pageW - margin * 2)
      doc.text(lines, margin, y + 4)
      y += lines.length * 4.5 + 6
      doc.setTextColor(17, 24, 39)
    }

    if (ins.insights?.length > 0) {
      autoTable(doc, {
        startY: y,
        head: [['Category', 'Title', 'Observation', 'Severity']],
        body: ins.insights.map(i => [
          (i.category || 'general').replace(/_/g, ' '),
          i.title || '',
          i.text || '',
          i.severity || 'info',
        ]),
        headStyles: { fillColor: [14, 96, 8], textColor: 255, fontStyle: 'bold', fontSize: 9 },
        bodyStyles: { fontSize: 8, textColor: [31, 41, 55] },
        alternateRowStyles: { fillColor: [247, 248, 250] },
        columnStyles: { 2: { cellWidth: 80 } },
        margin: { left: margin, right: margin },
        theme: 'grid',
      })
      y = doc.lastAutoTable.finalY + 8
    }

    if (ins.recommendations?.length > 0) {
      if (y > 220) { doc.addPage(); y = 20 }
      doc.setFontSize(11)
      doc.setFont('helvetica', 'bold')
      doc.text('Recommendations', margin, y)
      y += 4

      autoTable(doc, {
        startY: y,
        head: [['#', 'Priority', 'Recommendation']],
        body: ins.recommendations.map((r, i) => [
          String(i + 1),
          (r.priority || 'medium').toUpperCase(),
          r.text || '',
        ]),
        headStyles: { fillColor: [14, 96, 8], textColor: 255, fontStyle: 'bold', fontSize: 9 },
        bodyStyles: { fontSize: 8, textColor: [31, 41, 55] },
        alternateRowStyles: { fillColor: [247, 248, 250] },
        columnStyles: { 2: { cellWidth: 130 } },
        margin: { left: margin, right: margin },
        theme: 'grid',
      })
      y = doc.lastAutoTable.finalY + 8
    }

    if (ins.trends?.length > 0) {
      if (y > 220) { doc.addPage(); y = 20 }
      doc.setFontSize(11)
      doc.setFont('helvetica', 'bold')
      doc.text('Observed Trends', margin, y)
      y += 4

      autoTable(doc, {
        startY: y,
        head: [['Metric', 'Direction', 'Description']],
        body: ins.trends.map(t => [t.metric || '', t.direction || '', t.description || '']),
        headStyles: { fillColor: [14, 96, 8], textColor: 255, fontStyle: 'bold', fontSize: 9 },
        bodyStyles: { fontSize: 8, textColor: [31, 41, 55] },
        alternateRowStyles: { fillColor: [247, 248, 250] },
        margin: { left: margin, right: margin },
        theme: 'grid',
      })
      y = doc.lastAutoTable.finalY + 8
    }
  }

  // ── Snapshots ──
  if (data.snapshots?.length > 0) {
    if (y > 200) { doc.addPage(); y = 20 }
    doc.setFontSize(13)
    doc.setFont('helvetica', 'bold')
    doc.text('Historical Daily Snapshots', margin, y)
    y += 6

    autoTable(doc, {
      startY: y,
      head: [['Date', 'Active Users', 'Conversations', 'Messages', 'Avg Session (min)', 'Crisis Alerts']],
      body: data.snapshots.map(s => [
        s.snapshot_date || '',
        String(s.daily_active_users ?? 0),
        String(s.total_conversations ?? 0),
        String(s.total_messages ?? 0),
        String(s.avg_session_minutes ?? 0),
        String(s.crisis_alert_count ?? 0),
      ]),
      headStyles: { fillColor: [14, 96, 8], textColor: 255, fontStyle: 'bold', fontSize: 9 },
      bodyStyles: { fontSize: 8, textColor: [31, 41, 55] },
      alternateRowStyles: { fillColor: [247, 248, 250] },
      margin: { left: margin, right: margin },
      theme: 'grid',
    })
  }

  // ── Footer on all pages ──
  const pageCount = doc.internal.getNumberOfPages()
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i)
    doc.setFontSize(8)
    doc.setTextColor(156, 163, 175)
    doc.text(
      `LeanOn Bot Analytics Report  |  Page ${i} of ${pageCount}  |  Confidential — For authorized personnel only`,
      margin, doc.internal.pageSize.getHeight() - 8
    )
  }

  const filename = `leanon-analytics-${exportOptions.value.period}-${new Date().toISOString().slice(0, 10)}.pdf`
  doc.save(filename)
}

onMounted(() => {
  fetchData()
  fetchInsights()
})
</script>

<style scoped src="@/assets/admin/adminAnalytics.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>

<style scoped>
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
