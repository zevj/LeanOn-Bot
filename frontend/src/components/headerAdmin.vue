<template>
  <header class="header">

    <!-- Left: Hamburger (mobile only) + Logo -->
    <div class="logo-section">

      <!-- Mobile hamburger toggle -->
      <button class="hamburger-btn" @click="triggerMobileToggle" aria-label="Toggle Sidebar">
        <span class="ham-line"></span>
        <span class="ham-line"></span>
        <span class="ham-line"></span>
      </button>

      <img src="/leanOnBot.png" class="logo-icon" alt="LeanOnBot Logo" />

      <div class="logo-separation">
        <span class="logo-text">LeanOnBot</span>
        <p class="subtext">Admin Panel</p>
      </div>

    </div>

    <!-- Right Section -->
    <div class="right-section">
      <NotificationPanel @view-all="handleViewAll" />
    </div>

  </header>
</template>

<script setup>
import NotificationPanel from '../components/NotificationPanel.vue'
import { useSidebarToggle } from '@/composables/useSidebarToggle'

const { triggerMobileToggle } = useSidebarToggle()

defineEmits(['toggle-sidebar'])

function handleViewAll() {}
</script>

<style scoped src="../assets/header-sidebar/headerAdmin.css"></style>