<template>
  <div>

    <!-- Overlay for Sidebar -->
    <div v-if="open" class="overlay" @click="$emit('toggle')"></div>

    <!-- Sidebar -->
    <aside :class="['sidebar', { active: open }]">

      <!-- Header -->
      <div class="sidebar-header">
        <div class="header-left">
          <i class='bx bx-user user-icon'></i>
          <span>Student Panel</span>
        </div>
        <div class="close-btn">
          <i class='bx bx-x toggle-icon' @click="$emit('toggle')"></i>
        </div>
      </div>
      <hr>

      <!-- MENU -->
      <nav class="menu">

        <div class="button-container">
          <button class="new-convo-btn">
            <i class='bx bx-message-square-add'></i>
            New Chat
          </button>
        </div>

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

        <!-- CHAT LIST -->
        <div class="chat-convo-module">
          <div v-for="(chat, index) in chats" :key="index" class="chat-convo-container">
            <div class="title-3dots-separation">

              <div class="chat-text">
                <h4 class="chat-title">{{ chat.title }}</h4>
                <p class="chat-time">{{ chat.time }}</p>
              </div>

              <div class="menu-wrapper">
                <i class="bx bx-dots-horizontal dots" @click.stop="openDropdown($event, index)"></i>
              </div>

            </div>
          </div>
        </div>

        <!-- DROPDOWN -->
        <div 
          v-if="dropdown.visible" 
          class="dropdown-menu" 
          :style="{ top: dropdown.top + 'px', left: dropdown.left + 'px' }"
          @click.stop
        >
          <div class="dropdown-item">
            <i class='bx bx-edit'></i> Edit
          </div>
          <div class="dropdown-item" @click="archiveChat(dropdown.index)">
            <i class='bx bx-archive'></i> Archive
          </div>
          <div class="dropdown-item delete" @click="deleteChat(dropdown.index)">
            <i class='bx bx-trash'></i> Delete
          </div>
        </div>

      </nav>

      <!-- FOOTER -->
      <div class="logout">
        <div class="picture-info-separation" @click="openModal">
          <div class="picture">
            <img src="/leanOnBot.png" class="logo-icon" />
          </div>

          <div class="title-footer">
            <span class="logo-text">Allysa C. Lingad</span>
            <p class="subtext">202310636@gordoncollege.edu.ph</p>
          </div>
        </div>

        <div class="footer-buttons">
          <button class="archive-btn" @click="openArchivedModal">Archived</button>
          <button class="logout-btn" @click="openModal">Logout</button>
        </div>
      </div>

    </aside>

    <!-- LOGOUT MODAL -->
<transition name="modal">
  <div v-if="showLogoutModal" class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <div class="modal-item">
        <i class='bx bx-user'></i>
        <router-link to="/MyAccount" class="my-account">My Account</router-link>
      </div>
      <div class="modal-item" @click="openArchivedModal">
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

<!-- ARCHIVED MODAL -->
<!-- <transition name="modal">
  <div v-if="showArchivedModal" class="modal-overlay-archived" @click="closeArchivedModal">
    <div class="modal-container-archived" @click.stop>
      <h3 class="modal-title">Archived Chats</h3>
      <table class="archived-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Date Archived</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(chat, index) in archivedChats" :key="index">
            <td>{{ chat.title }}</td>
            <td>{{ chat.date }}</td>
            <td class="actions">
              <button class="restore-btn" @click="restoreChat(index)">
                <i class='bx bx-undo'></i>
              </button>
              <button class="delete-btn" @click="deleteArchivedChat(index)">
                <i class='bx bx-trash'></i>
              </button>
            </td>
          </tr>
          <tr v-if="archivedChats.length === 0">
            <td colspan="3" class="no-archived">No archived chats</td>
          </tr>
        </tbody>
      </table>
      <button class="close-modal" @click="closeArchivedModal">Close</button>
    </div>
  </div>
</transition> -->
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

/* Sidebar props */
defineProps({ open: Boolean })

/* MOCK DATA */
const chats = ref([
  { title: 'How to install XAMPP', time: 'Just now' },
  { title: 'Vue Sidebar Design', time: '5 mins ago' },
  { title: 'Fix CSS alignment issue', time: '10 mins ago' },
  { title: 'Database normalization', time: '30 mins ago' },
])

/* Archived chats state */
const archivedChats = ref([])

/* Logout Modal */
const showLogoutModal = ref(false)
const openModal = () => showLogoutModal.value = true
const closeModal = () => showLogoutModal.value = false
const confirmLogout = () => {
  localStorage.removeItem("token")
  window.location.href = "/login"
}

/* Archived Modal */
const showArchivedModal = ref(false)
const openArchivedModal = () => showArchivedModal.value = true
const closeArchivedModal = () => showArchivedModal.value = false

/* DROPDOWN STATE */
const dropdown = ref({ visible: false, top: 0, left: 0, index: null })
const openDropdown = (event, index) => {
  event.stopPropagation()
  const rect = event.target.getBoundingClientRect()
  const dropdownHeight = 120
  const spaceBelow = window.innerHeight - rect.bottom
  const spaceAbove = rect.top
  let topPos = spaceBelow >= dropdownHeight 
    ? rect.bottom + window.scrollY
    : spaceAbove >= dropdownHeight 
      ? rect.top + window.scrollY - dropdownHeight
      : Math.max(0, window.innerHeight - dropdownHeight + window.scrollY)
  dropdown.value = { visible: true, top: topPos, left: rect.left + window.scrollX, index }
}

/* Close dropdown on outside click */
const closeDropdown = () => { dropdown.value.visible = false }
onMounted(() => window.addEventListener('click', closeDropdown))
onBeforeUnmount(() => window.removeEventListener('click', closeDropdown))

/* ARCHIVE / RESTORE / DELETE */
const archiveChat = (index) => {
  if (index == null) return
  const chat = chats.value.splice(index, 1)[0]
  archivedChats.value.unshift({ ...chat, date: new Date().toLocaleDateString() })
  closeDropdown()
  openArchivedModal()
}

const restoreChat = (index) => {
  const chat = archivedChats.value.splice(index, 1)[0]
  chats.value.unshift(chat)
}

const deleteChat = (index) => {
  chats.value.splice(index, 1)
  closeDropdown()
}

const deleteArchivedChat = (index) => {
  archivedChats.value.splice(index, 1)
}
</script>

<style scoped src="../assets/Header & Sidebar/sidebar.css"></style>