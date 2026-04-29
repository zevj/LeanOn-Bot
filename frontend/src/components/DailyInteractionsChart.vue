<template>
  <div class="chart-container">

    <!-- HEADER -->
    <div class="chart-header">
      <div>
        <h3>Daily Interactions</h3>
        <p>Overview of user activity from Monday to Sunday</p>
      </div>
      <span class="total-badge">124 total</span>
    </div>

    <!-- CHART -->
    <div class="chart-wrapper">
      <Bar :data="chartData" :options="chartOptions" />
    </div>

  </div>
</template>

<script setup>
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from "chart.js"

import { Bar } from "vue-chartjs"

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const chartData = {
  labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
  datasets: [
    {
      label: "Interactions",
      data: [12, 19, 8, 15, 22, 30, 18],
      backgroundColor: "rgba(14, 96, 8, 0.82)",
      hoverBackgroundColor: "#16a34a",
      borderRadius: 6,
      borderSkipped: false,
      barThickness: 40
    }
  ]
}

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  animation: {
    duration: 600,
    easing: "easeOutQuart"
  },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: "#1a1a1a",
      titleColor: "#fff",
      bodyColor: "#a3e6a0",
      padding: 10,
      cornerRadius: 8,
      displayColors: false,
      callbacks: {
        label: (item) => `${item.raw} interactions`
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: "rgba(0,0,0,0.05)" },
      border: { display: false },
      ticks: {
        color: "#999",
        font: { size: 11 },
        padding: 6
      }
    },
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: {
        color: "#666",
        font: { size: 12 },
        padding: 4
      }
    }
  }
}
</script>

<style scoped>
.chart-container {
  width: 48%;
  height: 380px;
  padding: 20px 22px 16px;
  border-radius: 16px;
  background: #fff;
  border: 1.5px solid #e5e7eb;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: column;
  gap: 16px;
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.chart-container:hover {
  border-color: rgba(14, 96, 8, 0.35);
  box-shadow: 0 4px 20px rgba(14, 96, 8, 0.1);
}

/* HEADER */
.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.chart-header h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: #111;
  letter-spacing: -0.2px;
}

.chart-header p {
  margin: 3px 0 0;
  font-size: 12px;
  color: #888;
}

.total-badge {
  font-size: 12px;
  font-weight: 500;
  color: #0E6008;
  background: #f0fdf4;
  border: 1px solid rgba(14, 96, 8, 0.18);
  border-radius: 20px;
  padding: 3px 10px;
  white-space: nowrap;
  margin-top: 2px;
}

/* CHART */
.chart-wrapper {
  flex: 1;
  min-height: 0;
}
</style>