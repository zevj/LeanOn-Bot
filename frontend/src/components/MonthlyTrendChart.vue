<template>
  <div class="chart-container">

    <!-- HEADER -->
    <div class="chart-header">
      <div class="header-left">
        <p class="label">Interaction Analytics</p>
        <h3>{{ activeFilter.label }}</h3>
      </div>
      <div class="header-right">
        <span class="trend up" v-if="trendPercent >= 0">↑ {{ trendPercent }}%</span>
        <span class="trend down" v-else>↓ {{ Math.abs(trendPercent) }}%</span>
        <span class="vs-label">vs last period</span>
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
      <Line :data="chartData" :options="chartOptions" />
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

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, Filler)

const props = defineProps({
  data: {
    type: Array,
    default: () => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
  }
})

const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"]

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
        gradient.addColorStop(0, "rgba(14,96,8,0.12)")
        gradient.addColorStop(1, "rgba(14,96,8,0.01)")
        return gradient
      },
      fill: true,
      tension: 0.45,
      pointRadius: 4,
      pointHoverRadius: 7,
      pointBackgroundColor: "#fff",
      pointBorderColor: "#0E6008",
      pointBorderWidth: 2.5,
      borderWidth: 2.5
    }]
  }
}

const chartData = ref(buildDataset({ labels: months, data: props.data }))

watch(() => props.data, () => {
  chartData.value = buildDataset(rangesData.value[selectedRange.value])
}, { deep: true })

function selectRange(f) {
  selectedRange.value = f.key
  chartData.value = buildDataset(rangesData.value[f.key])
}

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 400, easing: "easeOutQuart" },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: "#0f1a0e",
      titleColor: "#d1fae5",
      bodyColor: "#fff",
      padding: 12,
      cornerRadius: 10,
      displayColors: false,
      titleFont: { size: 11, weight: "500" },
      bodyFont: { size: 13, weight: "600" },
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
        color: "#aaa",
        font: { size: 11, weight: "500" },
        padding: 6
      }
    },
    y: {
      grid: { color: "rgba(0,0,0,0.04)", drawTicks: false },
      border: { display: false, dash: [4, 4] },
      ticks: {
        color: "#aaa",
        font: { size: 11 },
        padding: 10,
        maxTicksLimit: 5
      }
    }
  }
}
</script>

<style scoped>
.chart-container {
  width: 50%;
  max-width: 950px;
  height: 380px;
  padding: 20px 22px 16px;
  border-radius: 18px;
  background: #fff;
  border: 1.5px solid #ebebeb;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px rgba(0,0,0,0.06);
  display: flex;
  flex-direction: column;
  gap: 14px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.chart-container:hover {
  border-color: rgba(14, 96, 8, 0.3);
  box-shadow: 0 2px 6px rgba(0,0,0,0.04), 0 12px 32px rgba(14,96,8,0.09);
}

/* HEADER */
.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.label {
  margin: 0 0 2px;
  font-size: 13px;
  font-weight: 600;
  color: black;
}

.chart-header h3 {
  margin: 0;
  font-size: 12px;
  font-weight: 500;
  color: #7a7a7a;
  letter-spacing: -0.3px;
  transition: color 0.2s;
}

.header-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.trend {
  font-size: 13px;
  font-weight: 700;
  letter-spacing: -0.2px;
}

.trend.up   { color: #16a34a; }
.trend.down { color: #dc2626; }

.vs-label {
  font-size: 10.5px;
  color: #bbb;
  font-weight: 500;
}

/* FILTER ROW */
.filter-row {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 5px 13px;
  border-radius: 8px;
  border: 1.5px solid #e5e7eb;
  background: #fafafa;
  font-size: 11.5px;
  font-weight: 500;
  color: #777;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
  line-height: 1.4;
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