<template>
  <header class="header">

    <!-- Left Logo + Sidebar Toggle -->
    <div class="logo-section">
      <!-- Sidebar Toggle Button -->
      <i class='bx bx-menu menu-toggle' @click="toggleSidebar"></i>

      <img src="/leanOnBot.png" class="logo-icon" />

      <div class="logo-separation">
        <span class="logo-text">LeanOnBot</span>
        <p class="subtext">Your wellness companion</p>
      </div>
      
    </div>

    <!-- Right Section -->
    <div class="right-section">
    

    </div>
  </header>

  <!-- Include Sidebar and pass state -->
  <Sidebar :open="openSidebar" @toggle="toggleSidebar" />
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from './sidebarStudent.vue'


// Sidebar state lifted to header
const openSidebar = ref(false)

// Toggle functions
function toggleSidebar() {
  openSidebar.value = !openSidebar.value
}
</script>

<style scoped src="../assets/Header & Sidebar/header.css"></style>
