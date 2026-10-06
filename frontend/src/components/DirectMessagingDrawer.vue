<template>
  <Teleport to="body">
    <div v-if="isOpen" class="messaging-drawer-backdrop" @click="closeDrawer"></div>

    <transition name="drawer-slide">
      <div v-if="isOpen" class="messaging-drawer-container">
        <!-- Header -->
        <div class="drawer-header">
          <div class="header-left">
            <button
              v-if="activeConversation"
              class="back-btn"
              @click="selectConversation(null)"
              title="Back to conversations"
            >
              <i class="bx bx-arrow-back"></i>
            </button>
            <div class="header-title-wrap">
              <h3 class="drawer-title">
                {{ activeConversation ? (activeConversation.other_participant?.full_name || 'Chat') : 'Direct Messages' }}
              </h3>
              <p v-if="activeConversation" class="drawer-subtitle">
                {{ activeConversation.other_participant?.department || activeConversation.other_participant?.email || 'Student' }}
              </p>
            </div>
          </div>

          <div class="header-actions">
            <button class="icon-action-btn" @click="fetchConversations" title="Refresh">
              <i class="bx bx-refresh" :class="{ spinning: loadingConversations }"></i>
            </button>
            <button class="icon-action-btn" @click="closeDrawer" title="Close">
              <i class="bx bx-x"></i>
            </button>
          </div>
        </div>

        <!-- Body Area -->
        <div class="drawer-body">
          <!-- CONVERSATION LIST & SEARCH -->
          <div v-if="!activeConversation" class="conversations-view">
            <!-- Student Search (Admin/Staff only) -->
            <div v-if="isStaff" class="search-box-wrap">
              <div class="search-input-box">
                <i class="bx bx-search search-icon"></i>
                <input
                  v-model="searchInput"
                  type="text"
                  placeholder="Search student by name or email..."
                  @input="onSearchInput"
                />
                <button v-if="searchInput" class="clear-search-btn" @click="clearSearch">
                  <i class="bx bx-x"></i>
                </button>
              </div>

              <!-- Search Results Dropdown -->
              <div v-if="searchInput.trim()" class="search-results-dropdown">
                <div v-if="searchingStudents" class="dropdown-info">
                  <div class="mini-spinner"></div> Searching students...
                </div>
                <template v-else>
                  <div
                    v-for="st in searchResults"
                    :key="st.id"
                    class="search-result-item"
                    @click="onSelectStudentSearchResult(st.id)"
                  >
                    <div class="avatar-circle small green">
                      {{ getInitials(st.full_name) }}
                    </div>
                    <div class="item-info">
                      <strong class="item-name">{{ st.full_name }}</strong>
                      <span class="item-sub">{{ st.email }} · {{ st.department || 'Student' }}</span>
                    </div>
                    <i class="bx bx-chat start-icon"></i>
                  </div>

                  <div v-if="searchResults.length === 0" class="dropdown-info">
                    No students found
                  </div>
                </template>
              </div>
            </div>

            <!-- List of Conversations -->
            <div v-if="loadingConversations && conversations.length === 0" class="loading-state">
              <div class="spinner"></div>
              <p>Loading conversations...</p>
            </div>

            <div v-else-if="conversations.length > 0" class="conversations-list">
              <div
                v-for="convo in conversations"
                :key="convo.id"
                class="convo-item"
                :class="{ 'has-unread': convo.unread_count > 0 }"
                @click="selectConversation(convo)"
              >
                <div class="avatar-circle">
                  {{ getInitials(convo.other_participant?.full_name) }}
                </div>
                <div class="convo-info">
                  <div class="convo-top">
                    <strong class="convo-name">{{ convo.other_participant?.full_name || 'User' }}</strong>
                    <span class="convo-time">{{ formatTime(convo.last_message_at || convo.created_at) }}</span>
                  </div>
                  <div class="convo-bottom">
                    <p class="convo-snippet">{{ convo.last_message || 'Start a conversation...' }}</p>
                    <span v-if="convo.unread_count > 0" class="unread-chip">
                      {{ convo.unread_count }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="empty-state">
              <i class="bx bx-message-rounded-x empty-icon"></i>
              <h4>No Messages Yet</h4>
              <p v-if="isStaff">Search for a student above to initiate a direct message.</p>
              <p v-else>Messages from guidance counselors will appear here.</p>
            </div>
          </div>

          <!-- ACTIVE CHAT STREAM -->
          <div v-else class="chat-thread-view">
            <!-- Messages Stream -->
            <div ref="chatContainerRef" class="chat-messages-container">
              <div v-if="loadingMessages" class="loading-state">
                <div class="spinner"></div>
                <p>Loading chat history...</p>
              </div>

              <template v-else>
                <div v-if="messages.length === 0" class="empty-chat-notice">
                  <i class="bx bx-chat"></i>
                  <p>Send a message to start the conversation with {{ activeConversation.other_participant?.full_name }}.</p>
                </div>

                <div
                  v-for="msg in messages"
                  :key="msg.id"
                  class="message-wrapper"
                  :class="isMyMessage(msg) ? 'message-outgoing' : 'message-incoming'"
                >
                  <div class="message-bubble">
                    <p class="message-text">{{ msg.message }}</p>
                    <span class="message-timestamp">
                      {{ formatTime(msg.created_at) }}
                      <i v-if="isMyMessage(msg)" class="bx" :class="msg.is_read ? 'bx-check-double read' : 'bx-check'"></i>
                    </span>
                  </div>
                </div>
              </template>
            </div>

            <!-- Message Input Composer -->
            <form class="chat-composer" @submit.prevent="handleSendMessage">
              <input
                v-model="newMessageText"
                type="text"
                class="composer-input"
                placeholder="Type your message..."
                :disabled="sendingMessage"
                @keydown.enter.exact.prevent="handleSendMessage"
              />
              <button
                type="submit"
                class="send-btn"
                :disabled="!newMessageText.trim() || sendingMessage"
              >
                <i class="bx bx-send" :class="{ spinning: sendingMessage }"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useDirectMessages } from '@/composables/useDirectMessages'

