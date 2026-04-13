<template>
    <div class="layout">
        <sidebarAdmin/>

        <main>
            <headerAdmin/>

            <div class="main-container">

                <div class="header-title">
                    <h1 class="title">Dashboard Overview</h1>
                    <p class="subtext">Usage statistics and interaction analytics</p>
                </div>
                <!-- STATS -->
                <div class="whole-stat-card">
                    <div class="high-stat-card">
                        <div class="stat-card-title-high">
                            <h4 class="title-stat">Total Interactions</h4>
                            <p class="stat-number">12</p>
                        </div>
                        <div class="icon-bg-high"><i class="bx bx-chat"></i></div>
                    </div>

                    <div class="severe-stat-card">
                        <div class="stat-card-title-severe">
                            <h4 class="title-stat">Active Users</h4>
                            <p class="stat-number">12</p>
                        </div>
                        <div class="icon-bg-severe"><i class="bx bx-user"></i></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-title-moderate">
                            <h4 class="title-stat">Average Session</h4>
                            <p class="stat-number">12</p>
                        </div>
                        <div class="icon-bg-moderate"><i class="bx bx-time-five"></i></div>
                    </div>

                    <!-- <div class="low-stat-card">
                        <div class="stat-card-title-low">
                            <h4 class="title-stat">Low</h4>
                            <p class="stat-number">12</p>
                        </div>
                        <div class="icon-bg-low"><i class="bx bx-check-shield"></i></div>
                    </div> -->
                </div>

                <div class="graph-stats">
                    <DailyInteractionsChart />
                    <MonthlyTrendChart/>
                </div>

            </div>
        </main>

    </div>
</template>

<script setup>
import sidebarAdmin from '@/components/sidebarAdmin.vue';
import headerAdmin from '@/components/headerAdmin.vue';
import DailyInteractionsChart from '@/components/DailyInteractionsChart.vue';
import MonthlyTrendChart from '@/components/MonthlyTrendChart.vue';
</script>

<style scoped src="@/assets/admin/adminDashboard.css"></style>