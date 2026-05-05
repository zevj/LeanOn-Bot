<template>
  <div class="chart-container">

    <!-- HEADER -->
    <div class="chart-header">
      <div>
        <p class="eyebrow">Weekly Breakdown</p>
        <h3>Trends Over Time</h3>
      </div>
      <div class="legend">
        <span
          v-for="d in chartData.datasets"
          :key="d.label"
          class="legend-item"
          :class="{ dimmed: hiddenSets.has(d.label) }"
          @click="toggleDataset(d.label)"
        >
          <span class="legend-dot" :style="{ background: d.borderColor }"></span>
          {{ d.label }}
        </span>
      </div>
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

    <!-- CHART -->
    <div class="chart-wrapper">
      <Line
        ref="chartRef"
        :key="chartKey"
        :data="visibleChartData"
        :options="chartOptions"
      />
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
  sad:         { border: '#0066FF', fill: 'rgba(0,102,255,1)' },
  anxious:     { border: '#9F7A00', fill: 'rgba(159,122,0,1)' },
  stressed:    { border: '#DC2625', fill: 'rgba(220,38,37,1)' },
  overwhelmed: { border: '#8B5CF6', fill: 'rgba(139,92,246,1)' },
  lonely:      { border: '#EC4899', fill: 'rgba(236,72,153,1)' },
  angry:       { border: '#F97316', fill: 'rgba(249,115,22,1)' },
  hopeful:     { border: '#06B6D4', fill: 'rgba(6,182,212,1)' },
}

const gradientFill = (color) => (context) => {
  const chart = context.chart
  const { ctx, chartArea } = chart
  if (!chartArea) return null
  const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
  gradient.addColorStop(0, color.replace('1)', '0.15)'))
  gradient.addColorStop(1, color.replace('1)', '0)'))
  return gradient
}

const buildDatasets = () => {
  const data = props.weeklyData
  if (!data || Object.keys(data).length === 0) {
    // Fallback: empty datasets
    return []
  }

  return Object.keys(data).map(emotion => {
    const color = emotionColors[emotion] || { border: '#888', fill: 'rgba(136,136,136,1)' }
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
      tension: 0.4,
      borderWidth: 2.5,
      pointRadius: 4,
      pointHoverRadius: 6,
      pointBackgroundColor: '#fff',
      pointBorderColor: d.borderColor,
      pointBorderWidth: 2
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

onMounted(() => { chartKey.value++ })

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 450, easing: 'easeOutQuart' },
  plugins: {
    legend: { display: false },
    tooltip: {
      mode: 'index',
      intersect: false,
      backgroundColor: '#fff',
      borderColor: '#e5e7eb',
      borderWidth: 1,
      titleColor: '#111',
      bodyColor: '#555',
      padding: 12,
      cornerRadius: 10,
      boxPadding: 5,
      titleFont: { size: 11, weight: '600' },
      bodyFont: { size: 12 }
    }
  },
  interaction: { mode: 'index', intersect: false },
  scales: {
    y: {
      beginAtZero: true,
      max: 100,
      grid: { color: 'rgba(0,0,0,0.04)', drawTicks: false },
      border: { display: false },
      ticks: {
        color: '#bbb',
        font: { size: 11 },
        padding: 8,
        maxTicksLimit: 6,
        callback: (v) => v + '%'
      }
    },
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: { color: '#bbb', font: { size: 11 }, padding: 6 }
    }
  }
}
</script>

<style scoped>
.chart-container {
  width: 100%;
  max-width: 790px;
  height: 610px;
  padding: 22px 24px 18px;
  border-radius: 18px;
  background: #fff;
  border: 1.5px solid #ebebeb;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  display: flex;
  flex-direction: column;
  gap: 14px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.chart-container:hover {
  border-color: rgba(14, 96, 8, 0.3);
  box-shadow: 0 4px 24px rgba(0,0,0,0.09);
}

/* HEADER */
.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.eyebrow {
  margin: 0 0 2px;
  font-size: 10.5px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #ccc;
}

.chart-header h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: #111;
  letter-spacing: -0.2px;
}

/* LEGEND */
.legend {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 500;
  color: #555;
  cursor: pointer;
  user-select: none;
  transition: opacity 0.15s ease;
}

.legend-item.dimmed {
  opacity: 0.35;
}

.legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* FILTER ROW */
.filter-row {
  display: flex;
  gap: 6px;
}

.filter-btn {
  padding: 5px 14px;
  border-radius: 8px;
  border: 1.5px solid #e5e7eb;
  background: #fafafa;
  font-size: 11.5px;
  font-weight: 500;
  color: #777;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.filter-btn:hover {
  border-color: #c5d9c4;
  color: #3a6b37;
  background: #f5fbf4;
}

.filter-btn.active {
  background: #0E6008;
  border-color: #0E6008;
  color: #fff;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(14, 96, 8, 0.25);
}

/* CHART */
.chart-wrapper {
  flex: 1;
  min-height: 0;
}
</style>