const {
  isOpen,
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
  closeDrawer,
} = useDirectMessages()

const searchInput = ref('')
const newMessageText = ref('')
const chatContainerRef = ref(null)

const currentUser = computed(() => {
  try {
    const raw = localStorage.getItem('user')
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
})

const isStaff = computed(() => {
  return currentUser.value?.role === 'guidance' || currentUser.value?.role === 'admin'
})

let searchTimer = null
const onSearchInput = () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    searchStudents(searchInput.value)
  }, 300)
}

const clearSearch = () => {
  searchInput.value = ''
  searchResults.value = []
}

const onSelectStudentSearchResult = async (studentId) => {
  clearSearch()
  await startConversationWithStudent(studentId)
}

const isMyMessage = (msg) => {
  return currentUser.value && (intEquals(msg.sender_id, currentUser.value.id))
}

const intEquals = (a, b) => parseInt(a, 10) === parseInt(b, 10)

const handleSendMessage = async () => {
  if (!newMessageText.value.trim() || sendingMessage.value) return
  const text = newMessageText.value
  newMessageText.value = ''
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
</script>

<style scoped>
.messaging-drawer-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(2px);
  z-index: 9991;
}

.messaging-drawer-container {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: 420px;
  max-width: 100vw;
  background: #ffffff;
  z-index: 9992;
  display: flex;
  flex-direction: column;
  box-shadow: -8px 0 32px rgba(0, 0, 0, 0.15);
}

/* Header */
.drawer-header {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #ffffff;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.back-btn {
  background: #f1f5f9;
  border: none;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #475569;
  font-size: 18px;
  transition: background 0.15s;
}

