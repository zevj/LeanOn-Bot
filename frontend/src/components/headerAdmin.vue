<template>
  <header class="header">
    <div class="logo-section">
      <img src="/leanOnBot.png" class="logo-icon" />
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
function handleViewAll() {}
</script>

<style scoped src="../assets/Header & Sidebar/headerAdmin.css"></style>