<template>
    <div class="layout">
        <!-- Sidebar -->
        <SidebarAdmin
            :open="sidebarOpen"
            @toggle="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)"
        />

        <main class="main-area">
            <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)" />

            <div class="main-container">

                <div class="page-header-wrapper">
                    <div class="header-title">
                        <h1 class="title">Dashboard Overview</h1>
                        <p class="subtext">Usage statistics and interaction analytics</p>
                    </div>
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
import SidebarAdmin from '@/components/sidebarAdmin.vue';
import HeaderAdmin from '@/components/headerAdmin.vue';
import DailyInteractionsChart from '@/components/DailyInteractionsChart.vue';
import MonthlyTrendChart from '@/components/MonthlyTrendChart.vue';

const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false');
const loading = ref(true); // Start as true to hide charts until fetch completes

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
</script>

<style scoped src="@/assets/admin/adminDashboard.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>