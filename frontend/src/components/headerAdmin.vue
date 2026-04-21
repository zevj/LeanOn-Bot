<template>
  <header class="header">
    <div class="logo-section">
      <i class='bx bx-menu menu-toggle' @click="toggleSidebar"></i>
      <img src="/leanOnBot.png" class="logo-icon" />
      <div class="logo-separation">
        <span class="logo-text">LeanOnBot</span>
        <p class="subtext">Admin Panel</p>
      </div>
    </div>

    <div class="right-section">
      <!-- Notification Component -->
      <NotificationPanel @view-all="handleViewAll" />
    </div>
  </header>

  <Sidebar :open="openSidebar" @toggle="toggleSidebar" />
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from '../components/sidebarAdmin.vue'
import NotificationPanel from '../components/NotificationPanel.vue'

const openSidebar = ref(false)

function toggleSidebar() {
  openSidebar.value = !openSidebar.value
}

function handleViewAll() {
  // Navigate to full notifications page
  // e.g. router.push('/notifications')
}
</script>

<style scoped src="../assets/Header & Sidebar/headerAdmin.css"></style>