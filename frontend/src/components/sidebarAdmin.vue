<template>
  <!-- Mobile backdrop -->
  <transition name="backdrop-fade">
    <div
      v-if="isMobile && mobileOpen"
      class="mobile-backdrop"
      @click="closeMobileSidebar"
    ></div>
  </transition>

  <aside
    :class="[
      'sidebar',
      {
        'collapsed': !open && !isMobile,
        'mobile-hidden': isMobile && !mobileOpen,
        'mobile-visible': isMobile && mobileOpen,
        'is-mobile': isMobile
      }
    ]"
  >

    <!-- ── COLLAPSED RAIL (desktop only) ── -->
    <div
      v-if="!isMobile && !open"
      class="rail-nav"
      @click="handleRailClick"
    >
      <button class="sidebar-toggle rail-toggle" @click.stop="$emit('toggle')" title="Expand">
        <i class='bx bx-dock-left'></i>
      </button>

      <router-link to="/AdminDashboard" class="rail-btn" title="Dashboard" @click.stop>
        <i class='bx bx-grid-alt'></i>
      </router-link>

      <router-link to="/AdminCrisisAlerts" class="rail-btn" title="Crisis Alerts" @click.stop>
        <i class='bx bx-shield'></i>
      </router-link>

      <router-link to="/EmotionalTrends" class="rail-btn" title="Emotional Trends" @click.stop>
        <i class='bx bx-line-chart'></i>
      </router-link>

      <router-link to="/AdminAnalytics" class="rail-btn" title="AI Analytics" @click.stop>
        <i class='bx bx-brain'></i>
      </router-link>

      <router-link to="/AdminLogRecords" class="rail-btn" title="Log Records" @click.stop>
        <i class='bx bx-file'></i>
      </router-link>

      <div class="rail-divider"></div>

      <button class="rail-btn bottom-avatar" @click.stop="showLogoutModal = true" title="Account">
        <img src="/leanOnBot.png" />
      </button>
    </div>

    <!-- ── EXPANDED SIDEBAR (desktop expanded + all mobile) ── -->
    <div v-if="open || isMobile" class="sidebar-full">

      <div class="sidebar-top">
        <div class="logo-box">
          <i class='bx bx-user'></i>
        </div>
        <div class="logo-texts">
          <h3>LeanOn Bot</h3>
          <span>Admin Panel</span>
        </div>
        <!-- Desktop: dock icon | Mobile: X close -->
        <button class="sidebar-toggle" @click.stop="handleCloseBtn" title="Close Sidebar">
          <i :class="isMobile ? 'bx bx-x' : 'bx bx-dock-left'"></i>
        </button>
      </div>

      <nav class="menu">
        <p class="menu-label">MENU</p>

        <router-link to="/AdminDashboard" class="menu-item" active-class="active" @click="handleNavClick">
          <i class='bx bx-grid-alt'></i>
          <span>Dashboard</span>
        </router-link>

        <router-link to="/AdminCrisisAlerts" class="menu-item" active-class="active" @click="handleNavClick">
          <i class='bx bx-shield'></i>
          <span>Crisis Alerts</span>
        </router-link>

        <router-link to="/EmotionalTrends" class="menu-item" active-class="active" @click="handleNavClick">
          <i class='bx bx-line-chart'></i>
          <span>Emotional Trends</span>
        </router-link>

        <router-link to="/AdminAnalytics" class="menu-item" active-class="active" @click="handleNavClick">
          <i class='bx bx-brain'></i>
          <span>AI Analytics</span>
        </router-link>

        <router-link to="/AdminLogRecords" class="menu-item" active-class="active" @click="handleNavClick">
          <i class='bx bx-file'></i>
          <span>Log Records</span>
        </router-link>
      </nav>

      <div class="logout" @click="showLogoutModal = true">
        <div class="picture-info-separation">
          <img :src="adminUser.profile_image_url || '/leanOnBot.png'" class="logo-icon" />
          <div class="title-footer">
            <span class="logo-text">{{ adminName }}</span>
            <p class="subtext">{{ adminEmail }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── LOGOUT MODAL — Teleported to body, always in DOM ── -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div v-if="showLogoutModal" class="modal-overlay" @click="closeModal">
          <div
            :class="[
              isMobile ? 'modal-mobile' : (open ? 'modal-expanded' : 'modal-collapsed')
            ]"
            @click.stop
          >
            <div class="modal-item">
              <i class='bx bx-user'></i>
              <router-link to="/AdminProfile" class="my-account">My Account</router-link>
            </div>
            <div class="modal-item logout-item" @click="confirmLogout">
              <i class='bx bx-log-out'></i>
              <span>Logout</span>
            </div>
          </div>
        </div>
      </transition>

      <ConfirmationModal
        :visible="confirmModal.visible"
        :title="confirmModal.title"
        :message="confirmModal.message"
        :confirmText="confirmModal.confirmText"
        :cancelText="confirmModal.cancelText"
        :type="confirmModal.type"
        @confirm="executeConfirm"
        @cancel="cancelConfirm"
      />
    </Teleport>

  </aside>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import axios from 'axios'
