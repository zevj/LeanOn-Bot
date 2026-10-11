<template>
  <div class="layout">
    <SidebarAdmin
      :open="sidebarOpen"
      @toggle="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)"
    />

    <main class="main-area">
      <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)" />

      <div class="main-container">
        <div class="page-header-wrapper">
          <div class="header-title">
            <h1 class="title">Direct Messages</h1>
            <p class="subtext">Communicate directly with students in real time.</p>
          </div>
        </div>

        <div class="messages-layout-card">
          <!-- Left Pane: Conversations & Student Search -->
          <div class="left-pane">
            <div class="search-box">
              <i class="bx bx-search search-icon"></i>
              <input
                v-model="searchInput"
                type="text"
                placeholder="Search student by name or email..."
                @input="onSearchInput"
                @focus="onSearchFocus"
              />
              <button v-if="searchInput || isSearchFocused" class="clear-btn" @click="clearSearch">
                <i class="bx bx-x"></i>
              </button>
            </div>

            <!-- Search Dropdown Results -->
            <div v-if="searchInput.trim() || isSearchFocused" class="search-results-list">
              <div v-if="searchingStudents" class="pane-notice">
                <div class="mini-spinner"></div> Searching students...
              </div>
              <template v-else>
                <div
                  v-for="st in searchResults"
                  :key="st.id"
                  class="search-item"
                  @click="onSelectStudent(st.id)"
                >
                  <div class="avatar">
                    {{ getInitials(st.full_name) }}
                  </div>
                  <div class="info">
                    <strong>{{ st.full_name }}</strong>
                    <span>{{ st.email }} · {{ st.department || 'Student' }}</span>
                  </div>
                  <i class="bx bx-chat action-icon"></i>
                </div>
                <div v-if="searchResults.length === 0" class="pane-notice">
                  No matching students found
                </div>
              </template>
            </div>

            <!-- Conversations List -->
            <div v-else class="convo-list">
              <div v-if="loadingConversations && conversations.length === 0" class="pane-notice">
                <div class="mini-spinner"></div> Loading conversations...
              </div>

              <template v-else-if="conversations.length > 0">
                <div
                  v-for="convo in conversations"
                  :key="convo.id"
                  class="convo-card"
                  :class="{
                    'is-active': activeConversation && activeConversation.id === convo.id,
                    'is-unread': convo.unread_count > 0
                  }"
                  @click="selectConversation(convo)"
                >
                  <div class="avatar">
                    {{ getInitials(convo.other_participant?.full_name) }}
                  </div>
                  <div class="card-body">
                    <div class="card-top">
                      <strong class="name">{{ convo.other_participant?.full_name || 'Student' }}</strong>
                      <span class="time">{{ formatTime(convo.last_message_at || convo.created_at) }}</span>
                    </div>
                    <div class="card-bottom">
                      <span class="snippet">{{ convo.last_message || 'Start a conversation...' }}</span>
                      <span v-if="convo.unread_count > 0" class="badge">{{ convo.unread_count }}</span>
                    </div>
                  </div>
                </div>
              </template>

              <div v-else class="pane-notice empty">
                <i class="bx bx-message-rounded-dots"></i>
                <p>No conversations found.<br/>Search for a student above to initiate a chat.</p>
              </div>
            </div>
          </div>

          <!-- Right Pane: Chat Thread -->
          <div class="right-pane">
            <template v-if="activeConversation">
              <!-- Chat Header -->
              <div class="chat-header">
                <div class="user-meta">
                  <div class="avatar">
                    {{ getInitials(activeConversation.other_participant?.full_name) }}
                  </div>
                  <div>
                    <h3 class="name">{{ activeConversation.other_participant?.full_name }}</h3>
                    <p class="sub">{{ activeConversation.other_participant?.email }} · {{ activeConversation.other_participant?.department || 'Student' }}</p>
                  </div>
                </div>
              </div>

              <!-- Message Stream -->
              <div ref="chatContainerRef" class="chat-stream">
                <div v-if="loadingMessages" class="pane-notice">
                  <div class="mini-spinner"></div> Loading messages...
                </div>

                <template v-else>
                  <div v-if="messages.length === 0" class="empty-chat">
                    <i class="bx bx-chat"></i>
                    <p>Send a message to begin your conversation with {{ activeConversation.other_participant?.full_name }}.</p>
                  </div>

                  <div
                    v-for="msg in messages"
                    :key="msg.id"
                    class="msg-row"
                    :class="isMyMessage(msg) ? 'me' : 'them'"
                  >
                    <div class="bubble">
                      <p class="text">{{ msg.message }}</p>
                      <span class="meta">
                        {{ formatTime(msg.created_at) }}
                        <i v-if="isMyMessage(msg)" class="bx" :class="msg.is_read ? 'bx-check-double read' : 'bx-check'"></i>
                      </span>
                    </div>
                  </div>
                </template>
              </div>

              <!-- Message Input -->
              <form class="composer" @submit.prevent="handleSend">
                <input
                  v-model="msgText"
                  type="text"
                  placeholder="Type a message..."
                  :disabled="sendingMessage"
                />
                <button type="submit" :disabled="!msgText.trim() || sendingMessage">
                  <i class="bx bx-send" :class="{ spinning: sendingMessage }"></i>
                </button>
              </form>
            </template>

            <!-- No Active Chat Selected -->
            <div v-else class="no-chat-selected">
              <i class="bx bx-chat icon"></i>
              <h3>Select a Conversation</h3>
              <p>Choose a student conversation from the left panel or search for a student to begin messaging.</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import SidebarAdmin from '@/components/sidebarAdmin.vue'
