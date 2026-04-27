<template>
  <aside :class="['sidebar', { collapsed: !open }]">

    <!-- ── COLLAPSED RAIL ── -->
    <div v-if="!open" class="rail-nav">
  <button class="sidebar-toggle rail-toggle" @click="$emit('toggle')" title="Open Sidebar">
    <i class='bx bx-dock-left'></i>
  </button>

  <button class="rail-btn" @click="createNewChat" title="New Chat">
    <i class='bx bx-message-square-add'></i>
  </button>

  <button class="rail-btn" @click="openSearchModal" title="Search">
    <i class='bx bx-search'></i>
  </button>

  <button class="rail-btn" @click="openSavedModal" title="Saved">
    <i class='bx bx-bookmark'></i>
  </button>

  <div class="rail-divider"></div>

  <!-- 👇 ADD THIS CLASS -->
  <button class="rail-btn rail-avatar bottom-avatar" @click="openModal" title="Account">
    <img :src="'/leanOnBot.png'" />
  </button>
</div>

    <!-- ── EXPANDED FULL SIDEBAR ── -->
    <div v-else class="sidebar-full">
      <div class="sidebar-header">
        <div class="header-left">
          <i class='bx bx-user user-icon'></i>
          <span>Student Panel</span>
        </div>
        <button class="sidebar-toggle" @click="$emit('toggle')" title="Close Sidebar">
          <i class='bx bx-dock-left'></i>
        </button>
      </div>
      <hr />

      <nav class="menu">
        <div class="button-container">
          <div class="new-convo-btn" @click="createNewChat">
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

        <div class="chat-convo-module">
          <div
            v-for="(chat, index) in chats"
            :key="chat.id"
            class="chat-convo-container"
            :class="{ 'active-chat': isSelected(chat.id) }"
            @click="selectChat(chat.id)"
          >
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

        <div
          v-if="dropdown.visible"
          class="dropdown-menu"
          :style="{ top: dropdown.top + 'px', left: dropdown.left + 'px' }"
          @click.stop
        >
          <div class="dropdown-item" @click="saveChat(dropdown.index)"><i class='bx bx-save'></i> Save</div>
          <div class="dropdown-item" @click="archiveChat(dropdown.index)"><i class='bx bx-archive'></i> Archive</div>
          <div class="dropdown-item delete" @click="deleteChat(dropdown.index)"><i class='bx bx-trash'></i> Delete</div>
        </div>
      </nav>

      <div class="logout">
        <div class="picture-info-separation" @click="openModal">
          <div class="picture">
            <img :src="'/leanOnBot.png'" class="logo-icon" />
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
    </div>

    <!-- ══════════════════════════════════
         ALL MODALS — outside v-if/v-else
         so they always render in the DOM
    ══════════════════════════════════ -->

    <!-- LOGOUT MODAL — anchored above avatar when collapsed, above footer when expanded -->
    <transition name="modal-fade">
      <div
        v-if="showLogoutModal"
        class="modal-overlay"
        @click="closeModal"
      >
        <div
          class="modal-container"
          :class="open ? 'modal-expanded' : 'modal-collapsed'"
          @click.stop
        >
          <div class="modal-item">
            <i class='bx bx-user'></i>
            <router-link to="/MyAccount" class="my-account">My Account</router-link>
          </div>
          <div class="modal-item" @click="openArchivedModal">
            <i class='bx bx-archive'></i>
            <span>Archived</span>
          </div>
          <div class="modal-item logout-item" @click="confirmLogout">
            <i class='bx bx-log-out'></i>
            <span>Logout</span>
          </div>
        </div>
      </div>
    </transition>

    <!-- ARCHIVED MODAL -->
    <!-- ARCHIVED MODAL -->
<transition name="archived-modal">
  <div v-if="showArchivedModal" class="archived-overlay" @click.self="closeArchivedModal">
    <div class="archived-container" @click.stop>

      <div class="archived-header">
        <h3 class="archived-title">Archived Chats</h3>
        <button class="archived-close-x" @click="closeArchivedModal">
          <i class='bx bx-x'></i>
        </button>
      </div>

      <div class="archived-list">
        <div v-for="(chat, index) in archivedChats" :key="chat.id" class="archived-item">
          <div class="archived-text">
            <h4>{{ chat.title }}</h4>
            <p>{{ formatDate(chat.updated_at) }}</p>
          </div>
          <div class="archived-actions">
            <button @click="restoreChat(index)" title="Restore"><i class='bx bx-undo'></i></button>
            <button @click="deleteArchivedChat(index)" title="Delete"><i class='bx bx-trash'></i></button>
          </div>
        </div>
        <div v-if="archivedChats.length === 0" class="empty-state">
          <div class="empty-state-icon"><i class='bx bx-archive'></i></div>
          <p class="empty-state-title">Nothing archived yet</p>
          <p class="empty-state-sub">Archived chats will appear here</p>
        </div>
      </div>
    </div>
  </div>
