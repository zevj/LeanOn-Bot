<template>
  <div class="chart-card">
    <div class="chart-card-header">
      <div class="chart-icon-wrapper icon-purple">
        <i class='bx bx-happy-heart-eyes'></i>
      </div>
      <h3 class="chart-card-title">Mood Distribution</h3>
    </div>
    <div class="chart-body">
      <Doughnut v-if="hasData" :data="chartData" :options="chartOptions" />
      <div v-else class="empty-chart-state">
        <i class='bx bx-pie-chart-alt-2'></i>
        <p>No mood data available yet</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
} from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
  data: {
    type: Object,
    default: () => ({})
  }
})

const emotionColors = {
  stressed:    '#f59e0b',
  anxious:     '#8b5cf6',
  sad:         '#3b82f6',
  overwhelmed: '#ef4444',
  lonely:      '#6366f1',
  angry:       '#dc2626',
  positive:    '#10b981',
  hopeful:     '#06b6d4',
}

const hasData = computed(() => {
  return Object.keys(props.data).length > 0 && Object.values(props.data).some(v => v > 0)
})

const chartData = computed(() => {
  const labels = Object.keys(props.data).map(k => k.charAt(0).toUpperCase() + k.slice(1))
  const values = Object.values(props.data)
  const colors = Object.keys(props.data).map(k => emotionColors[k] || '#9ca3af')

  return {
    labels,
    datasets: [{
      data: values,
      backgroundColor: colors,
      borderColor: 'var(--chart-border-color, #ffffff)',
      borderWidth: 2,
      hoverBorderWidth: 3,
      hoverOffset: 8,
    }]
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  cutout: '60%',
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        color: 'var(--text-secondary, #6b7280)',
        padding: 16,
        usePointStyle: true,
        pointStyleWidth: 10,
        font: {
          family: "'DM Sans', system-ui, sans-serif",
          size: 12,
        },
      },
    },
    tooltip: {
      backgroundColor: 'rgba(17, 24, 39, 0.9)',
      titleFont: { family: "'DM Sans', system-ui, sans-serif", weight: '600' },
      bodyFont: { family: "'DM Sans', system-ui, sans-serif" },
      padding: 12,
      cornerRadius: 8,
      callbacks: {
        label: (context) => {
          const total = context.dataset.data.reduce((a, b) => a + b, 0)
          const pct = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0
          return ` ${context.label}: ${context.raw} (${pct}%)`
        }
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

.icon-purple {
  background: #f3e8ff;
  color: #7c3aed;
  border: 1px solid #e9d5ff;
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
