<template>
    <div class="layout">
        <!-- Sidebar -->
        <SidebarAdmin
            :open="sidebarOpen"
            @toggle="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)"
        />

        <main class="main-area">
            <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)" />

            <!-- Attached the ref here so we can target this specific container for the PDF -->
            <div class="main-container" ref="dashboardRef">

                <!-- NEW WRAPPER: Aligns the Title and the Button -->
                <div class="page-header-wrapper">
                    <div class="header-title">
                        <h1 class="title">Dashboard Overview</h1>
                        <p class="subtext">Usage statistics and interaction analytics</p>
                    </div>

                    <!-- PDF Download Button (Hidden in the actual PDF via html2canvas-ignore) -->
                    <button class="download-btn" @click="downloadPDF" data-html2canvas-ignore="true">
                        <i class='bx bx-download'></i>
                        <span>Download PDF</span>
                    </button>
                </div>

                <!-- STATS -->
                <div class="whole-stat-card">

                    <div class="high-stat-card stat-card-animate" style="animation-delay: 0.1s;">
                        <div class="stat-card-title-high">
                            <h4 class="title-stat">Total interactions</h4>
                            <p class="stat-number">{{ stats.total_interactions?.toLocaleString() || 0 }}</p>
                        </div>
                        <div class="icon-bg-high">
                            <i class="bx bx-chat"></i>
                        </div>
                    </div>

                    <div class="severe-stat-card stat-card-animate" style="animation-delay: 0.2s;">
                        <div class="stat-card-title-severe">
                            <h4 class="title-stat">Active users</h4>
                            <p class="stat-number">{{ stats.active_users?.toLocaleString() || 0 }}</p>
                        </div>
                        <div class="icon-bg-severe">
                            <i class="bx bx-user"></i>
                        </div>
                    </div>

                    <div class="stat-card stat-card-animate" style="animation-delay: 0.3s;">
                        <div class="stat-card-title-moderate">
                            <h4 class="title-stat">Avg. session</h4>
                            <p class="stat-number">{{ stats.avg_session_minutes || 0 }}m</p>
                        </div>
                        <div class="icon-bg-moderate">
                            <i class="bx bx-time-five"></i>
                        </div>
                    </div>

                </div>

                <!-- GRAPHS: Wrapped in v-if to prevent rendering blank charts before data arrives -->
                <div class="graph-stats">
                    <template v-if="!loading">
                        <div class="chart-wrapper">
                            <DailyInteractionsChart :data="stats.daily_interactions" />
                        </div>
                        <div class="chart-wrapper">
                            <MonthlyTrendChart :data="stats.monthly_interactions" />
                        </div>
                    </template>
                    <div v-else class="graph-loading-state" data-html2canvas-ignore="true">
                        <div class="spinner"></div>
                        <p>Crunching the latest data...</p>
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { jsPDF } from 'jspdf';             
import html2canvas from 'html2canvas';     
import SidebarAdmin from '@/components/sidebarAdmin.vue';
import HeaderAdmin from '@/components/headerAdmin.vue';
import DailyInteractionsChart from '@/components/DailyInteractionsChart.vue';
import MonthlyTrendChart from '@/components/MonthlyTrendChart.vue';

const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false');
const loading = ref(true); // Start as true to hide charts until fetch completes

// Reference to the dashboard container we want to export
const dashboardRef = ref(null);

// Reactive state for stats
const stats = ref({
    total_interactions: 0,
    active_users: 0,
    avg_session_minutes: 0,
    daily_interactions: [],
    monthly_interactions: []
});

// Fetch backend data
const fetchDashboardStats = async () => {
    loading.value = true;
    try {
        const token = localStorage.getItem('token');
        
        const res = await axios.get('/api/admin/dashboard', {
            headers: { Authorization: `Bearer ${token}` }
        });
        
        stats.value = {
            total_interactions: res.data.total_interactions || 0,
            active_users: res.data.active_users || 0,
            avg_session_minutes: res.data.avg_session_minutes || 0,
            daily_interactions: res.data.daily_interactions || [],
            monthly_interactions: res.data.monthly_interactions || []
        };
    } catch (err) {
        console.error('Failed to fetch dashboard stats:', err);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchDashboardStats();
});

// PDF Generation Logic utilizing jsPDF + html2canvas for capturing graphs
const downloadPDF = async () => {
    const element = dashboardRef.value;
    
    try {
        // 1. Capture the dashboard as a high-res image
        const canvas = await html2canvas(element, { 
            scale: 2, 
            useCORS: true,
            backgroundColor: '#f7f8fa' // Matches the layout background cleanly
        });
        
        const imgData = canvas.toDataURL('image/jpeg', 0.98);
        
        // 2. Create the PDF document (Landscape, Points, A4 format)
        const pdf = new jsPDF('l', 'pt', 'a4');
        
        // 3. Calculate width/height to make sure the dashboard scales correctly to the PDF page
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        
        // 4. Add image to PDF and Download
        pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
        
        const currentDate = new Date().toLocaleDateString('en-PH').replace(/\//g, '-');
        pdf.save(`Dashboard-Overview-${currentDate}.pdf`);
        
    } catch (error) {
        console.error('Error generating Dashboard PDF:', error);
        alert("There was an issue generating the PDF. Please check the console.");
    }
};
</script>

<style scoped src="@/assets/admin/adminDashboard.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>