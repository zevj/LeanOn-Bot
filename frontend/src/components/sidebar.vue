<template>
  <div>

    <!-- Overlay -->
    <div 
      v-if="open"
      class="overlay"
      @click="$emit('toggle')"
    ></div>

    <!-- Sidebar -->
    <aside :class="['sidebar', { active: open }]">

      <!-- Header -->
      <div class="sidebar-header">
        <!-- Left side: user + text -->
        <div class="header-left">
          <i class='bx bx-user user-icon'></i>
          <span>Student Panel</span>
        </div>

        <!-- Right side: close button -->
        <div class="close-btn">
          <i class='bx bx-x toggle-icon' @click="$emit('toggle')"></i>
        </div>
      </div>
      <hr>

      <!-- MENU -->
      <nav class="menu">

        <!-- New Chat -->
        <div class="button-container">
          <button class="new-convo-btn">
            <i class='bx bx-message-square-add'></i>
            New Chat
          </button>
        </div>

        <!-- MAIN MENU -->
        <div class="main-menu">
          <div class="menu-item">
            <i class='bx bx-search'></i>
            <span>Search Chat</span>
          </div>

          <div class="menu-item">
            <i class='bx bx-bookmark'></i>
            <span>Saved</span>
          </div>
          
        </div>

        <h4 class="chat-history-title">Chat History</h4>

        <!-- SCROLLABLE CHAT LIST -->
        <div class="chat-convo-module">
          <div 
            class="chat-convo-container"
            v-for="(chat, index) in chats"
            :key="index"
          >
            <div class="title-3dots-separation">

              <div class="chat-text">
                <h4 class="chat-title">{{ chat.title }}</h4>
                <p class="chat-time">{{ chat.time }}</p>
              </div>

              <div class="menu-wrapper">
                <i 
                  class="bx bx-dots-horizontal dots"
                  @click.stop="openDropdown($event, index)"
                ></i>
              </div>

            </div>
          </div>
        </div>

        <!-- DROPDOWN (GLOBAL, above everything) -->
    <div 
      v-if="dropdown.visible" 
      class="dropdown-menu" 
      :style="{ top: dropdown.top + 'px', left: dropdown.left + 'px' }"
      @click.stop
    >
      <div class="dropdown-item">
        <i class='bx bx-edit'></i> Edit
      </div>

      <div class="dropdown-item">
        <i class='bx bx-archive'></i> Archive
      </div>

      <div class="dropdown-item delete">
        <i class='bx bx-trash'></i> Delete
      </div>
    </div>
      </nav>

      

      <!-- FOOTER -->
      <div class="logout" @click="openModal">
        <div class="picture-info-separation">
          <div class="picture">
            <img src="/leanOnBot.png" class="logo-icon" />
          </div>

          <div class="title-footer">
            <span class="logo-text">Allysa C. Lingad</span>
            <p class="subtext">202310636@gordoncollege.edu.ph</p>
          </div>
        </div>
      </div>

    </aside>

    <!-- MODAL WITH ANIMATION -->
    <transition name="modal">
      <div 
        v-if="showLogoutModal" 
        class="modal-overlay" 
        @click="closeModal"
      >
        <div class="modal-container" @click.stop>

          <div class="modal-item">
            <i class='bx bx-user'></i>
            <span>My Account</span>
          </div>

          <div class="modal-item">
            <i class='bx bx-archive'></i>
            <span>Archived</span>
          </div>

          <div class="modal-item">
            <i class='bx bx-error-circle'></i>
            <span>Crisis Alerts</span>
          </div>

          <div class="modal-item logout-item" @click="confirmLogout">
            <i class='bx bx-log-out'></i>
            <span>Logout</span>
          </div>

        </div>
      </div>
    </transition>
    

  </div>
</template>

<script setup>
import { ref, defineProps, onMounted, onBeforeUnmount } from 'vue'

defineProps({
  open: Boolean
})

/* MOCK DATA */
const chats = ref([
  { title: 'How to install XAMPP', time: 'Just now' },
  { title: 'Vue Sidebar Design', time: '5 mins ago' },
  { title: 'Fix CSS alignment issue', time: '10 mins ago' },
  { title: 'Database normalization', time: '30 mins ago' },
  { title: 'Laravel routing help', time: '1 hour ago' },
  { title: 'API integration error', time: '2 hours ago' },
  { title: 'Login authentication bug', time: 'Today' },
  { title: 'System design discussion', time: 'Yesterday' },
  { title: 'Capstone project ideas', time: 'Yesterday' },
  { title: 'React vs Vue comparison', time: '2 days ago' },
  { title: 'CSS grid vs flexbox', time: '2 days ago' },
  { title: 'Mobile responsive layout', time: 'March 20' },
  { title: 'Dark mode UI design', time: 'March 18' },
  { title: 'Performance optimization', time: 'March 15' },
  { title: 'Final project checklist', time: 'March 10' }
])

/* LOGOUT MODAL */
const showLogoutModal = ref(false)
const openModal = () => showLogoutModal.value = true
const closeModal = () => showLogoutModal.value = false
const confirmLogout = () => {
  localStorage.removeItem("token")
  window.location.href = "/login"
}

/* DROPDOWN STATE */
const dropdown = ref({
  visible: false,
  top: 0,
  left: 0,
  index: null
})

const openDropdown = (event, index) => {
  event.stopPropagation() // prevent bubbling to window click

  const rect = event.target.getBoundingClientRect()

  if (dropdown.value.visible && dropdown.value.index === index) {
    // toggle off if same menu
    dropdown.value.visible = false
  } else {
    // show dropdown for clicked item
    dropdown.value = {
      visible: true,
      top: rect.bottom + window.scrollY,
      left: rect.left + window.scrollX,
      index
    }
  }
}

/* CLOSE DROPDOWN ON OUTSIDE CLICK */
const closeDropdown = () => {
  dropdown.value.visible = false
}

onMounted(() => {
  window.addEventListener('click', closeDropdown)
})

onBeforeUnmount(() => {
  window.removeEventListener('click', closeDropdown)
})
</script>

<style scoped src="../assets/Header & Sidebar/sidebar.css"></style>