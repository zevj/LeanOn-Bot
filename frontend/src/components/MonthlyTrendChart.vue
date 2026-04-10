<template>
  <div class="chart-container">

    <!-- HEADER -->
    <div class="chart-header">
      <div>
        <h3>Interaction Analytics</h3>
        <p>Monthly engagement overview</p>
      </div>

      <!-- DROPDOWN FILTER -->
      <select v-model="selectedRange" @change="setRange">
        <option v-for="f in filters" :key="f.key" :value="f.key">
          {{ f.label }}
        </option>
      </select>
    </div>

    <!-- CHART -->
    <div class="chart-wrapper">
      <Line :data="chartData" :options="chartOptions" />
    </div>

  </div>
</template>

<script setup>
import { ref } from "vue"
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Filler
} from "chart.js"

import { Line } from "vue-chartjs"

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Filler
)

// COLORS
const COLORS = {
  primary: "#0E6008",
  secondary: "#049516",
  black: "#242424"
}

// MONTHS
const months = [
  "Jan","Feb","Mar",
  "Apr","May","Jun",
  "Jul","Aug","Sep",
  "Oct","Nov","Dec"
]

// DATA
const data = [120,150,180, 200,240,300, 320,280,350, 400,420,500]

// FILTERS
const filters = [
  { key: "all", label: "All Months" },
  { key: "q1", label: "Jan – Mar" },
  { key: "q2", label: "Apr – Jun" },
  { key: "q3", label: "Jul – Sep" },
  { key: "q4", label: "Oct – Dec" }
]

const selectedRange = ref("all")

// RANGE DATA
const ranges = {
  all: { labels: months, data },
  q1: { labels: months.slice(0,3), data: data.slice(0,3) },
  q2: { labels: months.slice(3,6), data: data.slice(3,6) },
  q3: { labels: months.slice(6,9), data: data.slice(6,9) },
  q4: { labels: months.slice(9,12), data: data.slice(9,12) }
}

// CHART DATA
const chartData = ref({
  labels: ranges.all.labels,
  datasets: [
    {
      label: "Interactions",
      data: ranges.all.data,
      borderColor: COLORS.primary,
      backgroundColor: "rgba(14, 96, 8, 0.15)",
      fill: true,
      tension: 0.4,
      pointRadius: 5,
      pointHoverRadius: 7,
      pointBackgroundColor: COLORS.secondary,
      borderWidth: 3
    }
  ]
})

// OPTIONS
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: COLORS.black,
      titleColor: "#fff",
      bodyColor: "#fff",
      padding: 12,
      displayColors: false
    }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: "#555" }
    },
    y: {
      grid: { color: "rgba(0,0,0,0.05)" },
      ticks: { color: "#555" }
    }
  }
}

// UPDATE
function setRange() {
  const r = ranges[selectedRange.value]

  chartData.value = {
    labels: r.labels,
    datasets: [
      {
        label: "Interactions",
        data: r.data,
        borderColor: COLORS.primary,
        backgroundColor: "rgba(14, 96, 8, 0.15)",
        fill: true,
        tension: 0.4,
        pointRadius: 5,
        pointHoverRadius: 7,
        pointBackgroundColor: COLORS.secondary,
        borderWidth: 3
      }
    ]
  }
}
</script>

<style scoped>
.chart-container {
  width: 50%;
  max-width: 950px;
  height: 380px; /* ✅ FIXED HEIGHT */
/*   background: var(--background-color);
 */  padding: 18px;
  border-radius: 18px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
  border: 1px solid rgba(0,0,0,0.06);
  display: flex;
  flex-direction: column;
  border: 2px solid #a8a8a848;
  transition: 0.15s ease-in-out all;
}

.chart-container:hover {
  border: 2px solid var(--primary-color);
}

/* HEADER */
.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.chart-header h3 {
  margin: 0;
  font-size: 18px;
  color: var(--primary-color);
}

.chart-header p {
  margin: 0;
  font-size: 12px;
  color: #555;
}

/* DROPDOWN */
select {
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid #d1d5db;
  background: #fff;
  font-size: 12px;
  cursor: pointer;
  transition: 0.2s ease;
}

select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(14, 96, 8, 0.15);
}

/* CHART WRAPPER (fits remaining space) */
.chart-wrapper {
  flex: 1; /* ✅ makes chart fill remaining height */
}
</style>