import { useSidebarToggle } from '@/composables/useSidebarToggle'
import ConfirmationModal from '@/components/ConfirmationModal.vue'

const props = defineProps({
  open: Boolean,
  mobileToggle: { type: Number, default: 0 }
})

const emit = defineEmits(['toggle'])

// ── ADMIN USER INFO ──
const adminUser = ref(JSON.parse(localStorage.getItem('user') || '{}'))

const adminName = computed(() => {
  const u = adminUser.value
  const full = [u.first_name, u.last_name].filter(Boolean).join(' ')
  return full || u.name || 'Admin'
})

const adminEmail = computed(() => adminUser.value.email || '')

// ── MOBILE DETECTION ──
const MOBILE_BREAKPOINT = 768
const isMobile = ref(window.innerWidth <= MOBILE_BREAKPOINT)
const mobileOpen = ref(false)

const { mobileToggleCount } = useSidebarToggle()

const handleResize = () => {
  isMobile.value = window.innerWidth <= MOBILE_BREAKPOINT
  if (!isMobile.value) mobileOpen.value = false
}

// Watch both the prop and the composable for maximum compatibility
watch(() => props.mobileToggle, () => {
  if (isMobile.value) mobileOpen.value = !mobileOpen.value
})

watch(mobileToggleCount, () => {
  if (isMobile.value) mobileOpen.value = !mobileOpen.value
})

const closeMobileSidebar = () => {
  mobileOpen.value = false
}

const handleCloseBtn = () => {
  if (isMobile.value) {
    mobileOpen.value = false
  } else {
    emit('toggle')
  }
}

const handleRailClick = (e) => {
  const isButton = e.target.closest('button')
  const isLink = e.target.closest('a')
  if (!isButton && !isLink) {
    emit('toggle')
  }
}

// Close mobile sidebar when navigating
const handleNavClick = () => {
  if (isMobile.value) mobileOpen.value = false
}

// ── LOGOUT MODAL ──
const showLogoutModal = ref(false)

const closeModal = () => {
  showLogoutModal.value = false
}

const confirmLogout = () => {
  closeModal()
  openConfirmModal({
    title: 'Logout',
    message: 'Are you sure you want to logout?',
    confirmText: 'Logout',
    type: 'danger',
    actionCallback: async () => {
      try {
        const token = localStorage.getItem('token')
        await axios.post('/api/logout', {}, { headers: { Authorization: `Bearer ${token}` } })
      } catch (e) {
        console.error('Logout API error:', e)
      }
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
  })
}

// ── CONFIRM MODAL ──
const confirmModal = ref({ visible: false, title: '', message: '', confirmText: '', cancelText: 'Cancel', type: 'primary', actionCallback: null })
const openConfirmModal = (options) => { confirmModal.value = { ...confirmModal.value, ...options, visible: true } }
const cancelConfirm = () => { confirmModal.value.visible = false }
const executeConfirm = async () => {
  if (confirmModal.value.actionCallback) await confirmModal.value.actionCallback()
  confirmModal.value.visible = false
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped src="../assets/header-sidebar/sidebarAdmin.css"></style>