</transition>

    <!-- SAVED MODAL -->
    <transition name="saved-modal">
      <div v-if="showSavedModal" class="saved-overlay" @click.self="closeSavedModal">
        <div class="saved-container" @click.stop>
          <h3 class="saved-title">Saved Chats</h3>
          <div class="saved-list">
            <div v-for="(chat, index) in savedChats" :key="index" class="saved-item">
              <div class="saved-text">
                <h4>{{ chat.title }}</h4>
                <p>{{ formatDate(chat.updated_at) }}</p>
              </div>
              <div class="saved-actions">
                <button @click="viewChat(chat)"><i class='bx bx-show'></i></button>
                <button @click="deleteSavedChat(index)"><i class='bx bx-trash'></i></button>
              </div>
            </div>
            <div v-if="savedChats.length === 0" class="empty-state">
              <div class="empty-state-icon"><i class='bx bx-bookmark'></i></div>
              <p class="empty-state-title">No saved chats</p>
              <p class="empty-state-sub">Bookmark a chat to see it here</p>
            </div>
          </div>
          <button class="close-saved-btn" @click="closeSavedModal">Close</button>
        </div>
      </div>
    </transition>

    <!-- SEARCH MODAL -->
    <transition name="search-modal">
      <div v-if="showSearchModal" class="search-modal-overlay" @click.self="closeSearchModal">
        <div class="search-modal-container" @click.stop>
          <h3 class="search-modal-title">Search Chats</h3>
          <input
            type="text"
            v-model="searchQuery"
            class="search-modal-input"
            placeholder="Search your chats..."
            autofocus
          />
          <div class="search-results">
            <div
              v-for="(chat, index) in filteredSearchResults"
              :key="index"
              class="search-result-item"
              @click="selectChat(chat.id); closeSearchModal()"
            >
              <h4>{{ chat.title }}</h4>
              <p>{{ formatDate(chat.updated_at) }}</p>
            </div>
            <div v-if="filteredSearchResults.length === 0" class="empty-state">
              <div class="empty-state-icon"><i class='bx bx-search-alt'></i></div>
              <p class="empty-state-title">No results found</p>
              <p class="empty-state-sub">Try a different keyword</p>
            </div>
          </div>
          <button class="search-modal-close-btn" @click="closeSearchModal">Close</button>
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

  </aside>
</template>

<script setup>
// script is 100% unchanged from your working version
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

const userProfile = ref({ first_name: 'Loading...', last_name: '', email: '' })

const fetchUserProfile = async () => {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('/api/user', { headers: { Authorization: `Bearer ${token}` } })
    userProfile.value = res.data
  } catch { console.error('Failed to fetch user profile') }
}

const { chats, fetchConversations, addConversation, removeConversation, updateConversation } = useChats()

onMounted(() => { fetchConversations(); fetchUserProfile() })

const savedChats = computed(() => chats.value.filter(c => c.is_saved))
const showSavedModal = ref(false)
const openSavedModal = () => { showSavedModal.value = true }
const closeSavedModal = () => { showSavedModal.value = false }

const showSearchModal = ref(false)
const searchQuery = ref('')
const openSearchModal = () => { showSearchModal.value = true; searchQuery.value = '' }
const closeSearchModal = () => { showSearchModal.value = false }
const filteredSearchResults = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()
  const all = chats.value.map(c => ({ ...c, type: 'Chat' }))
  if (!query) return all
  return all.filter(c =>
    (c.title && c.title.toLowerCase().includes(query)) ||
    (c.last_message && c.last_message.toLowerCase().includes(query))
  )
})

const showLogoutModal = ref(false)
const openModal = () => { showLogoutModal.value = true }
const closeModal = () => { showLogoutModal.value = false }
const confirmLogout = () => {
  closeModal()
  openConfirmModal({
    title: 'Logout',
    message: 'Are you sure you want to logout?',
    confirmText: 'Logout',
    type: 'danger',
    actionCallback: () => { localStorage.removeItem('token'); window.location.href = '/login' }
  })
}

const showArchivedModal = ref(false)
const archivedChats = ref([])
const openArchivedModal = async () => {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('/api/conversations?archived=1', { headers: { Authorization: `Bearer ${token}` } })
    archivedChats.value = Array.isArray(res.data) ? res.data : (res.data.data ?? [])
  } catch { toast.error('Failed to load archived chats'); archivedChats.value = [] }
  showArchivedModal.value = true
}
const closeArchivedModal = () => { showArchivedModal.value = false }

const restoreChat = (index) => {
  const chat = archivedChats.value[index]
  openConfirmModal({
    title: 'Restore Chat', message: 'Restore this chat from archive?', confirmText: 'Restore', type: 'primary',
    actionCallback: async () => {
      try {
        const token = localStorage.getItem('token')
        await axios.patch(`/api/conversations/${chat.id}`, { is_archived: false }, { headers: { Authorization: `Bearer ${token}` } })
        archivedChats.value.splice(index, 1)
        updateConversation(chat.id, { is_archived: false })
        toast.success('Chat restored!')
      } catch { toast.error('Failed to restore chat') }
    }
  })
}

