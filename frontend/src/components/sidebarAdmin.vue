<template>
  <aside :class="['sidebar', { collapsed: !open }]">

    <!-- ── COLLAPSED RAIL ── -->
    <div v-if="!open" class="rail-nav" @click="handleRailClick" >
  <button class="sidebar-toggle rail-toggle" @click="$emit('toggle')" title="Expand">
    <i class='bx bx-dock-left'></i>
  </button>

  <router-link to="/AdminDashboard" class="rail-btn" title="Dashboard">
    <i class='bx bx-grid-alt'></i>
  </router-link>

  <router-link to="/AdminCrisisAlerts" class="rail-btn" title="Crisis Alerts">
    <i class='bx bx-shield'></i>
  </router-link>

  <router-link to="/EmotionalTrends" class="rail-btn" title="Emotional Trends">
    <i class='bx bx-line-chart'></i>
  </router-link>

  <router-link to="/AdminLogRecords" class="rail-btn" title="Log Records">
    <i class='bx bx-file'></i>
  </router-link>

  <div class="rail-divider"></div>

  <button class="rail-btn bottom-avatar" @click="showLogoutModal = true" title="Account">
    <img src="/leanOnBot.png" />
  </button>
</div>

    <!-- ── EXPANDED SIDEBAR ── -->
    <div v-else class="sidebar-full">

      <div class="sidebar-top">
        <div class="logo-box">
          <i class='bx bx-user'></i>
        </div>
        <div class="logo-texts">
          <h3>LeanOn Bot</h3>
          <span>Admin Panel</span>
        </div>
        <button class="sidebar-toggle" @click="$emit('toggle')" title="Collapse">
          <i class='bx bx-dock-left'></i>
        </button>
      </div>

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

  <router-link to="/AdminLogRecords" class="menu-item" active-class="active">
    <i class='bx bx-file'></i>
    <span>Log Records</span>
  </router-link>
</nav>

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

    <!-- ── LOGOUT MODAL — outside v-if/v-else so always in DOM ── -->
    <transition name="modal">
      <div v-if="showLogoutModal" class="modal-overlay" @click="closeModal">
        <div
          :class="open ? 'modal-container modal-expanded' : 'modal-container modal-collapsed'"
          @click.stop
        >
          <div class="modal-item">
            <i class='bx bx-user'></i>
            <router-link to="/AdminProfile" class="my-account">My Account</router-link>
          </div>
          <div class="modal-item">
            <i class='bx bx-cog'></i>
            <router-link to="/AdminConfig" class="my-account">Configuration</router-link>
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
import { ref } from 'vue'

defineProps({ open: Boolean })
const emit = defineEmits(['toggle'])

const showLogoutModal = ref(false)

const closeModal = () => { showLogoutModal.value = false }

const confirmLogout = () => {
  showLogoutModal.value = false
  localStorage.removeItem('token')
  window.location.href = '/login'
}

// ✅ ADD THIS
const handleRailClick = (e) => {
  const isButton = e.target.closest('button')
  const isLink = e.target.closest('a')

  if (!isButton && !isLink) {
    emit('toggle')
  }
}
</script>

<style scoped src="../assets/Header & Sidebar/sidebarAdmin.css"></style>