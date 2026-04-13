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
          <div class="new-convo-btn" @click="createNewChat" style="cursor: pointer;">
            <i class='bx bx-message-square-add'></i>
            New Chat
          </div>
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
          <div v-for="(chat, index) in chats" :key="chat.id" class="chat-convo-container" :class="{ 'active-chat': isSelected(chat.id) }" @click="selectChat(chat.id)" style="cursor: pointer;">
            <div class="title-3dots-separation">

              <div class="chat-text">
                <h4 class="chat-title">{{ chat.title }}</h4>
                <p class="chat-time">{{ formatDate(chat.updated_at) }}</p>
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
            <img :src="'/leanOnBot.png'" class="logo-icon" style="object-fit: cover; border-radius: 50%;" />
          </div>

          <div class="title-footer">
            <span class="logo-text">{{ userProfile.first_name }} {{ userProfile.last_name }}</span>
            <p class="subtext">{{ userProfile.email }}</p>
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
                <p>{{ formatDate(chat.updated_at) }}</p>
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
                <p>{{ formatDate(chat.updated_at) }}</p>
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
        <div v-for="(chat, index) in filteredSearchResults" :key="index" class="search-result-item" @click="selectChat(chat.id)" style="cursor: pointer;">
          <h4>{{ chat.title }}</h4>
          <p>{{ formatDate(chat.updated_at) }}</p>
        </div>
        <p v-if="filteredSearchResults.length === 0" class="search-empty">No results found</p>
      </div>

      <button class="search-modal-close-btn" @click="closeSearchModal">Close</button>
    </div>
  </div>
</transition>

    <!-- CONFIRMATION MODAL -->
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

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useChats } from '@/composables/useChats'
import ConfirmationModal from '@/components/ConfirmationModal.vue'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const emit = defineEmits(['toggle', 'select-chat'])
defineProps({ open: Boolean })

const userProfile = ref({
  first_name: 'Loading...',
  last_name: '',
  email: ''
})

const fetchUserProfile = async () => {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('/api/user', {
      headers: { Authorization: `Bearer ${token}` }
    })
    userProfile.value = res.data
  } catch {
    console.error('Failed to fetch user profile')
  }
}

/* API DATA */
const { chats, fetchConversations, addConversation, removeConversation, updateConversation } = useChats()

onMounted(() => {
  fetchConversations()
  fetchUserProfile()
})

/* Saved chats */
const savedChats = computed(() => {
  return chats.value.filter(c => c.is_saved)
})

const showSavedModal = ref(false)
const openSavedModal = () => showSavedModal.value = true
const closeSavedModal = () => showSavedModal.value = false

/* SEARCH CHAT MODAL */
const showSearchModal = ref(false)
const searchQuery = ref('')
const openSearchModal = () => { showSearchModal.value = true; searchQuery.value = '' }
const closeSearchModal = () => showSearchModal.value = false

const filteredSearchResults = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()
  const allChats = [
    ...chats.value.map(c => ({ ...c, type: 'Chat' }))
  ]
  if (!query) return allChats
  return allChats.filter(c => (c.title && c.title.toLowerCase().includes(query)) || (c.last_message && c.last_message.toLowerCase().includes(query)))
})

/* Logout / Modals */
const showLogoutModal = ref(false)
const openModal = () => showLogoutModal.value = true
const closeModal = () => showLogoutModal.value = false
const confirmLogout = () => {
    closeModal() // Close the user menu modal first
    openConfirmModal({
        title: 'Logout',
        message: 'Are you sure you want to logout?',
        confirmText: 'Logout',
        type: 'danger',
        actionCallback: () => {
            localStorage.removeItem("token");
            window.location.href = "/login"
        }
    })
}

/* Archived Modal */
const showArchivedModal = ref(false)
const archivedChats = ref([])
const openArchivedModal = () => showArchivedModal.value = true
const closeArchivedModal = () => showArchivedModal.value = false

/* DROPDOWN */
const dropdown = ref({ visible: false, top: 0, left: 0, index: null })
const openDropdown = (event, index) => {
  event.stopPropagation()
  const rect = event.target.getBoundingClientRect()
  dropdown.value = { visible: true, top: rect.bottom + window.scrollY, left: rect.left + window.scrollX, index }
}
const closeDropdown = () => { dropdown.value.visible = false }
onMounted(() => window.addEventListener('click', closeDropdown))
onBeforeUnmount(() => window.removeEventListener('click', closeDropdown))

