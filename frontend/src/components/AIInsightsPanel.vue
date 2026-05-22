<template>
  <div class="insights-panel">
    <!-- Panel Header -->
    <div class="insights-header">
      <div class="insights-header-left">
        <div class="insights-icon-wrapper">
          <i class='bx bx-brain'></i>
        </div>
        <div>
          <h2 class="insights-title">AI Insights</h2>
          <p class="insights-subtitle" v-if="generatedAt">
            Last updated: {{ formatDate(generatedAt) }}
          </p>
          <p class="insights-subtitle" v-else>Generating insights...</p>
        </div>
      </div>
      <div class="scheduled-badge">
        <i class='bx bx-calendar-check'></i>
        <span>Daily scheduled</span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="insights-loading">
      <div class="skeleton-group">
        <div class="skeleton skeleton-summary"></div>
        <div class="skeleton skeleton-line"></div>
        <div class="skeleton skeleton-line short"></div>
        <div class="skeleton-cards">
          <div class="skeleton skeleton-card" v-for="i in 3" :key="i"></div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div v-else-if="hasContent" class="insights-content">

      <!-- Stale/Fallback Notice -->
      <div v-if="isStale || isFallback" class="notice-banner">
        <i class='bx bx-info-circle'></i>
        <span v-if="isStale">{{ staleMessage }}</span>
        <span v-else>The analytics system is collecting data. AI insights will improve over time.</span>
      </div>

      <!-- Wellness Summary -->
      <div v-if="wellnessSummary" class="wellness-card">
        <div class="wellness-header">
          <i class='bx bx-heart'></i>
          <h3>Wellness Summary</h3>
        </div>
        <p class="wellness-text">{{ wellnessSummary }}</p>
      </div>

      <!-- Insight Cards -->
      <div v-if="insights.length" class="insights-section">
        <h3 class="section-label">
          <i class='bx bx-bulb'></i> Key Observations
        </h3>
        <div class="insight-cards">
          <div
            v-for="(insight, index) in insights"
            :key="index"
            class="insight-card"
            :class="'severity-' + (insight.severity || 'info')"
          >
            <div class="insight-card-top">
              <span class="insight-badge" :class="'badge-' + (insight.category || 'general')">
                <i :class="getCategoryIcon(insight.category)"></i>
                {{ formatCategory(insight.category) }}
              </span>
              <span v-if="insight.severity === 'critical'" class="severity-dot critical"></span>
              <span v-else-if="insight.severity === 'warning'" class="severity-dot warning"></span>
            </div>
            <h4 class="insight-card-title" v-if="insight.title">{{ insight.title }}</h4>
            <p class="insight-card-text">{{ insight.text }}</p>
          </div>
        </div>
      </div>

      <!-- Trends -->
      <div v-if="trends.length" class="insights-section">
        <h3 class="section-label">
          <i class='bx bx-trending-up'></i> Trends
        </h3>
        <div class="trends-list">
          <div v-for="(trend, index) in trends" :key="index" class="trend-item">
            <div class="trend-direction" :class="'dir-' + trend.direction">
              <i :class="getTrendIcon(trend.direction)"></i>
            </div>
            <div class="trend-info">
              <span class="trend-metric">{{ trend.metric }}</span>
              <p class="trend-desc">{{ trend.description }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recommendations -->
      <div v-if="recommendations.length" class="insights-section">
        <h3 class="section-label">
          <i class='bx bx-check-shield'></i> Recommendations
        </h3>
        <div class="recommendations-list">
          <div
            v-for="(rec, index) in recommendations"
            :key="index"
            class="recommendation-item"
            :class="'priority-' + (rec.priority || 'medium')"
          >
            <span class="rec-number">{{ index + 1 }}</span>
            <div class="rec-content">
              <span class="rec-priority-badge" :class="'priority-' + (rec.priority || 'medium')">
                {{ (rec.priority || 'medium').toUpperCase() }}
              </span>
              <p class="rec-text">{{ rec.text }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Anomalies -->
      <div v-if="anomalies.length" class="insights-section">
        <h3 class="section-label anomaly-label">
          <i class='bx bx-error'></i> Anomalies Detected
        </h3>
        <div class="anomalies-list">
          <div
            v-for="(anomaly, index) in anomalies"
            :key="index"
            class="anomaly-item"
            :class="'anomaly-' + (anomaly.severity || 'info')"
          >
            <i class='bx bx-error-circle'></i>
            <p>{{ anomaly.description }}</p>
          </div>
        </div>
      </div>

    </div>

    <!-- Empty State -->
    <div v-else class="insights-empty">
      <i class='bx bx-bot'></i>
      <h3>No Insights Yet</h3>
      <p>Scheduled AI insights will appear here after the next daily background generation.</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  insights:        { type: Array, default: () => [] },
  recommendations: { type: Array, default: () => [] },
  trends:          { type: Array, default: () => [] },
  wellnessSummary: { type: String, default: '' },
  anomalies:       { type: Array, default: () => [] },
  generatedAt:     { type: String, default: '' },
  loading:         { type: Boolean, default: false },
  isStale:         { type: Boolean, default: false },
  isFallback:      { type: Boolean, default: false },
  staleMessage:    { type: String, default: '' },
})