import HeaderAdmin from '@/components/headerAdmin.vue'
import { useDirectMessages } from '@/composables/useDirectMessages'

const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false')

const {
  conversations,
  activeConversation,
  messages,
  loadingConversations,
  loadingMessages,
  sendingMessage,
  searchResults,
  searchingStudents,
  fetchConversations,
  selectConversation,
  sendMessage,
  startConversationWithStudent,
  searchStudents,
} = useDirectMessages()

const searchInput = ref('')
const msgText = ref('')
const chatContainerRef = ref(null)

const isSearchFocused = ref(false)

const currentUser = computed(() => {
  try {
    const raw = localStorage.getItem('user')
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
})

let searchTimer = null
const onSearchInput = () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    searchStudents(searchInput.value)
  }, 300)
}

const onSearchFocus = () => {
  isSearchFocused.value = true
  if (searchResults.value.length === 0) {
    searchStudents(searchInput.value)
  }
}

const clearSearch = () => {
  searchInput.value = ''
  isSearchFocused.value = false
  searchResults.value = []
}

const onSelectStudent = async (studentId) => {
  clearSearch()
  await startConversationWithStudent(studentId)
}

const isMyMessage = (msg) => {
  return currentUser.value && (parseInt(msg.sender_id, 10) === parseInt(currentUser.value.id, 10))
}

const handleSend = async () => {
  if (!msgText.value.trim() || sendingMessage.value) return
  const text = msgText.value
  msgText.value = ''
  await sendMessage(text)
  scrollToBottom()
}

const getInitials = (name) => {
  if (!name) return '?'
  const parts = name.trim().split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}

