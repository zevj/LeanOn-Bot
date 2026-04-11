<template>
  <div class="chart-container">
    <Line 
      ref="chartRef"
      :key="chartKey"
      :data="chartData" 
      :options="chartOptions" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Filler
} from 'chart.js'
import { Line } from 'vue-chartjs'

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Filler
)

// ✅ refs
const chartRef = ref(null)
const chartKey = ref(0)

// ✅ SAFE gradient
const gradientFill = (color) => (context) => {
  const chart = context.chart
  const { ctx, chartArea } = chart

  if (!chartArea) return null

  const gradient = ctx.createLinearGradient(
    0,
    chartArea.top,
    0,
    chartArea.bottom
  )

  gradient.addColorStop(0, color.replace('1)', '0.25)'))
  gradient.addColorStop(1, color.replace('1)', '0)'))

  return gradient
}

const chartData = {
  labels: ['W1', 'W2', 'W3', 'W4', 'W5', 'W6'],
  datasets: [
    {
      label: 'positive',
      data: [15, 16, 24, 30, 38, 44],
      borderColor: '#0A9569',
      backgroundColor: gradientFill('rgba(10,149,105,1)'),
      fill: true,
      tension: 0.4,
      borderWidth: 3
    },
    {
      label: 'sad',
      data: [30, 34, 40, 45, 50, 54],
      borderColor: '#0066FF',
      backgroundColor: gradientFill('rgba(0,102,255,1)'),
      fill: true,
      tension: 0.4,
      borderWidth: 3
    },
    {
      label: 'anxious',
      data: [60, 62, 65, 68, 70, 72],
      borderColor: '#9F7A00',
      backgroundColor: gradientFill('rgba(159,122,0,1)'),
      fill: true,
      tension: 0.4,
      borderWidth: 3
    },
    {
      label: 'stressed',
      data: [100, 100, 100, 100, 100, 100],
      borderColor: '#DC2625',
      backgroundColor: gradientFill('rgba(220,38,37,1)'),
      fill: true,
      tension: 0.4,
      borderWidth: 3
    }
  ]
}

// ✅ force re-render after mount (fix blank issue)
onMounted(() => {
  chartKey.value++
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,

  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        usePointStyle: true,
        padding: 16
      }
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      backgroundColor: '#ffffff',
      borderColor: '#e5e7eb',
      borderWidth: 1,
      titleColor: '#111827',
      bodyColor: '#374151',
      padding: 10,
      cornerRadius: 8
    },
    title: {
      display: true,
      text: 'Trends Over Time'
    }
  },

  interaction: {
    mode: 'index',
    intersect: false
  },

  scales: {
    y: {
      beginAtZero: true,
      max: 100,
      grid: {
        color: 'rgba(0,0,0,0.05)'
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  }
}
</script>

<style scoped>
.chart-container {
  width: 100%;        /* ✅ added width */
  max-width: 790px;   /* optional */
  height: 610px;  
  background: #ffffff;
  padding: 20px;
  border-radius: 16px;
  border: 2px solid #a8a8a848;
  box-shadow: 0 4px 20px rgba(0,0,0,0.2);
  transition: 0.15s ease-in-out all;
}

.chart-container:hover {
    border: 2px solid var(--primary-color);
}
</style>