const hasContent = computed(() => {
  return props.insights.length > 0 ||
    props.recommendations.length > 0 ||
    props.trends.length > 0 ||
    props.wellnessSummary ||
    props.anomalies.length > 0
})

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

const formatCategory = (cat) => {
  if (!cat) return 'General'
  return cat.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

const getCategoryIcon = (category) => {
  const icons = {
    usage: 'bx bx-bar-chart-alt-2',
    emotional: 'bx bx-heart',
    crisis: 'bx bx-shield-alt-2',
    engagement: 'bx bx-user-check',
    academic: 'bx bx-book-open',
    general: 'bx bx-info-circle',
  }
  return icons[category] || icons.general
}

const getTrendIcon = (direction) => {
  return {
    increasing: 'bx bx-up-arrow-alt',
    decreasing: 'bx bx-down-arrow-alt',
    stable: 'bx bx-minus',
  }[direction] || 'bx bx-minus'
}
</script>

<style scoped>
.insights-panel {
  background: var(--card-bg, linear-gradient(145deg, #ffffff, #fcfcfc));
  border-radius: 16px;
  border: 1px solid var(--card-border, #e5e7eb);
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}

/* ── Header ── */
.insights-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, #0E6008 0%, #16a34a 100%);
  color: #fff;
  flex-wrap: wrap;
  gap: 12px;
}

.insights-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.insights-icon-wrapper {
  width: 42px;
  height: 42px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  backdrop-filter: blur(4px);
}

.insights-title {
  font-size: 18px;
  font-weight: 700;
  margin: 0;
}

.insights-subtitle {
  font-size: 12px;
  opacity: 0.8;
  margin: 2px 0 0;
}

.scheduled-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 10px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 600;
  backdrop-filter: blur(4px);
  font-family: 'DM Sans', system-ui, sans-serif;
}

.scheduled-badge i {
  font-size: 16px;
}

/* ── Loading Skeleton ── */
.insights-loading {
  padding: 1.5rem;
}

.skeleton-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.skeleton {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
}

.skeleton-summary { height: 80px; }
.skeleton-line { height: 16px; width: 80%; }
.skeleton-line.short { width: 50%; }
.skeleton-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  margin-top: 8px;
}
.skeleton-card { height: 100px; }

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

/* ── Notice Banner ── */
.notice-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  margin: 1rem 1.5rem 0;
  background: #fefce8;
  border: 1px solid #fef08a;
  border-radius: 10px;
  color: #854d0e;
  font-size: 13px;
}

.notice-banner i {
  font-size: 18px;
  flex-shrink: 0;
}

/* ── Content ── */
.insights-content {
  padding: 0 1.5rem 1.5rem;
}

/* ── Wellness Summary ── */
.wellness-card {
  margin-top: 1.25rem;
  padding: 1.25rem;
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
  border: 1px solid #bbf7d0;
  border-radius: 12px;
}

.wellness-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
  color: #166534;
}

.wellness-header i {
  font-size: 20px;
}

.wellness-header h3 {
  font-size: 15px;
  font-weight: 600;
  margin: 0;
}

.wellness-text {
  font-size: 14px;
  line-height: 1.6;
  color: #15803d;
  margin: 0;
}

/* ── Sections ── */
.insights-section {
  margin-top: 1.5rem;
}

