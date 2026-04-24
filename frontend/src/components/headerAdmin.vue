<template>
  <header class="header">
    <div class="logo-section">
      <img src="/leanOnBot.png" class="logo-icon" />

      <!-- 🔥 ADD TOGGLE BUTTON -->
      <button class="toggle-btn" @click="emit('toggle-sidebar')">
        ☰
      </button>

      <div class="logo-separation">
        <span class="logo-text">LeanOnBot</span>
        <p class="subtext">Admin Panel</p>
      </div>
    </div>

    <div class="right-section">
      <NotificationPanel @view-all="handleViewAll" />
    </div>
  </header>
</template>

<script setup>
import NotificationPanel from '../components/NotificationPanel.vue'

const emit = defineEmits(['toggle-sidebar'])

function handleViewAll() {}
</script>

<style scoped src="../assets/Header & Sidebar/headerAdmin.css"></style>