const deleteArchivedChat = (index) => {
  const chat = archivedChats.value[index]
  openConfirmModal({
    title: 'Delete Archived Chat', message: 'Permanently delete this archived chat?', confirmText: 'Delete', type: 'danger',
    actionCallback: async () => {
      try {
        const token = localStorage.getItem('token')
        await axios.delete(`/api/conversations/${chat.id}`, { headers: { Authorization: `Bearer ${token}` } })
        archivedChats.value.splice(index, 1)
        removeConversation(chat.id)
        toast.success('Archived chat deleted!')
      } catch { toast.error('Failed to delete archived chat') }
    }
  })
}

const dropdown = ref({ visible: false, top: 0, left: 0, index: null })
const openDropdown = (event, index) => {
  event.stopPropagation()
  const rect = event.target.getBoundingClientRect()
  dropdown.value = { visible: true, top: rect.bottom + window.scrollY, left: rect.left + window.scrollX, index }
}
const closeDropdown = () => { dropdown.value.visible = false }
onMounted(() => window.addEventListener('click', closeDropdown))
onBeforeUnmount(() => window.removeEventListener('click', closeDropdown))

const createNewChat = async () => {
  try {
    const res = await axios.post('/api/conversations')
    addConversation(res.data)
    emit('select-chat', res.data.id)
    router.currentRoute.value.path !== '/ChatConvo'
      ? router.push({ path: '/ChatConvo', query: { conversation_id: res.data.id } })
      : router.push({ query: { conversation_id: res.data.id } })
  } catch { toast.error('Failed to create new chat') }
}

const selectChat = (id) => {
  emit('select-chat', id)
  router.currentRoute.value.path !== '/ChatConvo'
    ? router.push({ path: '/ChatConvo', query: { conversation_id: id } })
    : router.push({ query: { conversation_id: id } })
}

const isSelected = (id) => route.query.conversation_id == id

const confirmModal = ref({ visible: false, title: '', message: '', confirmText: '', cancelText: 'Cancel', type: 'primary', actionCallback: null })
const openConfirmModal = (options) => { confirmModal.value = { ...confirmModal.value, ...options, visible: true } }
const cancelConfirm = () => { confirmModal.value.visible = false }
const executeConfirm = async () => {
  if (confirmModal.value.actionCallback) await confirmModal.value.actionCallback()
  confirmModal.value.visible = false
}

const archiveChat = (index) => {
  if (index == null) return
  const chat = chats.value[index]
  openConfirmModal({
    title: 'Archive Chat', message: 'Are you sure you want to archive this chat?', confirmText: 'Archive', type: 'primary',
    actionCallback: async () => {
      try {
        const token = localStorage.getItem('token')
        await axios.patch(`/api/conversations/${chat.id}`, { is_archived: true }, { headers: { Authorization: `Bearer ${token}` } })
        removeConversation(chat.id); toast.success('Chat archived!'); closeDropdown()
      } catch { toast.error('Failed to archive chat') }
    }
  })
}

const deleteChat = (index) => {
  if (index == null) return
  const id = chats.value[index].id
  openConfirmModal({
    title: 'Delete Chat', message: 'Are you sure you want to permanently delete this chat?', confirmText: 'Delete', type: 'danger',
    actionCallback: async () => {
      try {
        await axios.delete(`/api/conversations/${id}`)
        removeConversation(id); closeDropdown(); toast.success('Chat deleted successfully!')
      } catch { toast.error('Failed to delete chat') }
    }
  })
}

const saveChat = (index) => {
  if (index == null) return
  const chat = chats.value[index]
  if (chat.is_saved) { toast.info('Already saved!'); closeDropdown(); return }
  openConfirmModal({
    title: 'Save Chat', message: 'Do you want to save this chat to your bookmarks?', confirmText: 'Save', type: 'primary',
    actionCallback: async () => {
      try {
        await axios.patch(`/api/conversations/${chat.id}`, { is_saved: true })
        updateConversation(chat.id, { is_saved: true }); toast.success('Chat saved successfully!'); closeDropdown()
      } catch { toast.error('Failed to save chat') }
    }
  })
}

const deleteSavedChat = (index) => {
  const chat = savedChats.value[index]
  openConfirmModal({
    title: 'Remove Saved Chat', message: 'Remove this chat from your saved list?', confirmText: 'Remove', type: 'danger',
    actionCallback: async () => {
      try {
        await axios.patch(`/api/conversations/${chat.id}`, { is_saved: false })
        updateConversation(chat.id, { is_saved: false }); toast.success('Removed from saved!')
      } catch { toast.error('Failed to update chat') }
    }
  })
}

const viewChat = (chat) => { selectChat(chat.id); closeSavedModal() }

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const diff = Math.floor((now - date) / 60000)
  if (diff < 1) return 'Just now'
  const hours = Math.floor(diff / 60)
  if (diff < 60) return `${diff} min${diff > 1 ? 's' : ''} ago`
  if (diff < 1440) return `${hours} hr${hours > 1 ? 's' : ''} ago`
  return date.toLocaleDateString()
}
</script>

<style scoped src="../assets/Header & Sidebar/sidebar.css"></style>