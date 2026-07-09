<template>
  <div class="chart-container">
    <!-- HEADER -->
    <div class="chart-header">
      <div class="header-text">
        <h3>Daily Interactions</h3>
        <p>Overview of user activity from Monday to Sunday</p>
      </div>
      <span class="total-badge">
        <i class='bx bx-trending-up'></i> {{ total }} total
      </span>
    </div>

    <!-- CHART -->
    <div class="chart-wrapper">
      <Bar v-if="!hasNoData" :data="chartData" :options="chartOptions" />

      <div v-else class="empty-state-bar">
        <div class="empty-icon-wrap-bar">
          <i class='bx bx-bar-chart-alt-2'></i>
        </div>
        <p class="empty-title-bar">No interactions yet</p>
        <p class="empty-subtitle-bar">Daily activity will appear here once users start interacting.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  Filler
} from "chart.js"

import { Bar } from "vue-chartjs"

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, Filler)

const props = defineProps({
  data: {
    type: Array,
    default: () => [0, 0, 0, 0, 0, 0, 0]
  }
})

const total = computed(() => props.data.reduce((a, b) => a + b, 0))

// --- Empty state: no data array, or every day is zero ---
const hasNoData = computed(() => !props.data.length || props.data.every(v => Number(v) === 0))

const chartData = computed(() => ({
  labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
  datasets: [
    {
      label: "Interactions",
      data: props.data,
      // Create a smooth dynamic gradient for the bars
      backgroundColor: (context) => {
        const chart = context.chart;
        const { ctx, chartArea } = chart;
        if (!chartArea) {
          return "rgba(14, 96, 8, 0.82)"; // Fallback
        }
        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        gradient.addColorStop(0, 'rgba(14, 96, 8, 0.15)'); // Light transparent green at bottom
        gradient.addColorStop(1, 'rgba(14, 96, 8, 0.95)'); // Solid brand green at top
        return gradient;
      },
      hoverBackgroundColor: "#16a34a",
      borderColor: "rgba(14, 96, 8, 1)",
      borderWidth: 1,
      borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
      borderSkipped: false,
      // Responsive bar sizing instead of fixed width
      maxBarThickness: 45,
      barPercentage: 0.6,
      categoryPercentage: 0.8
    }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  animation: {
    duration: 800,
    easing: "easeOutExpo"
  },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: "rgba(17, 24, 39, 0.95)",
      titleColor: "#f3f4f6",
      titleFont: { size: 13, weight: 'bold', family: "'DM Sans', sans-serif" },
      bodyColor: "#a3e6a0",
      bodyFont: { size: 12, family: "'DM Sans', sans-serif" },
      padding: 12,
      cornerRadius: 8,
      displayColors: false,
      borderColor: "rgba(255,255,255,0.1)",
      borderWidth: 1,
      callbacks: {
        label: (item) => `${item.raw} interactions`
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { 
        color: "rgba(0,0,0,0.04)",
        drawBorder: false,
        borderDash: [5, 5] // Dashed grid lines for a cleaner look
      },
      border: { display: false },
      ticks: {
        color: "#9ca3af",
        font: { size: 11, family: "'DM Sans', sans-serif" },
        padding: 8
      }
    },
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: {
        color: "#6b7280",
        font: { size: 12, family: "'DM Sans', sans-serif" },
        padding: 6
      }
    }
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&display=swap');

.chart-container {
  width: 100%; /* Replaced fixed 48% to allow parent grid/flex to control width */
  height: 380px;
  padding: 24px 24px 20px;
  border-radius: 16px;
  background: linear-gradient(145deg, #ffffff, #fafafa);
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02), 0 1px 3px rgba(0, 0, 0, 0.01);
  display: flex;
  flex-direction: column;
  gap: 18px;
  transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
  font-family: 'DM Sans', system-ui, sans-serif;
  
  /* Entrance Animation */
  animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) backwards;
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
  gap: 12px;
}

.header-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.chart-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  letter-spacing: -0.2px;
}

.chart-header p {
  margin: 0;
  font-size: 12.5px;
  color: #6b7280;
}

.total-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 600;
  color: #0E6008;
  background: #f0fdf4;
  border: 1px solid rgba(14, 96, 8, 0.15);
  border-radius: 20px;
  padding: 5px 12px;
  white-space: nowrap;
  box-shadow: 0 1px 2px rgba(14, 96, 8, 0.05);
}

.total-badge i {
  font-size: 14px;
}

/* CHART */
.chart-wrapper {
  flex: 1;
  min-height: 0;
  width: 100%;
}

/* EMPTY STATE */
.empty-state-bar {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  text-align: center;
  padding: 12px;
}

.empty-icon-wrap-bar {
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

.empty-title-bar {
  font-size: 13.5px;
  font-weight: 600;
  color: #374151;
  margin: 0;
}

.empty-subtitle-bar {
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
    height: 320px;
    padding: 16px;
    border-radius: 14px;
  }
  
  .chart-header h3 {
    font-size: 15px;
  }
  
  .chart-header p {
    font-size: 11.5px;
  }
}

@media (max-width: 480px) {
  .chart-container {
    height: 280px;
    padding: 14px;
  }
  
  .chart-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .total-badge {
    align-self: flex-start;
  }

  .empty-icon-wrap-bar {
    width: 44px;
    height: 44px;
    font-size: 19px;
    border-radius: 12px;
  }

  .empty-title-bar { font-size: 13px; }
  .empty-subtitle-bar { font-size: 11.5px; }
}
</style>