const formatTime = (iso) => {
  if (!iso) return ''
  const d = new Date(iso)
  if (isNaN(d)) return ''
  return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const scrollToBottom = () => {
  nextTick(() => {
    if (chatContainerRef.value) {
      chatContainerRef.value.scrollTop = chatContainerRef.value.scrollHeight
    }
  })
}

watch(messages, () => {
  scrollToBottom()
}, { deep: true })

onMounted(() => {
  fetchConversations()
})
</script>

<style scoped>
.messages-layout-card {
  display: flex;
  height: calc(100vh - 170px);
  min-height: 520px;
  background: var(--surface-color, #ffffff);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px var(--shadow-color, rgba(0, 0, 0, 0.05));
  transition: background-color 0.2s, border-color 0.2s;
}

/* Left Pane */
.left-pane {
  width: 340px;
  border-right: 1px solid var(--border-color, #e5e7eb);
  display: flex;
  flex-direction: column;
  background: var(--surface-color, #ffffff);
}

.search-box {
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-color, #f3f4f6);
  position: relative;
  display: flex;
  align-items: center;
  background: var(--surface-color, #ffffff);
}

.search-icon {
  position: absolute;
  left: 28px;
  color: var(--text-secondary, #9ca3af);
  font-size: 18px;
}

.search-box input {
  width: 100%;
  height: 40px;
  padding: 0 32px 0 36px;
  border-radius: 10px;
  border: 1px solid var(--border-color, #e5e7eb);
  background: var(--bg-secondary, #f9fafb);
  color: var(--text-primary, #111827);
  font-size: 13.5px;
  outline: none;
  transition: border-color 0.15s, background 0.15s;
}

.search-box input:focus {
  border-color: var(--primary-color, #16a34a);
  background: var(--surface-color, #ffffff);
}

.clear-btn {
  position: absolute;
  right: 24px;
  background: none;
  border: none;
  color: var(--text-secondary, #9ca3af);
  font-size: 18px;
  cursor: pointer;
}

.convo-list,
.search-results-list {
  flex: 1;
  overflow-y: auto;
  background: var(--surface-color, #ffffff);
}

.search-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color, #f3f4f6);
  cursor: pointer;
  transition: background 0.15s;
}

.search-item:hover {
  background: var(--surface-hover, #f0fdf4);
}

.search-item .info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.search-item .info strong {
  font-size: 13.5px;
  color: var(--text-primary, #111827);
}

.search-item .info span {
  font-size: 12px;
  color: var(--text-secondary, #6b7280);
}

.search-item .action-icon {
  font-size: 20px;
  color: var(--primary-color, #16a34a);
}

.convo-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-color, #f3f4f6);
  cursor: pointer;
  transition: background 0.15s;
}

.convo-card:hover {
  background: var(--surface-hover, #f9fafb);
}

.convo-card.is-active {
  background: var(--surface-hover, #f0fdf4);
  border-left: 4px solid var(--primary-color, #16a34a);
}

.avatar {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color, #0e6008) 0%, var(--secondary-color, #16a34a) 100%);
  color: #ffffff;
  font-weight: 700;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.card-body {
  flex: 1;
  min-width: 0;
}

.card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.card-top .name {
  font-size: 13.5px;
  color: var(--text-primary, #111827);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-top .time {
  font-size: 11px;
  color: var(--text-secondary, #9ca3af);
}

.card-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.card-bottom .snippet {
  font-size: 12.5px;
  color: var(--text-secondary, #6b7280);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}

.card-bottom .badge {
  background: #ef4444;
  color: #ffffff;
  font-size: 11px;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 10px;
}

/* Right Pane */
.right-pane {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: var(--bg-secondary, #f9fafb);
}

.chat-header {
  padding: 16px 20px;
  background: var(--surface-color, #ffffff);
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.chat-header .user-meta {
  display: flex;
  align-items: center;
  gap: 12px;
}

.chat-header .user-meta .name {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary, #111827);
}

.chat-header .user-meta .sub {
  margin: 2px 0 0;
  font-size: 12px;
  color: var(--text-secondary, #6b7280);
}

.chat-stream {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: var(--bg-color, #f9fafb);
}

.msg-row {
  display: flex;
}

.msg-row.me {
  justify-content: flex-end;
}

.msg-row.me .bubble {
  background: linear-gradient(135deg, var(--primary-color, #0e6008), var(--secondary-color, #16a34a));
  color: #ffffff;
  border-bottom-right-radius: 4px;
}

.msg-row.me .bubble .text {
  color: #ffffff;
}

.msg-row.me .bubble .meta {
  color: rgba(255, 255, 255, 0.85);
}

.msg-row.them {
  justify-content: flex-start;
}

.msg-row.them .bubble {
  background: var(--surface-color, #ffffff);
  color: var(--text-primary, #111827);
  border: 1px solid var(--border-color, #e5e7eb);
  border-bottom-left-radius: 4px;
}

.msg-row.them .bubble .text {
  color: var(--text-primary, #111827);
}

.msg-row.them .bubble .meta {
  color: var(--text-secondary, #9ca3af);
}

.bubble {
  max-width: 75%;
  padding: 10px 14px;
  border-radius: 16px;
  box-shadow: 0 1px 2px var(--shadow-color, rgba(0, 0, 0, 0.04));
}

.bubble .text {
  margin: 0;
  font-size: 13.5px;
  line-height: 1.45;
  font-weight: 400 !important;
}

.bubble .meta {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
  font-size: 10px;
  margin-top: 4px;
}

.meta .read {
  color: #60a5fa;
}

.composer {
  padding: 14px 20px;
  background: var(--surface-color, #ffffff);
  border-top: 1px solid var(--border-color, #e5e7eb);
  display: flex;
  gap: 12px;
}

.composer input {
  flex: 1;
  height: 42px;
  padding: 0 16px;
  border-radius: 21px;
  border: 1px solid var(--border-color, #d1d5db);
  background: var(--bg-secondary, #f9fafb);
  color: var(--text-primary, #111827);
  outline: none;
  font-size: 13.5px;
  transition: border-color 0.2s;
}

.composer input:focus {
  border-color: var(--primary-color, #16a34a);
}

.composer button {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color, #0e6008), var(--secondary-color, #16a34a));
  color: #ffffff;
  border: none;
  cursor: pointer;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.15s, opacity 0.15s;
}

.composer button:hover:not(:disabled) {
  transform: scale(1.05);
}

.composer button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.no-chat-selected,
.empty-chat,
.pane-notice {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  text-align: center;
  color: var(--text-secondary, #6b7280);
}

.no-chat-selected .icon,
.empty-chat i,
.pane-notice.empty i {
  font-size: 48px;
  color: var(--primary-color, #16a34a);
  margin-bottom: 12px;
}

.no-chat-selected h3 {
  font-size: 1.2rem;
  color: var(--text-primary, #111827);
  margin-bottom: 0.4rem;
}

.mini-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid var(--border-color, #e5e7eb);
  border-top-color: var(--primary-color, #16a34a);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: inline-block;
  margin-right: 6px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Explicit Dark Mode Overrides for Admin Layout */
:global([data-theme="dark"]) .messages-layout-card {
  background: #161b27;
  border-color: #2d3748;
}

:global([data-theme="dark"]) .left-pane,
:global([data-theme="dark"]) .search-box,
:global([data-theme="dark"]) .convo-list,
:global([data-theme="dark"]) .search-results-list,
:global([data-theme="dark"]) .chat-header,
:global([data-theme="dark"]) .composer {
  background: #161b27;
  border-color: #2d3748;
}

:global([data-theme="dark"]) .search-box input,
:global([data-theme="dark"]) .composer input {
  background: #1e2533;
  border-color: #374151;
  color: #f3f4f6;
}

:global([data-theme="dark"]) .search-box input:focus,
:global([data-theme="dark"]) .composer input:focus {
  border-color: #4ade80;
  background: #1e2533;
}

:global([data-theme="dark"]) .right-pane,
:global([data-theme="dark"]) .chat-stream {
  background: #0f1117;
}

:global([data-theme="dark"]) .convo-card,
:global([data-theme="dark"]) .search-item {
  border-bottom-color: #2d3748;
}

:global([data-theme="dark"]) .convo-card:hover,
:global([data-theme="dark"]) .search-item:hover {
  background: #1e2533;
}

:global([data-theme="dark"]) .convo-card.is-active {
  background: #1c2b21;
  border-left-color: #4ade80;
}

:global([data-theme="dark"]) .card-top .name,
:global([data-theme="dark"]) .chat-header .user-meta .name,
:global([data-theme="dark"]) .search-item .info strong,
:global([data-theme="dark"]) .no-chat-selected h3 {
  color: #f3f4f6;
}

:global([data-theme="dark"]) .card-top .time,
:global([data-theme="dark"]) .card-bottom .snippet,
:global([data-theme="dark"]) .search-item .info span,
:global([data-theme="dark"]) .chat-header .user-meta .sub,
:global([data-theme="dark"]) .no-chat-selected p,
:global([data-theme="dark"]) .empty-chat p,
:global([data-theme="dark"]) .pane-notice {
  color: #cbd5e1;
}

:global([data-theme="dark"]) .msg-row.them .bubble {
  background: #1e2533;
  color: #f3f4f6;
  border-color: #374151;
}

:global([data-theme="dark"]) .msg-row.them .bubble .text {
  color: #f3f4f6;
}

:global([data-theme="dark"]) .msg-row.them .bubble .meta {
  color: #9ca3af;
}
</style>
