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

                <div class="header-title">
                    <h1 class="title">Dashboard Overview</h1>
                    <p class="subtext">Usage statistics and interaction analytics</p>
                </div>

                <!-- STATS -->
                <div class="whole-stat-card">

                    <div class="high-stat-card">
                        <div class="stat-card-title-high">
                        <h4 class="title-stat">Total interactions</h4>
                        <p class="stat-number">{{ stats.total_interactions.toLocaleString() }}</p>
                        </div>
                        <div class="icon-bg-high">
                        <i class="bx bx-chat"></i>
                        </div>
                    </div>

                    <div class="severe-stat-card">
                        <div class="stat-card-title-severe">
                        <h4 class="title-stat">Active users</h4>
                        <p class="stat-number">{{ stats.active_users.toLocaleString() }}</p>
                        </div>
                        <div class="icon-bg-severe">
                        <i class="bx bx-user"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-title-moderate">
                        <h4 class="title-stat">Avg. session</h4>
                        <p class="stat-number">{{ stats.avg_session_minutes }}m</p>
                        </div>
                        <div class="icon-bg-moderate">
                        <i class="bx bx-time-five"></i>
                        </div>
                    </div>

                    </div>

                <div class="graph-stats">
                    <DailyInteractionsChart :data="stats.daily_interactions" />
                    <MonthlyTrendChart :data="stats.monthly_interactions" />
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

/* ✅ Sidebar state */
const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false');

const stats = ref({
    total_interactions: 0,
    active_users: 0,
    avg_session_minutes: 0,
    daily_interactions: [0, 0, 0, 0, 0, 0, 0],
    monthly_interactions: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
});

const fetchDashboardStats = async () => {
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get('/api/admin/dashboard', {
            headers: { Authorization: `Bearer ${token}` }
        });
        stats.value = res.data;
    } catch (err) {
        console.error('Failed to fetch dashboard stats:', err);
    }
};

onMounted(() => {
    fetchDashboardStats();
});

</script>

<style scoped src="@/assets/admin/adminDashboard.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>