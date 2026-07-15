<template>
  <div class="chart-container">

    <!-- HEADER -->
    <div class="chart-header">
      <div class="header-left">
        <p class="label">Interaction Analytics</p>
        <h3>{{ activeFilter.label }} Overview</h3>
      </div>
      <div class="header-right">
        <div :class="['trend-badge', trendPercent >= 0 ? 'up' : 'down']">
          <i :class="trendPercent >= 0 ? 'bx bx-trending-up' : 'bx bx-trending-down'"></i>
          <span>{{ Math.abs(trendPercent) }}%</span>
        </div>
        <span class="vs-label">vs previous period</span>
      </div>
    </div>

    <!-- FILTER ROW -->
    <div class="filter-row">
      <button
        v-for="f in filters"
        :key="f.key"
        :class="['filter-btn', { active: selectedRange === f.key }]"
        @click="selectRange(f)"
      >
        {{ f.label }}
      </button>
    </div>

    <!-- CHART -->
    <div class="chart-wrapper">
      <Line v-if="!hasNoData" :data="chartData" :options="chartOptions" />

      <div v-else class="empty-state-line">
        <div class="empty-icon-wrap-line">
          <i class='bx bx-line-chart'></i>
        </div>
        <p class="empty-title-line">No interaction data yet</p>
        <p class="empty-subtitle-line">This trend will populate once activity is recorded.</p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue"
import {
  Chart as ChartJS,
  Title, Tooltip, Legend,
  LineElement, PointElement,
  CategoryScale, LinearScale, Filler
} from "chart.js"
import { Line } from "vue-chartjs"
import { useTheme } from '@/composables/useTheme.js'
import { getChartTheme } from '@/utils/chartTheme.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, Filler)

const props = defineProps({
  data: {
    type: Array,
    default: () => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
  }
})

const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"]
const { isDark } = useTheme()
const themeColors = computed(() => getChartTheme(isDark.value))

const filters = [
  { key: "all", label: "All Months" },
  { key: "q1",  label: "Jan – Mar"  },
  { key: "q2",  label: "Apr – Jun"  },
  { key: "q3",  label: "Jul – Sep"  },
  { key: "q4",  label: "Oct – Dec"  }
]

const selectedRange = ref("all")
const activeFilter = computed(() => filters.find(f => f.key === selectedRange.value))

const rangesData = computed(() => ({
  all: { labels: months,              data: props.data },
  q1:  { labels: months.slice(0, 3),  data: props.data.slice(0, 3)  },
  q2:  { labels: months.slice(3, 6),  data: props.data.slice(3, 6)  },
  q3:  { labels: months.slice(6, 9),  data: props.data.slice(6, 9)  },
  q4:  { labels: months.slice(9, 12), data: props.data.slice(9, 12) }
}))

// --- Empty state: no data array, or every month is zero ---
const hasNoData = computed(() => !props.data.length || props.data.every(v => Number(v) === 0))

// Simple trend: compare last 6 months average to first 6 months
const trendPercent = computed(() => {
  const d = props.data
  const firstHalf = d.slice(0, 6).reduce((a, b) => a + b, 0)
  const secondHalf = d.slice(6, 12).reduce((a, b) => a + b, 0)
  if (firstHalf === 0) return 0
  return ((secondHalf - firstHalf) / firstHalf * 100).toFixed(1)
})

function buildDataset(r) {
  return {
    labels: r.labels,
    datasets: [{
      label: "Interactions",
      data: r.data,
      borderColor: "#0E6008",
      backgroundColor: (ctx) => {
        const chart = ctx.chart
        const { ctx: c, chartArea } = chart
        if (!chartArea) return "rgba(14,96,8,0.06)"
        const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
        gradient.addColorStop(0, "rgba(14,96,8,0.25)") // Slightly stronger green at the top
        gradient.addColorStop(1, "rgba(14,96,8,0.00)") // Fades out completely at the bottom
        return gradient
      },
      fill: true,
      tension: 0.45,
      pointRadius: 4,
      pointHoverRadius: 7,
      pointBackgroundColor: themeColors.value.pointBorder,
      pointBorderColor: "#0E6008",
      pointBorderWidth: 2.5,
      borderWidth: 3
    }]
  }
}

const chartData = ref(buildDataset({ labels: months, data: props.data }))

watch(() => props.data, () => {
  chartData.value = buildDataset(rangesData.value[selectedRange.value])
}, { deep: true })

watch(isDark, () => {
  chartData.value = buildDataset(rangesData.value[selectedRange.value])
})

