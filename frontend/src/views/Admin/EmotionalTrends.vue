<template>
  <div class="layout">
    <SidebarAdmin />

    <main>
      <HeaderAdmin />

      <div class="main-container">
        <div class="header-title">
          <h1 class="title">Emotional Trends</h1>
          <p class="subtext">Anonymized emotional patterns</p>
        </div>

        <div class="charts-container">
          <div class="charts-separation">
            <TrendsChart />
          </div>

          <div class="charts-flex">
            <div class="referrals-card">
              <h2 class="referrals-card__title">Referrals</h2>
              <div class="referrals-card__divider" />
              <ul class="referrals-card__list">
                <li v-for="stat in stats" :key="stat.label" class="referrals-card__row">
                  <span class="referrals-card__label">{{ stat.label }}</span>
                  <span class="referrals-card__value" :class="`referrals-card__value--${stat.modifier}`">
                    {{ stat.value }}
                  </span>
                </li>
              </ul>
            </div>

            <div class="card">
              <h3 class="emotion-title">Top Emotions</h3>

              <div
                v-for="(item, index) in emotions"
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
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import SidebarAdmin from '@/components/sidebarAdmin.vue';
import HeaderAdmin from '@/components/headerAdmin.vue';
import TrendsChart from '@/components/TrendsChart.vue';

/* TRENDS PROGRESS*/
const emotions = [
  { name: 'Stressed', value: 35 },
  { name: 'Anxious', value: 25 },
  { name: 'Sad', value: 15 },
  { name: 'Overwhelmed', value: 12 },
  { name: 'Lonely', value: 8 },
  { name: 'Positive', value: 5 }
]

/* REFERRALS */
const stats = [
  { label: 'Total',    value: 47, modifier: 'total'    },
  { label: 'Accepted', value: 32, modifier: 'accepted' },
  { label: 'Pending',  value: 15, modifier: 'pending'  },
]
</script>


<style scoped src="@/assets/admin/emotionalTrends.css"></style>