.back-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.drawer-title {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.drawer-subtitle {
  margin: 2px 0 0;
  font-size: 12px;
  color: #64748b;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.icon-action-btn {
  background: transparent;
  border: none;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: #64748b;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}

.icon-action-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.spinning {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Body */
.drawer-body {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.conversations-view,
.chat-thread-view {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Search */
.search-box-wrap {
  padding: 12px 16px;
  border-bottom: 1px solid #f1f5f9;
  position: relative;
}

.search-input-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 12px;
  color: #94a3b8;
  font-size: 18px;
}

.search-input-box input {
  width: 100%;
  height: 40px;
  padding: 0 36px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  font-size: 13.5px;
  outline: none;
  transition: border-color 0.15s, background 0.15s;
}

.search-input-box input:focus {
  border-color: #16a34a;
  background: #ffffff;
}

.clear-search-btn {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 18px;
  cursor: pointer;
}

.search-results-dropdown {
  position: absolute;
  top: 100%;
  left: 16px;
  right: 16px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
  z-index: 10;
  max-height: 240px;
  overflow-y: auto;
}

.search-result-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s;
}

.search-result-item:hover {
  background: #f0fdf4;
}

.dropdown-info {
  padding: 14px;
  text-align: center;
  font-size: 13px;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* Conversations List */
.conversations-list {
  flex: 1;
  overflow-y: auto;
}

.convo-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background 0.15s;
}

.convo-item:hover {
  background: #f8fafc;
}

.convo-item.has-unread {
  background: #f0fdf4;
}

.avatar-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0e6008 0%, #16a34a 100%);
  color: #ffffff;
  font-weight: 700;
  font-size: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.avatar-circle.small {
  width: 34px;
  height: 34px;
  font-size: 13px;
}

.convo-info {
  flex: 1;
  min-width: 0;
}

.convo-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.convo-name {
  font-size: 14px;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.convo-time {
  font-size: 11px;
  color: #94a3b8;
}

.convo-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.convo-snippet {
  margin: 0;
  font-size: 12.5px;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}

.unread-chip {
  background: #ef4444;
  color: #ffffff;
  font-size: 11px;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 10px;
}

/* Chat Thread */
.chat-messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: #f8fafc;
}

.message-wrapper {
  display: flex;
  margin-bottom: 2px;
}

.message-outgoing {
  justify-content: flex-end;
}

.message-incoming {
  justify-content: flex-start;
}

.message-bubble {
  max-width: 80%;
  padding: 10px 14px;
  border-radius: 16px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);

  .message-text {
    margin: 0;
    font-size: 13.5px;
    line-height: 1.45;
    word-break: break-word;
  }

  .message-timestamp {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    font-size: 10px;
    margin-top: 4px;
    opacity: 0.75;
  }
}

.message-outgoing .message-bubble {
  background: #16a34a;
  color: #ffffff;
  border-bottom-right-radius: 4px;
}

.message-incoming .message-bubble {
  background: #ffffff;
  color: #0f172a;
  border: 1px solid #e2e8f0;
  border-bottom-left-radius: 4px;
}

.chat-composer {
  padding: 12px 16px;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.composer-input {
  flex: 1;
  height: 42px;
  padding: 0 14px;
  border-radius: 21px;
  border: 1px solid #cbd5e1;
  background: #f8fafc;
  font-size: 13.5px;
  outline: none;
  transition: border-color 0.15s;
}

.composer-input:focus {
  border-color: #16a34a;
  background: #ffffff;
}

.send-btn {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: #16a34a;
  color: #ffffff;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  cursor: pointer;
  transition: background 0.15s, transform 0.15s;
}

.send-btn:hover:not(:disabled) {
  background: #15803d;
  transform: scale(1.05);
}

.send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Empty States & Loading */
.loading-state,
.empty-state,
.empty-chat-notice {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
  color: #64748b;
}

.empty-icon {
  font-size: 48px;
  color: #cbd5e1;
  margin-bottom: 8px;
}

.mini-spinner,
.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #e2e8f0;
  border-top-color: #16a34a;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* Slide Transition */
.drawer-slide-enter-active,
.drawer-slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.drawer-slide-enter-from,
.drawer-slide-leave-to {
  transform: translateX(100%);
}

/* Dark Mode Overrides */
[data-theme="dark"] .messaging-drawer-container {
  background: #0f172a;
  color: #f8fafc;
}

[data-theme="dark"] .drawer-header,
[data-theme="dark"] .chat-composer {
  background: #0f172a;
  border-color: #1e293b;
}

[data-theme="dark"] .drawer-title { color: #f8fafc; }
[data-theme="dark"] .search-box-wrap { border-color: #1e293b; }
[data-theme="dark"] .search-input-box input,
[data-theme="dark"] .composer-input {
  background: #1e293b;
  border-color: #334155;
  color: #f8fafc;
}

[data-theme="dark"] .convo-item {
  border-color: #1e293b;
}

[data-theme="dark"] .convo-item:hover {
  background: #1e293b;
}

[data-theme="dark"] .convo-item.has-unread {
  background: #14291f;
}

[data-theme="dark"] .convo-name { color: #f8fafc; }
[data-theme="dark"] .chat-messages-container { background: #0b0f19; }
[data-theme="dark"] .message-incoming .message-bubble {
  background: #1e293b;
  color: #f8fafc;
  border-color: #334155;
}
</style>
