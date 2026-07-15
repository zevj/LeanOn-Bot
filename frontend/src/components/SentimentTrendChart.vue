<template>
  <div class="chart-card">
    <div class="chart-card-header">
      <div class="chart-icon-wrapper icon-blue">
        <i class='bx bx-trending-up'></i>
      </div>
      <h3 class="chart-card-title">Sentiment Trends</h3>
    </div>
    <div class="chart-body">
      <Line v-if="hasData" :data="chartData" :options="chartOptions" />
      <div v-else class="empty-chart-state">
        <i class='bx bx-line-chart'></i>
        <p>No sentiment data available yet</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import { useTheme } from '@/composables/useTheme.js'
import { getChartTheme } from '@/utils/chartTheme.js'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Filler,
  Tooltip,
  Legend,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip, Legend)

const props = defineProps({
  data: {
    type: Array,
    default: () => []
  }
})

const hasData = computed(() => props.data.length > 0)
const { isDark } = useTheme()
const themeColors = computed(() => getChartTheme(isDark.value))

const chartData = computed(() => {
  const labels = props.data.map((d, i) => `Week ${i + 1}`)

  return {
    labels,
    datasets: [
      {
        label: 'Positive',
        data: props.data.map(d => d.positive),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.08)',
        fill: true,
        tension: 0.4,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: '#10b981',
        pointBorderColor: themeColors.value.pointBorder,
        pointBorderWidth: 2,
        borderWidth: 2.5,
      },
      {
        label: 'Neutral',
        data: props.data.map(d => d.neutral),
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245, 158, 11, 0.06)',
        fill: true,
        tension: 0.4,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: '#f59e0b',
        pointBorderColor: themeColors.value.pointBorder,
        pointBorderWidth: 2,
        borderWidth: 2.5,
      },
      {
        label: 'Negative',
        data: props.data.map(d => d.negative),
        borderColor: '#ef4444',
        backgroundColor: 'rgba(239, 68, 68, 0.06)',
        fill: true,
        tension: 0.4,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: '#ef4444',
        pointBorderColor: themeColors.value.pointBorder,
        pointBorderWidth: 2,
        borderWidth: 2.5,
      },
    ]
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: {
      position: 'top',
      align: 'end',
      labels: {
        color: themeColors.value.tick,
        usePointStyle: true,
        pointStyleWidth: 10,
        padding: 16,
        font: {
          family: "'DM Sans', system-ui, sans-serif",
          size: 12,
        },
      },
    },
    tooltip: {
      backgroundColor: themeColors.value.tooltipBg,
      borderColor: themeColors.value.tooltipBorder,
      borderWidth: 1,
      titleColor: themeColors.value.tooltipTitle,
      bodyColor: themeColors.value.tooltipBody,
      titleFont: { family: "'DM Sans', system-ui, sans-serif", weight: '600' },
      bodyFont: { family: "'DM Sans', system-ui, sans-serif" },
      padding: 12,
      cornerRadius: 8,
    }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: {
        color: themeColors.value.tick,
        font: { family: "'DM Sans', system-ui, sans-serif", size: 11 },
      }
    },
    y: {
      beginAtZero: true,
      grid: { color: themeColors.value.grid },
      ticks: {
        color: themeColors.value.tick,
        font: { family: "'DM Sans', system-ui, sans-serif", size: 11 },
        precision: 0,
      }
    }
  }
}))
</script>

<style scoped>
.chart-card {
  background: var(--card-bg, linear-gradient(145deg, #ffffff, #fcfcfc));
  border-radius: 16px;
  border: 1px solid var(--card-border, #e5e7eb);
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
  transition: all 0.3s ease;
  width: 100%;
  box-sizing: border-box;
}

.chart-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
  transform: translateY(-2px);
}

.chart-card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 1.25rem;
}

.chart-icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.icon-blue {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
}

.chart-card-title {
  font-size: 15px;
  font-weight: 600;
  color: black;
  margin: 0;
}

.chart-body {
  height: 260px;
  position: relative;
  width: 100%;
}

.empty-chart-state {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #9ca3af;
}

.empty-chart-state i {
  font-size: 2.5rem;
  opacity: 0.5;
}

.empty-chart-state p {
  font-size: 13px;
  margin: 0;
}

/* ── Responsive ── */
@media (max-width: 1024px) {
  .chart-body {
    height: 240px;
  }
}

@media (max-width: 768px) {
  .chart-card {
    padding: 1.25rem;
    border-radius: 14px;
  }

  .chart-body {
    height: 220px;
  }

  .chart-card-title {
    font-size: 14px;
  }

  .chart-icon-wrapper {
    width: 36px;
    height: 36px;
    font-size: 1.1rem;
  }
}

@media (max-width: 480px) {
  .chart-card {
    padding: 1rem;
    border-radius: 12px;
  }

  .chart-body {
    height: 200px;
  }

  .chart-card-header {
    gap: 10px;
    margin-bottom: 1rem;
  }

  .chart-card-title {
    font-size: 13.5px;
  }

  .chart-icon-wrapper {
    width: 32px;
    height: 32px;
    font-size: 1rem;
    border-radius: 8px;
  }
}

@media (max-width: 360px) {
  .chart-body {
    height: 180px;
  }

  .chart-card-title {
    font-size: 13px;
  }
}
</style>
