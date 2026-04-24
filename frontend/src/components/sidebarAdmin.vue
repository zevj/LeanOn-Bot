<template>
  <aside :class="[
  'sidebar',
  { active: open, collapsed: !open }
]">

    <!-- 🔻 COLLAPSED RAIL -->
    <div v-if="!open" class="rail-nav">

      <button class="sidebar-toggle rail-toggle" @click="handleToggle">
        <i class='bx bx-dock-left'></i>
      </button>

      <router-link to="/AdminDashboard" class="rail-btn">
        <i class='bx bx-grid-alt'></i>
      </router-link>

      <router-link to="/AdminCrisisAlerts" class="rail-btn">
        <i class='bx bx-shield'></i>
      </router-link>

      <router-link to="/EmotionalTrends" class="rail-btn">
        <i class='bx bx-line-chart'></i>
      </router-link>

      <div class="rail-divider"></div>

      <!-- 👇 BOTTOM AVATAR -->
      <button class="rail-btn bottom-avatar" @click="showLogoutModal = true">
        <img src="/leanOnBot.png" />
      </button>

    </div>

    <!-- 🔻 EXPANDED SIDEBAR -->
    <div v-else class="sidebar-full">

      <!-- HEADER -->
      <div class="sidebar-top">
        <div class="logo-box">
          <i class='bx bx-heart'></i>
        </div>

        <div class="logo-texts">
          <h3>LeanOn Bot</h3>
          <span>Admin Panel</span>
        </div>

        <button class="sidebar-toggle" @click="handleToggle">
          <i class='bx bx-dock-left'></i>
        </button>
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
          <span>Log Records</span>
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

    </div>

    <!-- MODAL (UNCHANGED) -->
    <transition name="modal">
      <div v-if="showLogoutModal" class="modal-overlay" @click="closeModal">
        <div class="modal-container" @click.stop>

          <div class="modal-item">
            <i class='bx bx-user'></i>
            <router-link to="/AdminProfile" class="my-account">
              My Account
            </router-link>
          </div>

          <div class="modal-item">
            <i class='bx bx-cog'></i>
            <router-link to="/AdminConfig" class="my-account">
              <span>Configuration</span>
            </router-link>
          </div>

          <div class="modal-item logout-item" @click="confirmLogout">
            <i class='bx bx-log-out'></i>
            <span>Logout</span>
          </div>

        </div>
      </div>
    </transition>

  </aside>
</template>

<script setup>
import { ref } from "vue"

const props = defineProps({
  open: Boolean
})

const emit = defineEmits(["toggle"])

const showLogoutModal = ref(false)

/* optional safety toggle handler */
const handleToggle = () => {
  emit("toggle")
}

const closeModal = () => {
  showLogoutModal.value = false
}

const confirmLogout = () => {
  console.log("Logout triggered")
  showLogoutModal.value = false
}
</script>

<style scoped src="../assets/Header & Sidebar/sidebarAdmin.css"></style>