<template>
  <div class="chart-container fade-in">

    <!-- HEADER -->
    <div class="chart-header">
      <div class="header-text">
        <p class="eyebrow">Weekly Breakdown</p>
        <h3>Trends Over Time</h3>
      </div>
      
      <!-- FILTER ROW -->
      <div class="filter-row">
        <button
          v-for="f in filters"
          :key="f.key"
          :class="['filter-btn', { active: selectedFilter === f.key }]"
          @click="selectFilter(f.key)"
        >
          {{ f.label }}
        </button>
      </div>
    </div>

    <template v-if="!hasNoData">
      <!-- CUSTOM LEGEND (Interactive Pills) -->
      <div class="legend-container">
        <button
          v-for="d in chartData.datasets"
          :key="d.label"
          class="legend-pill"
          :class="{ dimmed: hiddenSets.has(d.label) }"
          @click="toggleDataset(d.label)"
        >
          <span class="legend-dot" :style="{ background: d.borderColor, boxShadow: `0 0 6px ${d.borderColor}` }"></span>
          {{ d.label }}
        </button>
      </div>

      <!-- CHART -->
      <div class="chart-wrapper">
        <Line
          ref="chartRef"
          :key="chartKey"
          :data="visibleChartData"
          :options="chartOptions"
        />
      </div>
    </template>

    <!-- EMPTY STATE -->
    <div v-else class="empty-state-chart">
      <div class="empty-icon-wrap-chart">
        <i class='bx bx-line-chart'></i>
      </div>
      <p class="empty-title-chart">No trend data yet</p>
      <p class="empty-subtitle-chart">Weekly emotion trends will appear here once data is available.</p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import {
  Chart as ChartJS,
  Title, Tooltip, Legend,
  LineElement, CategoryScale,
  LinearScale, PointElement, Filler
} from 'chart.js'
import { Line } from 'vue-chartjs'
import { useTheme } from '@/composables/useTheme.js'
import { getChartTheme } from '@/utils/chartTheme.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler)

const props = defineProps({
  weeklyData: {
    type: Object,
    default: () => ({})
  },
  weekLabels: {
    type: Array,
    default: () => ['W1', 'W2', 'W3', 'W4', 'W5', 'W6']
  }
})

const chartRef = ref(null)
const chartKey = ref(0)
const { isDark } = useTheme()
const themeColors = computed(() => getChartTheme(isDark.value))

const filters = [
  { key: 'all', label: 'All Weeks' },
  { key: 'w1_3', label: 'W1 – W3' },
  { key: 'w4_6', label: 'W4 – W6' }
]

const selectedFilter = ref('all')
const hiddenSets = ref(new Set())

const ranges = {
  all:  { start: 0, end: 6 },
  w1_3: { start: 0, end: 3 },
  w4_6: { start: 3, end: 6 }
}

const emotionColors = {
  positive:    { border: '#0A9569', fill: 'rgba(10,149,105,1)' },
  sad:         { border: '#3b82f6', fill: 'rgba(59,130,246,1)' },
  anxious:     { border: '#eab308', fill: 'rgba(234,179,8,1)' },
  stressed:    { border: '#ef4444', fill: 'rgba(239,68,68,1)' },
  overwhelmed: { border: '#8b5cf6', fill: 'rgba(139,92,246,1)' },
  lonely:      { border: '#ec4899', fill: 'rgba(236,72,153,1)' },
  angry:       { border: '#f97316', fill: 'rgba(249,115,22,1)' },
  hopeful:     { border: '#06b6d4', fill: 'rgba(6,182,212,1)' },
}

// --- Empty state: no emotion keys present in the incoming data ---
const hasNoData = computed(() => !props.weeklyData || Object.keys(props.weeklyData).length === 0)

const gradientFill = (color) => (context) => {
  const chart = context.chart
  const { ctx, chartArea } = chart
  if (!chartArea) return null
  const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
  gradient.addColorStop(0, color.replace('1)', '0.2)'))
  gradient.addColorStop(1, color.replace('1)', '0.0)'))
  return gradient
}

const buildDatasets = () => {
  const data = props.weeklyData
  if (!data || Object.keys(data).length === 0) {
    return []
  }

  return Object.keys(data).map(emotion => {
    const color = emotionColors[emotion] || { border: '#9ca3af', fill: 'rgba(156,163,175,1)' }
    return {
      label: emotion.charAt(0).toUpperCase() + emotion.slice(1),
      data: data[emotion],
      borderColor: color.border,
      fillColor: color.fill,
    }
  })
}

const chartData = computed(() => {
  const { start, end } = ranges[selectedFilter.value]
  const datasets = buildDatasets()
  return {
    labels: props.weekLabels.slice(start, end),
    datasets: datasets.map(d => ({
      label: d.label,
      data: (d.data || []).slice(start, end),
      borderColor: d.borderColor,
      backgroundColor: gradientFill(d.fillColor),
      fill: true,
      tension: 0.45, /* Smoother curves */
      borderWidth: 3, /* Bolder lines */
      pointRadius: 4,
      pointHoverRadius: 7,
      pointBackgroundColor: '#ffffff',
      pointBorderColor: d.borderColor,
      pointBorderWidth: 2.5
    }))
  }
})

const visibleChartData = computed(() => ({
  labels: chartData.value.labels,
  datasets: chartData.value.datasets.filter(d => !hiddenSets.value.has(d.label))
}))

function selectFilter(key) {
  selectedFilter.value = key
  chartKey.value++
}

function toggleDataset(label) {
  const next = new Set(hiddenSets.value)
  next.has(label) ? next.delete(label) : next.add(label)
  hiddenSets.value = next
}

