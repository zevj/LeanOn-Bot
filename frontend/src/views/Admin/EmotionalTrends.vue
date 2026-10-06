<template>
  <div class="layout">
    <SidebarAdmin
      :open="sidebarOpen"
      @toggle="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)"
    />

    <main class="main-area">
      <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)" />

      <div class="main-container">
        <div class="header-title fade-in">
          <h1 class="title">Emotional Trends</h1>
          <p class="subtext">Analyze anonymized emotional patterns and referral statistics over time.</p>
        </div>

        <div class="charts-container">
          <div class="charts-separation animate-card stagger-1">
            <TrendsChart :weeklyData="weeklyData" :weekLabels="weekLabels" />
          </div>

          <div class="charts-flex">
            
            <div class="referrals-card animate-card stagger-2">
              <div class="card-header">
                <div class="icon-wrapper icon-green"><i class='bx bx-user-plus'></i></div>
                <h2 class="card-title">Referrals</h2>
              </div>
              <div class="referrals-card__divider" />

              <ul v-if="referralStats.length" class="referrals-card__list">
                <li v-for="stat in referralStats" :key="stat.label" class="referrals-card__row">
                  <span class="referrals-card__label">{{ stat.label }}</span>
                  <span class="referrals-badge" :class="`badge--${stat.modifier}`">
                    {{ stat.value }}
                  </span>
                </li>
              </ul>

              <div v-else class="empty-state-mini">
                <div class="empty-icon-wrap-mini icon-green">
                  <i class='bx bx-user-plus'></i>
                </div>
                <p class="empty-title-mini">No referrals yet</p>
                <p class="empty-subtitle-mini">Referral activity will show up here once available.</p>
              </div>
            </div>

            <div class="top-emotions-card animate-card stagger-3">
              <div class="card-header">
                <div class="icon-wrapper icon-blue"><i class='bx bx-heart'></i></div>
                <h3 class="card-title">Top Emotions</h3>
              </div>
              
              <div v-if="topEmotions.length" class="emotions-list">
                <div
                  v-for="(item, index) in topEmotions"
                  :key="index"
                  class="emotion-item"
                >
                  <div class="label-row">
                    <span class="name">{{ item.name }}</span>
                    <span class="percent">{{ item.value }}%</span>
                  </div>
                  <div class="progress-bar">
                    <div
                      class="progress-fill"
                      :style="{ width: item.value + '%' }"
                    ></div>
                  </div>
                </div>
              </div>

              <div v-else class="empty-state-mini">
                <div class="empty-icon-wrap-mini icon-blue">
                  <i class='bx bx-heart'></i>
                </div>
                <p class="empty-title-mini">No emotion data yet</p>
                <p class="empty-subtitle-mini">Emotional trend data will appear here once recorded.</p>
              </div>
            </div>

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
import TrendsChart from '@/components/TrendsChart.vue';

const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false');

const topEmotions = ref([]);
const referralStats = ref([]);
const weeklyData = ref({});
const weekLabels = ref(['W1', 'W2', 'W3', 'W4', 'W5', 'W6']);

const fetchEmotionalTrends = async () => {
    try {
        const token = localStorage.getItem('token');
        const res = await axios.get('/api/admin/emotional-trends', {
            headers: { Authorization: `Bearer ${token}` },
        });

        topEmotions.value = res.data.top_emotions;
        referralStats.value = res.data.referral_stats;
        weeklyData.value = res.data.weekly_data;
        weekLabels.value = res.data.week_labels;
    } catch (err) {
        console.error('Failed to fetch emotional trends:', err);
    }
};

onMounted(() => {
    fetchEmotionalTrends();
});
</script>

<style scoped src="@/assets/admin/emotionalTrends.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>