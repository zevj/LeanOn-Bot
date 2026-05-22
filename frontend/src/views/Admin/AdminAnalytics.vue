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
        <!-- Page Header -->
        <div class="page-header-wrapper">
          <div class="header-title">
            <h1 class="title">AI Analytics & Insights</h1>
            <p class="subtext">Anonymized wellness statistics, patterns, and AI-generated school-wide insights.</p>
          </div>

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
        </div>

        <!-- Global Page Loading State -->
        <div v-if="loadingDashboard" class="loading-overlay">
          <div class="spinner"></div>
          <p>Analyzing and aggregating data snapshots...</p>
        </div>

        <div v-else>
          <!-- Stat Cards Grid -->
          <div class="stat-cards-grid">
            <!-- DAU -->
            <div class="stat-card blue">
              <div class="stat-card-content">
                <h4 class="stat-label">Daily Active Users</h4>
                <p class="stat-value">{{ stats.daily_active_users || 0 }}</p>
                <span class="stat-growth" :class="getGrowthClass(stats.dau_growth)">
                  <i :class="getGrowthIcon(stats.dau_growth)"></i>
                  {{ Math.abs(stats.dau_growth) }}%
                </span>
              </div>
              <div class="stat-icon-wrapper icon-blue">
                <i class="bx bx-user"></i>
              </div>
            </div>

            <!-- Conversations -->
            <div class="stat-card green">
              <div class="stat-card-content">
                <h4 class="stat-label">Total Conversations</h4>
                <p class="stat-value">{{ stats.total_conversations || 0 }}</p>
                <span class="stat-growth" :class="getGrowthClass(stats.conversation_growth)">
                  <i :class="getGrowthIcon(stats.conversation_growth)"></i>
                  {{ Math.abs(stats.conversation_growth) }}%
                </span>
              </div>
              <div class="stat-icon-wrapper icon-green">
                <i class="bx bx-chat"></i>
              </div>
            </div>

            <!-- Messages -->
            <div class="stat-card purple">
              <div class="stat-card-content">
                <h4 class="stat-label">Total Messages</h4>
                <p class="stat-value">{{ stats.total_messages || 0 }}</p>
                <span class="stat-growth" :class="getGrowthClass(stats.message_growth)">
                  <i :class="getGrowthIcon(stats.message_growth)"></i>
                  {{ Math.abs(stats.message_growth) }}%
                </span>
              </div>
              <div class="stat-icon-wrapper icon-purple">
                <i class="bx bx-message-detail"></i>
              </div>
            </div>

            <!-- Avg Session -->
            <div class="stat-card amber">
              <div class="stat-card-content">
                <h4 class="stat-label">Avg. Session</h4>
                <p class="stat-value unit-suffix">
                  {{ stats.avg_session_minutes || 0 }}<span style="font-size: 14px; font-weight: 500;">m</span>
                </p>
                <span style="font-size: 11px; color: #6b7280; margin-top: 2px;">Closed sessions only</span>
              </div>
              <div class="stat-icon-wrapper icon-amber">
                <i class="bx bx-time-five"></i>
              </div>
            </div>

            <!-- Peak hour -->
            <div class="stat-card cyan">
              <div class="stat-card-content">
                <h4 class="stat-label">Peak Usage Hour</h4>
                <p class="stat-value unit-suffix">{{ formatPeakHour(stats.peak_hour) }}</p>
                <span style="font-size: 11px; color: #6b7280; margin-top: 2px;">Highest interaction volume</span>
              </div>
              <div class="stat-icon-wrapper icon-cyan">
                <i class="bx bx-bell"></i>
              </div>
            </div>

            <!-- Crisis Alerts -->
            <div class="stat-card red">
              <div class="stat-card-content">
                <h4 class="stat-label">Crisis Alerts</h4>
                <p class="stat-value" :style="{ color: stats.crisis_alert_count > 0 ? '#b91c1c' : '#111827' }">
                  {{ stats.crisis_alert_count || 0 }}
                </p>
                <span style="font-size: 11px; color: #6b7280; margin-top: 2px;">Requires attention</span>
              </div>
              <div class="stat-icon-wrapper icon-red">
                <i class="bx bx-shield"></i>
              </div>
            </div>
          </div>

          <!-- Charts Row 1: Mood Distribution & Peak Hours -->
          <div class="charts-section">
            <MoodDistributionChart :data="trendData.emotion_distribution || {}" />
            <PeakUsageChart :data="trendData.peak_usage_hours || []" />
          </div>

          <!-- Charts Row 2: Sentiment Trends -->
          <div class="charts-section-bottom">
            <SentimentTrendChart :data="trendData.sentiment_over_time || []" />

            <!-- Fallback & Engagement card -->
            <div class="stat-card" style="height: auto; flex-direction: column; align-items: flex-start; justify-content: flex-start; padding: 1.5rem; gap: 1.25rem; border-left: 4px solid #16a34a; background: var(--card-bg, #fff);">
              <div style="display: flex; align-items: center; gap: 10px; width: 100%;">
                <div class="stat-icon-wrapper icon-green" style="width: 36px; height: 36px; font-size: 1.1rem;">
                  <i class="bx bx-info-circle"></i>
                </div>
                <h3 style="font-size: 15px; font-weight: 600; color: #111827; margin: 0;">System Performance & Privacy</h3>
              </div>
              
              <div style="display: flex; flex-direction: column; gap: 12px; width: 100%; font-size: 13.5px; color: #4b5563;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">
                  <span>Off-topic Fallbacks:</span>
                  <strong style="color: #111827;">{{ stats.fallback_count || 0 }} times</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">
                  <span>Active Student Engagement Rate:</span>
                  <strong style="color: #111827;">
                    {{ stats.total_registered_users > 0 ? ((stats.daily_active_users / stats.total_registered_users) * 100).toFixed(1) : 0 }}%
                  </strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">
                  <span>Total Registered Students:</span>
                  <strong style="color: #111827;">{{ stats.total_registered_users || 0 }}</strong>
                </div>
                <div style="margin-top: 8px; font-size: 12px; line-height: 1.5; color: #6b7280; background: #f9fafb; padding: 10px; border-radius: 8px; border: 1px solid #f3f4f6;">
                  <i class="bx bx-lock-alt" style="margin-right: 4px; color: #16a34a;"></i>
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
            />
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
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

