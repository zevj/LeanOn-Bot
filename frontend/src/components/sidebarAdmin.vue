<template>
  <div>
    <!-- Overlay -->
    <div v-if="open" class="overlay" @click="$emit('toggle')"></div>

    <!-- Sidebar -->
    <aside :class="['sidebar', { active: open }]">

      <!-- CLOSE -->
      <div class="close-sidebar" @click="$emit('toggle')">
        <i class='bx bx-x'></i>
      </div>

      <!-- HEADER -->
      <div class="sidebar-top">
        <div class="logo-box">
          <i class='bx bx-heart'></i>
        </div>

        <div class="logo-texts">
          <h3>LeanOn Bot</h3>
          <span>Admin Panel</span>
        </div>
      </div>

      <!-- MENU -->
      <nav class="menu">
        <p class="menu-label">MENU</p>

        <router-link to="/AdminDashboard" class="menu-item" active-class="active">
          <i class='bx bx-grid-alt'></i>
          <span>Dashboard</span>
        </router-link>

        <router-link to="/AdminCrisisAlerts" class="menu-item" active-class="active">
          <i class='bx bx-shield'></i>
          <span>Crisis Alerts</span>
        </router-link>

        <router-link to="/EmotionalTrends" class="menu-item" active-class="active">
          <i class='bx bx-line-chart'></i>
          <span>Emotional Trends</span>
        </router-link>

        <div class="menu-item">
          <i class='bx bx-message'></i>
          <span>Chatbot Responses</span>
        </div>
      </nav>

      <!-- FOOTER -->
      <div class="logout" @click="showLogoutModal = true">
        <div class="picture-info-separation">
          <img src="/leanOnBot.png" class="logo-icon" />

          <div class="title-footer">
            <span class="logo-text">Allysa C. Lingad</span>
            <p class="subtext">202310636@gordoncollege.edu.ph</p>
          </div>
        </div>
      </div>

    </aside>

    <!-- MODAL -->
    <transition name="modal">
      <div v-if="showLogoutModal" class="modal-overlay" @click="closeModal">
        <div class="modal-container" @click.stop>

          <div class="modal-item">
            <i class='bx bx-user'></i>
            <router-link to="/MyAccount" class="my-account">
              My Account
            </router-link>
          </div>

          <div class="modal-item" @click="openArchivedModal">
            <i class='bx bx-cog'></i>
            <router-link to="/MyAccount" class="my-account">
            <span>Configuration</span>
            </router-link>
          </div>

          <div class="modal-item logout-item" @click="confirmLogout">
            <i class='bx bx-log-out'></i>
            <router-link to="/MyAccount" class="logout-item">
            <span>Logout</span>
            </router-link>
          </div>

        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref } from "vue"

defineProps({
  open: Boolean
})

defineEmits(["toggle"])

const showLogoutModal = ref(false)

const closeModal = () => {
  showLogoutModal.value = false
}

const openArchivedModal = () => {
  console.log("Open Configuration Modal")
}

const confirmLogout = () => {
  console.log("Logout triggered")
  showLogoutModal.value = false
}
</script>

<style scoped src="../assets/Header & Sidebar/sidebarAdmin.css"></style>