/* ACTIONS */
const createNewChat = async () => {
  try {
    const res = await axios.post('/api/conversations')
    addConversation(res.data)
    emit('select-chat', res.data.id)
    if (router.currentRoute.value.path !== '/ChatConvo') {
      router.push({ path: '/ChatConvo', query: { conversation_id: res.data.id } })
    } else {
      router.push({ query: { conversation_id: res.data.id } })
    }
  } catch {
    toast.error('Failed to create new chat')
  }
}

const selectChat = (id) => {
  emit('select-chat', id)
  if (router.currentRoute.value.path !== '/ChatConvo') {
    router.push({ path: '/ChatConvo', query: { conversation_id: id }})
  } else {
    router.push({ query: { conversation_id: id }})
  }
}

const isSelected = (id) => {
  return route.query.conversation_id == id
}

/* CONFIRM MODAL STATE */
const confirmModal = ref({
  visible: false,
  title: '',
  message: '',
  confirmText: '',
  cancelText: 'Cancel',
  type: 'primary',
  actionCallback: null
})

const openConfirmModal = (options) => {
  confirmModal.value = { ...confirmModal.value, ...options, visible: true }
}
const cancelConfirm = () => {
  confirmModal.value.visible = false
}
const executeConfirm = async () => {
  if (confirmModal.value.actionCallback) {
    await confirmModal.value.actionCallback()
  }
  confirmModal.value.visible = false
}

const archiveChat = () => {
  openConfirmModal({
    title: 'Archive Chat',
    message: 'Are you sure you want to archive this chat?',
    confirmText: 'Archive',
    type: 'primary',
    actionCallback: () => {
      toast.info('Archive feature coming soon')
      closeDropdown()
    }
  })
}

const deleteChat = (index) => {
  if (index == null) return
  const id = chats.value[index].id
  
  openConfirmModal({
    title: 'Delete Chat',
    message: 'Are you sure you want to permanently delete this chat?',
    confirmText: 'Delete',
    type: 'danger',
    actionCallback: async () => {
      try {
        await axios.delete(`/api/conversations/${id}`)
        removeConversation(id)
        closeDropdown()
        toast.success('Chat deleted successfully!')
      } catch {
        toast.error("Failed to delete chat")
      }
    }
  })
}

const saveChat = (index) => {
  if (index == null) return
  const chat = chats.value[index]
  if (chat.is_saved) {
    toast.info('Already saved!')
    closeDropdown()
    return
  }

  openConfirmModal({
    title: 'Save Chat',
    message: 'Do you want to save this chat to your bookmarks?',
    confirmText: 'Save',
    type: 'primary',
    actionCallback: async () => {
      try {
        await axios.patch(`/api/conversations/${chat.id}`, { is_saved: true })
        updateConversation(chat.id, { is_saved: true })
        toast.success('Chat saved successfully!')
        closeDropdown()
      } catch {
        toast.error("Failed to save chat")
      }
    }
  })
}

const deleteSavedChat = (index) => {
  const chat = savedChats.value[index]
  
  openConfirmModal({
    title: 'Remove Saved Chat',
    message: 'Remove this chat from your saved list?',
    confirmText: 'Remove',
    type: 'danger',
    actionCallback: async () => {
      try {
        await axios.patch(`/api/conversations/${chat.id}`, { is_saved: false })
        updateConversation(chat.id, { is_saved: false })
        toast.success('Removed from saved!')
      } catch {
        toast.error("Failed to update chat")
      }
    }
  })
}

const viewChat = (chat) => {
  selectChat(chat.id)
  closeSavedModal()
}

const deleteArchivedChat = () => {}
const restoreChat = () => {}

const formatDate = (dateString) => {
  if (!dateString) return ''

  const date = new Date(dateString)
  const now = new Date()
  const diff = Math.floor((now - date) / 60000)

  if (diff < 1) return 'Just now'

  const minutes = diff
  const hours = Math.floor(diff / 60)

  if (minutes < 60) {
    return `${minutes} min${minutes > 1 ? 's' : ''} ago`
  }
  if (minutes < 1440) {
    return `${hours} hr${hours > 1 ? 's' : ''} ago`
  }
  return date.toLocaleDateString()
}
</script>

<style scoped src="../assets/Header & Sidebar/sidebar.css"></style>