<template>
    <div class="layout">
        <!-- Sidebar -->
        <SidebarAdmin
            :open="sidebarOpen"
@toggle="sidebarOpen = !sidebarOpen"
        />

        <main class="main-area">
            <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen" />

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
                        <p class="stat-number">1,284</p>
                        </div>
                        <div class="icon-bg-high">
                        <i class="bx bx-chat"></i>
                        </div>
                    </div>

                    <div class="severe-stat-card">
                        <div class="stat-card-title-severe">
                        <h4 class="title-stat">Active users</h4>
                        <p class="stat-number">348</p>
                        </div>
                        <div class="icon-bg-severe">
                        <i class="bx bx-user"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-title-moderate">
                        <h4 class="title-stat">Avg. session</h4>
                        <p class="stat-number">4.2m</p>
                        </div>
                        <div class="icon-bg-moderate">
                        <i class="bx bx-time-five"></i>
                        </div>
                    </div>

                    </div>

                <div class="graph-stats">
                    <DailyInteractionsChart />
                    <MonthlyTrendChart />
                </div>

            </div>
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import SidebarAdmin from '@/components/sidebarAdmin.vue';
import HeaderAdmin from '@/components/headerAdmin.vue';
import DailyInteractionsChart from '@/components/DailyInteractionsChart.vue';
import MonthlyTrendChart from '@/components/MonthlyTrendChart.vue';



/* ✅ Sidebar state (same pattern as ChatConvo.vue) */
const sidebarOpen = ref(false);

</script>

<style scoped src="@/assets/admin/adminDashboard.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>