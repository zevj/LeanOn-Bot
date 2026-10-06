<template>
  <header class="header">

    <!-- Left: Hamburger (mobile only) + Logo -->
    <div class="logo-section">

      <!-- Mobile hamburger toggle -->
      <button button class="hamburger-btn" @click="triggerMobileToggle" aria-label="Toggle Sidebar">
        <span class="ham-line"></span>
        <span class="ham-line"></span>
        <span class="ham-line"></span>
      </button>

      <img src="/leanOnBot.png" class="logo-icon" />

      <div class="logo-separation">
        <span class="logo-text">LeanOnBot</span>
        <p class="subtext">Your wellness companion</p>
      </div>

    </div>

    <!-- Right Section -->
    <div class="right-section">
        <ThemeToggleHeader />
        <NotificationPanel @view-all="handleViewAll" />
    </div>

  </header>
</template>

<script setup>
import { ref } from 'vue'
import NotificationPanel from '../components/StudentNotificationPanel.vue'
import ThemeToggleHeader from '@/components/ThemeToggleHeader.vue'
import { useSidebarToggle } from '@/composables/useSidebarToggle'

const { triggerMobileToggle } = useSidebarToggle()  // ← add

defineEmits(['toggle-sidebar'])

function handleViewAll() {
  // handle view all notifications
}
</script>

<style scoped src="../assets/header-sidebar/header.css"></style>

<!-- repush updates -->