watch(() => props.weeklyData, () => {
  chartKey.value++
}, { deep: true })

watch(isDark, () => {
  chartKey.value++
})

onMounted(() => { chartKey.value++ })

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 600, easing: 'easeOutQuart' },
  plugins: {
    legend: { display: false }, /* Disabled native legend to use custom pills */
    tooltip: {
      mode: 'index',
      intersect: false,
      backgroundColor: themeColors.value.tooltipBg, /* Dark glassmorphism tooltip */
      borderColor: themeColors.value.tooltipBorder,
      borderWidth: 1,
      titleColor: themeColors.value.tooltipTitle,
      bodyColor: themeColors.value.tooltipBody,
      padding: 14,
      cornerRadius: 12,
      boxPadding: 6,
      usePointStyle: true,
      titleFont: { size: 13, weight: 'bold', family: "'DM Sans', sans-serif" },
      bodyFont: { size: 12, family: "'DM Sans', sans-serif" }
    }
  },
  interaction: { mode: 'index', intersect: false },
  scales: {
    y: {
      beginAtZero: true,
      max: 100,
      grid: { 
        color: themeColors.value.grid,
        drawBorder: false,
        borderDash: [5, 5] /* Clean dashed grid lines */
      },
      border: { display: false },
      ticks: {
        color: themeColors.value.tick,
        font: { size: 11, family: "'DM Sans', sans-serif" },
        padding: 10,
        maxTicksLimit: 6,
        callback: (v) => v + '%'
      }
    },
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: { 
        color: themeColors.value.tickMuted,
        font: { size: 12, family: "'DM Sans', sans-serif" }, 
        padding: 8 
      }
    }
  }
}))
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap');

/* ── Container ── */
.chart-container {
  width: 100%;
  height: 600px; /* Fluid height, scales down in media queries */
  padding: 1.75rem 2rem;
  border-radius: 16px;
  background: linear-gradient(145deg, #ffffff, #fafafa);
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  display: flex;
  flex-direction: column;
  gap: 16px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  font-family: 'DM Sans', system-ui, sans-serif;
}

.chart-container:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.08);
}

.fade-in {
  animation: fadeSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) backwards;
}

@keyframes fadeSlideUp {
  0% { opacity: 0; transform: translateY(20px); }
  100% { opacity: 1; transform: translateY(0); }
}

/* ── Header ── */
.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
  flex-wrap: wrap;
}

.header-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.eyebrow {
  margin: 0;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #9ca3af;
}

.chart-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  letter-spacing: -0.2px;
}

/* ── Filter Row ── */
.filter-row {
  display: flex;
  gap: 8px;
  background: #f3f4f6;
  padding: 4px;
  border-radius: 10px;
}

.filter-btn {
  padding: 6px 16px;
  border-radius: 8px;
  border: none;
  background: transparent;
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: 'DM Sans', sans-serif;
}

.filter-btn:hover {
  color: #374151;
}

.filter-btn.active {
  background: #ffffff;
  color: #0E6008;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* ── Legend (Interactive Pills) ── */
.legend-container {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  padding-bottom: 8px;
}

.legend-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #4b5563;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  padding: 6px 14px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: 'DM Sans', sans-serif;
}

.legend-pill:hover {
  background: #f9fafb;
  border-color: #d1d5db;
  transform: translateY(-1px);
}

.legend-pill.dimmed {
  opacity: 0.4;
  filter: grayscale(80%);
  background: #f3f4f6;
  box-shadow: none;
  transform: translateY(0);
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
  transition: all 0.2s;
}

/* ── Chart Wrapper ── */
.chart-wrapper {
  flex: 1;
  min-height: 250px; /* Ensures chart doesn't collapse to 0 on tiny screens */
  width: 100%;
}

/* ── Empty State ── */
.empty-state-chart {
  flex: 1;
  min-height: 250px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 14px;
  padding: 24px;
}

.empty-icon-wrap-chart {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #0E6008;
  font-size: 26px;
  flex-shrink: 0;
}

.empty-title-chart {
  font-size: 14px;
  font-weight: 700;
  color: #374151;
  margin: 0;
  font-family: 'DM Sans', sans-serif;
}

.empty-subtitle-chart {
  font-size: 12.5px;
  font-weight: 500;
  color: #9ca3af;
  margin: 0;
  max-width: 260px;
  line-height: 1.5;
}

/* ── 📱 RESPONSIVE BREAKPOINTS ── */

@media (max-width: 1024px) {
  .chart-container {
    height: 500px;
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .chart-container {
    height: auto;
    min-height: 450px;
    padding: 1.25rem;
  }

  .chart-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .filter-row {
    width: 100%;
    justify-content: space-between;
  }
  
  .filter-btn {
    flex: 1;
  }

  .legend-container {
    padding-bottom: 12px;
  }
}

@media (max-width: 480px) {
  .chart-container {
    min-height: 400px;
    padding: 1rem;
    gap: 12px;
  }

  .chart-header h3 {
    font-size: 16px;
  }

  .filter-btn {
    padding: 6px 10px;
    font-size: 11px;
  }

  .legend-pill {
    padding: 4px 10px;
    font-size: 11px;
  }

  .legend-dot {
    width: 8px;
    height: 8px;
  }

  .empty-icon-wrap-chart {
    width: 50px;
    height: 50px;
    font-size: 22px;
    border-radius: 14px;
  }

  .empty-title-chart { font-size: 13px; }
  .empty-subtitle-chart { font-size: 11.5px; }
}
</style>
