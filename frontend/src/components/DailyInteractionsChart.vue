<template>
  <div class="chart-container">

    <!-- HEADER (LEFT ALIGNED) -->
    <div class="chart-header">
      <div>
        <h3>Daily Interactions</h3>
        <p>Overview of user activity from Monday to Sunday</p>
      </div>
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

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
)

// DATA
const chartData = {
  labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
  datasets: [
    {
      label: "Interactions",
      data: [12, 19, 8, 15, 22, 30, 18],
      backgroundColor: "#0E6008",
      hoverBackgroundColor: "#049516",
      borderRadius: 5,
      barThickness: 45
    }
  ]
}

// OPTIONS
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,

  plugins: {
    legend: { display: false },

    tooltip: {
      backgroundColor: "#242424",
      titleColor: "#fff",
      bodyColor: "#d1fae5",
      padding: 10
    }
  },

  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: "rgba(14, 96, 8, 0.08)"
      },
      ticks: {
        color: "#242424"
      }
    },
    x: {
      grid: { display: false },
      ticks: {
        color: "#242424"
      }
    }
  }
}
</script>

<style scoped>
.chart-container {
  width: 48%;
  height: 380px;

/*   background: #faf9f6;
 */  padding: 18px;
  border-radius: 16px;

  box-shadow: 0 8px 22px rgba(14, 96, 8, 0.12);
  border: 1px solid rgba(14, 96, 8, 0.15);

  display: flex;
  flex-direction: column;
  border: 2px solid #a8a8a848;
  transition: 0.15s ease-in-out all;
}

.chart-container:hover {
  border: 2px solid var(--primary-color);
}

/* HEADER LEFT ALIGN */
.chart-header {
  display: flex;
  justify-content: flex-start;
  margin-bottom: 10px;
}

.chart-header h3 {
  margin: 0;
  font-size: 18px;
  color: #0E6008;
  font-weight: 700;
}

.chart-header p {
  margin: 2px 0 0;
  font-size: 12px;
  color: #555;
}

/* CHART AREA */
.chart-wrapper {
  flex: 1;
}
</style>