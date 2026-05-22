<template>
  <div class="chart-card">
    <div class="chart-card-header">
      <div class="chart-icon-wrapper icon-amber">
        <i class='bx bx-time-five'></i>
      </div>
      <h3 class="chart-card-title">Peak Usage Hours</h3>
    </div>
    <div class="chart-body">
      <Bar v-if="hasData" :data="chartData" :options="chartOptions" />
      <div v-else class="empty-chart-state">
        <i class='bx bx-bar-chart-alt-2'></i>
        <p>No usage data available yet</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Tooltip,
  Legend,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend)

const props = defineProps({
  data: {
    type: Array,
    default: () => []
  }
})

const hasData = computed(() => props.data.length > 0)

const formatHour = (h) => {
  if (h === 0) return '12 AM'
  if (h === 12) return '12 PM'
  return h < 12 ? `${h} AM` : `${h - 12} PM`
}

const chartData = computed(() => {
  // Sort by hour and fill all 24 hours
  const hourMap = {}
  props.data.forEach(item => {
    hourMap[item.hour] = item.count
  })

  const labels = []
  const values = []
  const colors = []

  // Find peak hour
  const peakHour = props.data.length > 0
    ? props.data.reduce((a, b) => a.count > b.count ? a : b).hour
    : -1

  for (let h = 0; h < 24; h++) {
    labels.push(formatHour(h))
    const val = hourMap[h] || 0
    values.push(val)
    colors.push(h === peakHour ? '#0E6008' : 'rgba(14, 96, 8, 0.3)')
  }

  return {
    labels,
    datasets: [{
      label: 'Messages',
      data: values,
      backgroundColor: colors,
      borderRadius: 4,
      borderSkipped: false,
      barPercentage: 0.7,
    }]
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(17, 24, 39, 0.9)',
      titleFont: { family: "'DM Sans', system-ui, sans-serif", weight: '600' },
      bodyFont: { family: "'DM Sans', system-ui, sans-serif" },
      padding: 12,
      cornerRadius: 8,
      callbacks: {
        title: (items) => items[0]?.label || '',
        label: (ctx) => ` ${ctx.raw} messages`
      }
    }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: {
        color: '#9ca3af',
        font: { family: "'DM Sans', system-ui, sans-serif", size: 10 },
        maxRotation: 45,
        callback: function(val, idx) {
          // Show every 3rd label to avoid crowding
          return idx % 3 === 0 ? this.getLabelForValue(val) : ''
        }
      }
    },
    y: {
      beginAtZero: true,
      grid: { color: 'rgba(0, 0, 0, 0.04)' },
      ticks: {
        color: '#9ca3af',
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
}

.icon-amber {
  background: #fffbeb;
  color: #d97706;
  border: 1px solid #fef08a;
}

.chart-card-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary, #111827);
  margin: 0;
}

.chart-body {
  height: 260px;
  position: relative;
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
</style>
