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
      <button class="sidebar-toggle rail-toggle" @click.stop="$emit('toggle')">
        <i class='bx bx-dock-left'></i>
      </button>

      <button class="rail-btn" @click.stop="createNewChat">
        <i class='bx bx-message-square-add'></i>
      </button>

      <button class="rail-btn" @click.stop="openSearchModal">
        <i class='bx bx-search'></i>
      </button>

      <button class="rail-btn" @click.stop="openSavedModal">
        <i class='bx bx-bookmark'></i>
      </button>

      <div class="rail-divider"></div>

      <button class="rail-btn rail-avatar bottom-avatar" @click.stop="openModal">
        <img src="/leanOnBot.png"/>
      </button>
    </div>

    <!-- ── EXPANDED FULL SIDEBAR (desktop expanded + all mobile) ── -->
    <div v-if="open || isMobile" class="sidebar-full">
      <div class="sidebar-header">
        <div class="header-left">
          <i class='bx bx-user user-icon'></i>
          <span>Student Panel</span>
        </div>
        <!-- Desktop: dock icon | Mobile: X close -->
        <button class="sidebar-toggle header-toggle" @click.stop="handleCloseBtn" title="Close Sidebar">
          <i :class="isMobile ? 'bx bx-x' : 'bx bx-dock-left'"></i>
        </button>
      </div>
      <hr />

      <nav class="menu">
        <div class="button-container">
          <div class="new-convo-btn" @click.stop="createNewChat">
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

        <!-- ── INTERACTIVE CHAT HISTORY HEADER ── -->
        <div class="chat-history-header" @click.stop="toggleChatHistory">
          <div class="chat-history-label">
            <i class='bx bx-history chat-history-icon'></i>
            <h4 class="chat-history-title">Chat History</h4>
          </div>
          <i class='bx bx-chevron-down chat-history-toggle' :class="{ 'is-collapsed': !isChatHistoryExpanded }"></i>
        </div>

        <!-- ── ANIMATED CHAT HISTORY CONTAINER ── -->
        <transition name="history-collapse">
          <div v-if="isChatHistoryExpanded" class="chat-convo-module">
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
            
            <!-- Empty state fallback when no chats exist -->
            <div v-if="chats.length === 0" class="no-history-msg">
              <i class='bx bx-message-rounded-dots'></i>
              <span>No past conversations</span>
            </div>
          </div>
        </transition>

        <Teleport to="body">
          <div
            v-if="dropdown.visible"
            class="dropdown-menu"
            :style="{ top: dropdown.top + 'px', left: dropdown.left + 'px' }"
            @click.stop
          >
            <div class="dropdown-item" @click="saveChat(dropdown.index)"><i class='bx bx-save'></i> Save</div>
            <div class="dropdown-item" @click="archiveChat(dropdown.index)"><i class='bx bx-archive'></i> Archive</div>
          </div>
        </Teleport>
      </nav>

      <div class="logout">
        <div class="picture-info-separation" @click="openModal">
          <div class="picture">
            <img src="/leanOnBot.png" class="logo-icon"/>
          </div>
          <div class="title-footer">
            <span class="logo-text">{{ userProfile.first_name }} {{ userProfile.last_name }}</span>
            <p class="subtext">{{ userProfile.email }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════
         MODALS — always in DOM
    ══════════════════════════════════ -->

    <Teleport to="body">
      <!-- LOGOUT MODAL -->
      <transition name="modal-fade">
        <div v-if="showLogoutModal" class="modal-overlay" @click="closeModal">
          <div
            class="modal-container"
            :class="[
              isMobile ? 'modal-mobile' : (open ? 'modal-expanded' : 'modal-collapsed')
            ]"
            @click.stop
          >
            <div class="modal-item">
              <i class='bx bx-user'></i>
              <router-link to="/MyAccount" class="my-account">My Account</router-link>
            </div>

            <!-- ── Terms of Use & Privacy Policy (dedicated modals) ── -->
            <div class="modal-item" @click="openTermsOfUse">
              <i class='bx bx-file'></i>
              <span>Terms of Use</span>
            </div>
            <div class="modal-item" @click="openPrivacyPolicy">
              <i class='bx bx-shield'></i>
              <span>Privacy Policy</span>
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

      <!-- TermsModal: section prop controls which tab opens first -->
      <TermsModal
        :visible="showTermsModal"
        :userId="userProfile.email || 'guest'"
        :mode="termsModalMode"
        :section="termsModalSection"
        @accept="closeTermsModal"
        @close="closeTermsModal"
      />

      <!-- Dedicated standalone modals (sidebar menu items) -->
      <TermsOfUseModal
        :visible="showTermsOfUse"
        @close="showTermsOfUse = false"
      />
      <PrivacyPolicyModal
        :visible="showPrivacyPolicy"
        @close="showPrivacyPolicy = false"
      />
    </Teleport>

  </aside>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useChats } from '@/composables/useChats'
