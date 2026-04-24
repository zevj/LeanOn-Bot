<template>
  <header class="header">

    <!-- Left Logo + Sidebar Toggle -->
    <div class="logo-section">
      <!-- Sidebar Toggle Button -->
      <button class="toggle-btn" @click="handleToggleSidebar">
  ☰
</button>
      <img src="/leanOnBot.png" class="logo-icon" />

      <div class="logo-separation">
        <span class="logo-text">LeanOnBot</span>
        <p class="subtext">Your wellness companion</p>
      </div>
      
    </div>

    <!-- Right Section -->
    <div class="right-section">
      <!-- Notification Component -->
      <NotificationPanel @view-all="handleViewAll" />
    </div>
  </header>

</template>

<script setup>
import { ref } from 'vue'
import Sidebar from './sidebarStudent.vue'
import NotificationPanel from '../components/NotificationPanel.vue'



// Sidebar state lifted to header
const openSidebar = ref(false)

// Toggle functions
function toggleSidebar() {
  openSidebar.value = !openSidebar.value
}
</script>

<style scoped src="../assets/Header & Sidebar/header.css"></style>