.section-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary, #374151);
  margin: 0 0 12px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.section-label i {
  font-size: 18px;
  color: #0E6008;
}

.anomaly-label i {
  color: #dc2626;
}

/* ── Insight Cards ── */
.insight-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 12px;
}

.insight-card {
  padding: 1rem 1.25rem;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: var(--card-bg-light, #fafafa);
  transition: all 0.25s ease;
}

.insight-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.insight-card.severity-critical {
  border-left: 3px solid #ef4444;
}

.insight-card.severity-warning {
  border-left: 3px solid #f59e0b;
}

.insight-card.severity-info {
  border-left: 3px solid #3b82f6;
}

.insight-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.insight-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.badge-usage      { background: #eff6ff; color: #1d4ed8; }
.badge-emotional  { background: #fdf2f8; color: #be185d; }
.badge-crisis     { background: #fef2f2; color: #b91c1c; }
.badge-engagement { background: #f0fdf4; color: #166534; }
.badge-academic   { background: #fffbeb; color: #854d0e; }
.badge-general    { background: #f3f4f6; color: #4b5563; }

.severity-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.severity-dot.critical {
  background: #ef4444;
  box-shadow: 0 0 6px rgba(239, 68, 68, 0.4);
}

.severity-dot.warning {
  background: #f59e0b;
  box-shadow: 0 0 6px rgba(245, 158, 11, 0.4);
}

.insight-card-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary, #111827);
  margin: 0 0 6px;
}

.insight-card-text {
  font-size: 13px;
  line-height: 1.55;
  color: var(--text-secondary, #6b7280);
  margin: 0;
}

/* ── Trends ── */
.trends-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.trend-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 10px;
  background: var(--card-bg-light, #fafafa);
  border: 1px solid #e5e7eb;
}

.trend-direction {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.dir-increasing {
  background: #fef2f2;
  color: #ef4444;
}

.dir-decreasing {
  background: #f0fdf4;
  color: #10b981;
}

.dir-stable {
  background: #f3f4f6;
  color: #6b7280;
}

.trend-metric {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary, #111827);
}

.trend-desc {
  font-size: 12.5px;
  line-height: 1.5;
  color: var(--text-secondary, #6b7280);
  margin: 4px 0 0;
}

/* ── Recommendations ── */
.recommendations-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.recommendation-item {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 14px 16px;
  border-radius: 10px;
  background: var(--card-bg-light, #fafafa);
  border: 1px solid #e5e7eb;
}

.rec-number {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: #0E6008;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
}

.rec-content {
  flex: 1;
}

.rec-priority-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.05em;
  margin-bottom: 6px;
}

.rec-priority-badge.priority-high   { background: #fef2f2; color: #b91c1c; }
.rec-priority-badge.priority-medium { background: #fffbeb; color: #854d0e; }
.rec-priority-badge.priority-low    { background: #f0fdf4; color: #166534; }

.rec-text {
  font-size: 13px;
  line-height: 1.55;
  color: var(--text-secondary, #4b5563);
  margin: 0;
}

/* ── Anomalies ── */
.anomalies-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.anomaly-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 13px;
  line-height: 1.5;
}

.anomaly-item i {
  font-size: 18px;
  flex-shrink: 0;
  margin-top: 1px;
}

.anomaly-item p {
  margin: 0;
}

.anomaly-critical {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

.anomaly-warning {
  background: #fffbeb;
  border: 1px solid #fef08a;
  color: #854d0e;
}

.anomaly-info {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e40af;
}

/* ── Empty State ── */
.insights-empty {
  padding: 3rem 1.5rem;
  text-align: center;
  color: #9ca3af;
}

.insights-empty i {
  font-size: 3rem;
  opacity: 0.4;
}

.insights-empty h3 {
  font-size: 16px;
  color: var(--text-primary, #6b7280);
  margin: 12px 0 6px;
}

.insights-empty p {
  font-size: 13px;
  max-width: 360px;
  margin: 0 auto;
  line-height: 1.5;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .insights-header {
    padding: 1rem 1.25rem;
  }

  .insights-content {
    padding: 0 1.25rem 1.25rem;
  }

  .insight-cards {
    grid-template-columns: 1fr;
  }

  .scheduled-badge span {
    display: none;
  }
}
</style>