const selectedPeriod = ref('7d')
const loadingDashboard = ref(true)
const loadingInsights = ref(false)
const fetchingDashboard = ref(false)
const fetchingInsights = ref(false)

const stats = ref({
  daily_active_users: 0,
  dau_growth: 0,
  total_conversations: 0,
  conversation_growth: 0,
  total_messages: 0,
  message_growth: 0,
  avg_session_minutes: 0,
  peak_hour: null,
  peak_usage_hours: [],
  crisis_alert_count: 0,
  crisis_by_severity: {},
  fallback_count: 0,
  total_registered_users: 0,
})

const trendData = ref({
  emotion_distribution: {},
  sentiment_over_time: [],
  peak_usage_hours: [],
  weekly_comparison: [],
})

const insightsData = ref({
  insights: [],
  recommendations: [],
  trends: [],
  wellness_summary: '',
  anomalies: [],
  generated_at: '',
  is_stale: false,
  is_fallback: false,
  stale_message: '',
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

const getGrowthClass = (val) => {
  if (!val || val === 0) return 'neutral'
  return val > 0 ? 'positive' : 'negative'
}

const getGrowthIcon = (val) => {
  if (!val || val === 0) return 'bx bx-minus'
  return val > 0 ? 'bx bx-trending-up' : 'bx bx-trending-down'
}

const fetchData = async () => {
  if (fetchingDashboard.value) return
  fetchingDashboard.value = true
  loadingDashboard.value = true
  try {
    const token = localStorage.getItem('token')
    const config = { headers: { Authorization: `Bearer ${token}` } }

    // Convert tab periods to backend period query params
    const backendPeriod = selectedPeriod.value

    const [dashboardRes, trendsRes] = await Promise.all([
      axios.get(`/api/admin/analytics/dashboard?period=${backendPeriod}`, config),
      axios.get(`/api/admin/analytics/trends?period=${backendPeriod === '1d' ? '7d' : backendPeriod}`, config) // Trends need multiple days to plot line charts nicely
    ])

    stats.value = dashboardRes.data
    trendData.value = trendsRes.data
  } catch (error) {
    console.error('Error fetching analytics dashboard stats:', error)
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
    const token = localStorage.getItem('token')
    const config = { headers: { Authorization: `Bearer ${token}` } }

    const res = await axios.get('/api/admin/analytics/insights?period=weekly', config)
    insightsData.value = res.data
  } catch (error) {
    console.error('Error fetching AI insights:', error)
  } finally {
    loadingInsights.value = false
    fetchingInsights.value = false
  }
}

onMounted(() => {
  fetchData()
  fetchInsights()
})
</script>

<style scoped src="@/assets/admin/adminAnalytics.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>