import ConfirmationModal from '@/components/ConfirmationModal.vue'
import TermsModal from '@/components/TermsModal.vue'
import TermsOfUseModal from '@/components/TermsofuseModal.vue'
import PrivacyPolicyModal from '@/components/PrivacypolicyModal.vue'
import { useSidebarToggle } from '@/composables/useSidebarToggle'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const { mobileToggleCount } = useSidebarToggle()

const props = defineProps({
  open: Boolean,
  mobileToggle: { type: Number, default: 0 }
})

// ── MOBILE DETECTION ──
const MOBILE_BREAKPOINT = 768
const isMobile = ref(window.innerWidth <= MOBILE_BREAKPOINT)
const mobileOpen = ref(false)

const handleResize = () => {
  isMobile.value = window.innerWidth <= MOBILE_BREAKPOINT
  if (!isMobile.value) mobileOpen.value = false
}

const emit = defineEmits(['toggle', 'select-chat', 'update:mobileOpen'])

// Watch both the prop and the composable to ensure maximum compatibility
watch(() => props.mobileToggle, () => {
  if (isMobile.value) mobileOpen.value = !mobileOpen.value
})

watch(mobileToggleCount, () => {
  if (isMobile.value) mobileOpen.value = !mobileOpen.value
})

const closeMobileSidebar = () => { mobileOpen.value = false }

const handleCloseBtn = () => {
  if (isMobile.value) {
    mobileOpen.value = false
  } else {
    emit('toggle')
  }
}

const handleRailClick = (e) => {
  if (e.target.closest('button')) return
  emit('toggle')
}

// ── TERMS ──
// termsModalSection controls which tab the modal opens on ('terms' | 'privacy')
const termsModalMode = ref('accept')
const termsModalSection = ref('terms')
const showTermsModal = ref(false)



const closeTermsModal = () => {
  showTermsModal.value = false
}

const handleConversationError = (error, fallbackMessage) => {
  if (error.response?.status === 403 && error.response?.data?.status === 'TERMS_REQUIRED') {
    termsModalMode.value = 'accept'
    termsModalSection.value = 'terms'
    showTermsModal.value = true
    toast.info('Please accept the terms before managing chats.')
    return
  }

  toast.error(fallbackMessage)
}

// ── DEDICATED TERMS OF USE / PRIVACY POLICY MODALS (sidebar menu) ──
const showTermsOfUse = ref(false)
const showPrivacyPolicy = ref(false)

const openTermsOfUse = () => {
  closeModal()
  showTermsOfUse.value = true
}

const openPrivacyPolicy = () => {
  closeModal()
  showPrivacyPolicy.value = true
}

// ── USER ──
const userProfile = ref({ first_name: 'Loading...', last_name: '', email: '' })

