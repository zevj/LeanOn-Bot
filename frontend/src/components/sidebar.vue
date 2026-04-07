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
          <router-link to="/ChatConvo" class="new-convo-btn">
            <i class='bx bx-message-square-add'></i>
            New Chat
          </router-link>
        </div>

        <div class="main-menu">
          <div class="menu-item" @click="openSearchModal">
            <i class='bx bx-search'></i>
            <span>Search Chat</span>
          </div>
          <div class="menu-item" @click="openSavedModal">
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
          <div class="dropdown-item" @click="saveChat(dropdown.index)">
            <i class='bx bx-save'></i> Save
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
          <router-link to="/CrisisAlert" class="modal-item">
            <i class='bx bx-error-circle'></i>
            <span class="crisis-text">Crisis Alerts</span>
          </router-link>
          <div class="modal-item logout-item" @click="confirmLogout">
            <i class='bx bx-log-out'></i>
            <span>Logout</span>
          </div>
        </div>
      </div>
    </transition>

    <!-- ARCHIVED MODAL -->
    <transition name="archived-modal">
      <div 
        v-if="showArchivedModal" 
        class="archived-overlay" 
        @click="closeArchivedModal"
      >
        <div class="archived-container" @click.stop>
          <h3 class="archived-title">Archived Chats</h3>

          <div class="archived-list">
            <div 
              v-for="(chat, index) in archivedChats" 
              :key="index" 
              class="archived-item"
            >
              <div class="archived-text">
                <h4>{{ chat.title }}</h4>
                <p>{{ chat.date }}</p>
              </div>

              <div class="archived-actions">
                <button @click="restoreChat(index)">
                  <i class='bx bx-undo'></i>
                </button>
                <button @click="deleteArchivedChat(index)">
                  <i class='bx bx-trash'></i>
                </button>
              </div>
            </div>

            <p v-if="archivedChats.length === 0" class="empty">
              No archived chats
            </p>
          </div>

          <button class="close-archived-btn" @click="closeArchivedModal">
            Close
          </button>
        </div>
      </div>
    </transition>

    <!-- SAVED MODAL -->
    <transition name="saved-modal">
      <div 
        v-if="showSavedModal" 
        class="saved-overlay" 
        @click="closeSavedModal"
      >
        <!-- Only this container animates -->
        <div class="saved-container" @click.stop>
          <h3 class="saved-title">Saved Chats</h3>

          <div class="saved-list">
            <div 
              v-for="(chat, index) in savedChats" 
              :key="index" 
              class="saved-item"
            >
              <div class="saved-text">
                <h4>{{ chat.title }}</h4>
                <p>{{ chat.date }}</p>
              </div>

              <div class="saved-actions">
                <button @click="viewChat(chat)">
                  <i class='bx bx-show'></i>
                </button>
                <button @click="deleteSavedChat(index)">
                  <i class='bx bx-trash'></i>
                </button>
              </div>
            </div>

            <p v-if="savedChats.length === 0" class="empty">
              No saved chats
            </p>
          </div>

          <button class="close-saved-btn" @click="closeSavedModal">
            Close
          </button>
        </div>
      </div>
    </transition>

    <!-- SEARCH CHAT MODAL -->
<transition name="search-modal">
  <div 
    v-if="showSearchModal" 
    class="search-modal-overlay" 
    @click="closeSearchModal"
  >
    <div class="search-modal-container" @click.stop>
      <h3 class="search-modal-title">Search Chats</h3>
      <input 
        type="text" 
        v-model="searchQuery" 
        class="search-modal-input" 
        placeholder="Search your chats..."
      />

      <div class="search-results">
        <div v-for="(chat, index) in filteredSearchResults" :key="index" class="search-result-item">
          <h4>{{ chat.title }}</h4>
          <p>{{ chat.date || chat.time }}</p>
        </div>
        <p v-if="filteredSearchResults.length === 0" class="search-empty">No results found</p>
      </div>

      <button class="search-modal-close-btn" @click="closeSearchModal">Close</button>
    </div>
  </div>
</transition>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

/* Sidebar props */
defineProps({ open: Boolean })

/* MOCK DATA */
const chats = ref([
  { title: 'How to install XAMPP', time: 'Just now' },
  { title: 'Vue Sidebar Design', time: '5 mins ago' },
  { title: 'Fix CSS alignment issue', time: '10 mins ago' },
  { title: 'Database normalization', time: '30 mins ago' },
])

/* Archived chats */
const archivedChats = ref([])

/* Saved chats */
const savedChats = ref([
  { title: 'How to install XAMPP', date: '2026-03-31 12:00 PM' },
  { title: 'Vue Sidebar Design', date: '2026-03-31 11:45 AM' },
])
const showSavedModal = ref(false)
const openSavedModal = () => showSavedModal.value = true
const closeSavedModal = () => showSavedModal.value = false

/* SEARCH CHAT MODAL */
const showSearchModal = ref(false)
const searchQuery = ref('')

// Open search modal
const openSearchModal = () => {
  showSearchModal.value = true
  searchQuery.value = ''
}

// Close search modal
const closeSearchModal = () => showSearchModal.value = false

// Computed filtered search results from chats, archivedChats, savedChats
import { computed } from 'vue'
const filteredSearchResults = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()

  // combine all chats
  const allChats = [
    ...chats.value.map(c => ({ ...c, type: 'Chat' })),
    ...archivedChats.value.map(c => ({ ...c, type: 'Archived Chat' })),
    ...savedChats.value.map(c => ({ ...c, type: 'Saved Chat' }))
  ]

  // if query is empty, show all chats
  if (!query) return allChats

  // otherwise, filter
  return allChats.filter(c => c.title.toLowerCase().includes(query))
})

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

/* DROPDOWN */
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
const closeDropdown = () => { dropdown.value.visible = false }
onMounted(() => window.addEventListener('click', closeDropdown))
onBeforeUnmount(() => window.removeEventListener('click', closeDropdown))

/* ARCHIVE / RESTORE / DELETE */
const archiveChat = (index) => {
  if (index == null) return
  const chat = chats.value[index]
  // Remove from main chats
  chats.value.splice(index, 1)
  // Add to archived
  archivedChats.value.unshift({ ...chat, date: new Date().toLocaleString() })
  // Close dropdown
  closeDropdown()
  // Show success toast
  toast.success('Chat archived successfully!')
}

const restoreChat = (index) => {
  const chat = archivedChats.value[index]
  archivedChats.value.splice(index, 1)
  chats.value.unshift(chat)
  toast.success('Chat restored successfully!')
}

const deleteChat = (index) => {
  if (index == null) return
  chats.value.splice(index, 1)       // remove chat
  closeDropdown()                     // close dropdown menu
  toast.success('Chat deleted successfully!')  // show toast
}

const deleteArchivedChat = (index) => {
  if (index == null) return
  archivedChats.value.splice(index, 1)      // remove archived chat
  toast.success('Archived chat deleted successfully!')  // show toast
}

/* SAVED CHAT */
const saveChat = (index) => {
  if (index == null) return
  const chat = chats.value[index]
  if (savedChats.value.find(c => c.title === chat.title)) {
    toast.info('Already saved!')
    return
  }
  savedChats.value.unshift({ ...chat, date: new Date().toLocaleString() })
  toast.success('Chat saved successfully!')
  closeDropdown()
}

const viewChat = (chat) => {
  toast.info(`Viewing: ${chat.title}`)
}

const deleteSavedChat = (index) => {
  savedChats.value.splice(index, 1)
  toast.success('Removed from saved!')
}
</script>

<style scoped src="../assets/Header & Sidebar/sidebar.css"></style>