function selectRange(f) {
  selectedRange.value = f.key
  chartData.value = buildDataset(rangesData.value[f.key])
}

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 800, easing: "easeOutExpo" },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: themeColors.value.tooltipBg,
      titleColor: themeColors.value.tooltipTitle,
      titleFont: { size: 13, weight: 'bold', family: "'DM Sans', sans-serif" },
      bodyColor: themeColors.value.tooltipBody,
      bodyFont: { size: 12, family: "'DM Sans', sans-serif" },
      padding: 12,
      cornerRadius: 8,
      displayColors: false,
      borderColor: themeColors.value.tooltipBorder,
      borderWidth: 1,
      callbacks: {
        title: (items) => items[0].label,
        label: (item) => `${item.raw} interactions`
      }
    }
  },
  scales: {
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: {
        color: themeColors.value.tickMuted,
        font: { size: 12, family: "'DM Sans', sans-serif" },
        padding: 6
      }
    },
    y: {
      beginAtZero: true,
      grid: { 
        color: themeColors.value.grid,
        drawBorder: false,
        borderDash: [5, 5]
      },
      border: { display: false },
      ticks: {
        color: themeColors.value.tick,
        font: { size: 11, family: "'DM Sans', sans-serif" },
        padding: 10,
        maxTicksLimit: 6
      }
    }
  }
}))
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&display=swap');

.chart-container {
  width: 100%; /* Replaced fixed 50% width to be naturally responsive */
  height: 380px;
  padding: 24px 24px 20px;
  border-radius: 16px;
  background: linear-gradient(145deg, #ffffff, #fafafa);
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02), 0 1px 3px rgba(0, 0, 0, 0.01);
  display: flex;
  flex-direction: column;
  gap: 16px;
  transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
  font-family: 'DM Sans', system-ui, sans-serif;
  
  /* Entrance Animation */
  animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) backwards;
  animation-delay: 0.1s; /* Slight delay to cascade with the daily chart */
}

@keyframes fadeUp {
  0% { opacity: 0; transform: translateY(15px); }
  100% { opacity: 1; transform: translateY(0); }
}

.chart-container:hover {
  border-color: rgba(14, 96, 8, 0.3);
  box-shadow: 0 10px 25px rgba(14, 96, 8, 0.06), 0 4px 10px rgba(0, 0, 0, 0.02);
  transform: translateY(-2px);
}

/* HEADER */
.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.label {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  letter-spacing: -0.2px;
}

.chart-header h3 {
  margin: 0;
  font-size: 13px;
  font-weight: 500;
  color: #6b7280;
}

.header-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.trend-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: -0.2px;
}

.trend-badge i {
  font-size: 15px;
}

.trend-badge.up { 
  color: #15803d; 
  background-color: #dcfce7;
  border: 1px solid #bbf7d0;
}

.trend-badge.down { 
  color: #b91c1c; 
  background-color: #fee2e2;
  border: 1px solid #fecaca;
}

.vs-label {
  font-size: 11px;
  color: #9ca3af;
  font-weight: 500;
}

/* FILTER ROW */
.filter-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 6px 14px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: #ffffff;
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
  font-family: 'DM Sans', system-ui, sans-serif;
}

.filter-btn:hover {
  border-color: #bbf7d0;
  color: #15803d;
  background: #f0fdf4;
}

.filter-btn.active {
  background: #0E6008;
  border-color: #0E6008;
  color: #ffffff;
  font-weight: 600;
  box-shadow: 0 4px 6px rgba(14, 96, 8, 0.2);
  transform: translateY(-1px);
}

/* CHART */
.chart-wrapper {
  flex: 1;
  min-height: 0;
  width: 100%;
}

/* EMPTY STATE */
.empty-state-line {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  text-align: center;
  padding: 12px;
}

.empty-icon-wrap-line {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0fdf4;
  border: 1px solid rgba(14, 96, 8, 0.15);
  color: #0E6008;
  font-size: 22px;
  flex-shrink: 0;
}

.empty-title-line {
  font-size: 13.5px;
  font-weight: 600;
  color: #374151;
  margin: 0;
}

.empty-subtitle-line {
  font-size: 12px;
  font-weight: 500;
  color: #9ca3af;
  margin: 0;
  max-width: 220px;
  line-height: 1.5;
}

/* ── RESPONSIVE BREAKPOINTS ── */

@media (max-width: 1024px) {
  .chart-container {
    height: 350px;
    padding: 20px;
  }
}

@media (max-width: 768px) {
  .chart-container {
    height: 350px;
    padding: 16px;
    border-radius: 14px;
  }
  
  .label {
    font-size: 15px;
  }
  
  .filter-btn {
    padding: 5px 12px;
    font-size: 11.5px;
  }
}

@media (max-width: 480px) {
  .chart-container {
    height: auto; /* Let the height adapt to stacked elements */
    min-height: 320px;
    padding: 14px;
  }
  
  .chart-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .header-right {
    align-items: flex-start;
    flex-direction: row;
    align-items: center;
    gap: 8px;
  }

  .filter-row {
    gap: 6px;
    padding-bottom: 10px;
  }

  .chart-wrapper {
    height: 200px; /* Give the canvas a strict height on small phones */
  }

  .empty-icon-wrap-line {
    width: 44px;
    height: 44px;
    font-size: 19px;
    border-radius: 12px;
  }

  .empty-title-line { font-size: 13px; }
  .empty-subtitle-line { font-size: 11.5px; }
}
</style>