const fetchUserProfile = async () => {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('/api/user', { headers: { Authorization: `Bearer ${token}` } })
    userProfile.value = res.data
    // Post-login: show in accept mode if terms not yet accepted
    if (!res.data.terms_accepted_at) {
      termsModalMode.value = 'accept'
      termsModalSection.value = 'terms'
      showTermsModal.value = true
    }
  } catch {
    console.error('Failed to fetch user profile')
  }
}

// ── CHATS & HISTORY TOGGLE ──
const { chats, fetchConversations, addConversation, removeConversation, updateConversation } = useChats()
const isChatHistoryExpanded = ref(true)

const toggleChatHistory = () => {
  isChatHistoryExpanded.value = !isChatHistoryExpanded.value
}

onMounted(() => {
  fetchConversations()
  fetchUserProfile()
  window.addEventListener('resize', handleResize)
  window.addEventListener('click', closeDropdown)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('click', closeDropdown)
})

// ── SAVED ──
const savedChats = computed(() => chats.value.filter(c => c.is_saved))
const showSavedModal = ref(false)
const openSavedModal = () => { showSavedModal.value = true }
const closeSavedModal = () => { showSavedModal.value = false }

// ── SEARCH ──
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

// ── LOGOUT MODAL ──
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
    actionCallback: async () => {
      try {
        const token = localStorage.getItem('token')
        await axios.post('/api/logout', {}, { headers: { Authorization: `Bearer ${token}` } })
      } catch (e) { console.error('Logout API error:', e) }
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
  })
}

// ── ARCHIVED ──
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
        addConversation({ ...chat, is_archived: false })
        toast.success('Chat restored!')
      } catch (error) { handleConversationError(error, 'Failed to restore chat') }
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
        await axios.delete(`/api/conversations/${chat.id}`, { data: {}, headers: { Authorization: `Bearer ${token}` } })
        archivedChats.value.splice(index, 1)
        removeConversation(chat.id)
        if (route.query.conversation_id == chat.id) {
          router.push('/ChatConvo')
        }
        toast.success('Archived chat deleted!')
      } catch (error) { handleConversationError(error, 'Failed to delete archived chat') }
    }
  })
}

// ── DROPDOWN ──
const dropdown = ref({ visible: false, top: 0, left: 0, index: null })
const openDropdown = (event, index) => {
  event.stopPropagation()
  const rect = event.target.getBoundingClientRect()
  dropdown.value = { visible: true, top: rect.bottom + window.scrollY, left: rect.left + window.scrollX, index }
}
const closeDropdown = () => { dropdown.value.visible = false }

// ── CHAT ACTIONS ──
const createNewChat = async () => {
  try {
    const res = await axios.post('/api/conversations', {})
    addConversation(res.data)
    emit('select-chat', res.data.id)
    if (isMobile.value) mobileOpen.value = false
    router.currentRoute.value.path !== '/ChatConvo'
      ? router.push({ path: '/ChatConvo', query: { conversation_id: res.data.id } })
      : router.push({ query: { conversation_id: res.data.id } })
  } catch (error) { handleConversationError(error, 'Failed to create new chat') }
}

const selectChat = (id) => {
  emit('select-chat', id)
  if (isMobile.value) mobileOpen.value = false
  router.currentRoute.value.path !== '/ChatConvo'
    ? router.push({ path: '/ChatConvo', query: { conversation_id: id } })
    : router.push({ query: { conversation_id: id } })
}

const isSelected = (id) => route.query.conversation_id == id

// ── CONFIRM MODAL ──
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
      } catch (error) { handleConversationError(error, 'Failed to archive chat') }
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
        await axios.delete(`/api/conversations/${id}`, { data: {} })
        removeConversation(id); 
        closeDropdown();
        if (route.query.conversation_id == id) {
          router.push('/ChatConvo')
        }
        toast.success('Chat deleted successfully!')
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

<style scoped src="../assets/header-sidebar/sidebar.css"></style>
