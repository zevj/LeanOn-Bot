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

        <div class="messages-layout-card" :class="{ 'mobile-chat-open': mobileChatVisible }">
          <!-- Left Pane: Conversations & Student Search -->
          <div class="left-pane">
            <div class="search-box">
              <i class="bx bx-search search-icon"></i>
              <input
                v-model="searchInput"
                type="text"
                placeholder="Search students..."
                @input="onSearchInput"
                @focus="onSearchFocus"
              />
              <button v-if="searchInput || isSearchFocused" class="clear-btn" @click="clearSearch">
                <i class="bx bx-x"></i>
              </button>
            </div>

            <!-- Search Dropdown Results -->
            <div v-if="searchInput.trim() || isSearchFocused" class="search-results-list">
              <!-- Search Loader -->
              <div v-if="searchingStudents" class="modern-loader-container">
                <div class="bouncing-dots"><span></span><span></span><span></span></div>
                <p>Finding students...</p>
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
                    <span>{{ st.email }} &bull; {{ st.department || 'Student' }}</span>
                  </div>
                  <div class="action-btn"><i class="bx bx-message-square-edit"></i></div>
                </div>
                <div v-if="searchResults.length === 0" class="pane-notice empty">
                  <div class="empty-icon-wrap"><i class="bx bx-user-x"></i></div>
                  <p>No students found</p>
                </div>
              </template>
            </div>

            <!-- Conversations List -->
            <div v-else class="convo-list">
              <!-- Conversation Skeleton Loader -->
              <div v-if="loadingConversations && conversations.length === 0" class="skeleton-list">
                <div class="skeleton-convo" v-for="n in 5" :key="n">
                  <div class="skeleton-avatar shimmer"></div>
                  <div class="skeleton-lines">
                    <div class="skeleton-line long shimmer"></div>
                    <div class="skeleton-line short shimmer"></div>
                  </div>
                </div>
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
                  <div class="avatar-wrapper">
                    <div class="avatar">{{ getInitials(convo.other_participant?.full_name) }}</div>
                    <div v-if="convo.unread_count > 0" class="online-indicator active"></div>
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
                <div class="empty-icon-wrap"><i class="bx bx-message-square-detail"></i></div>
                <p>Your inbox is empty.<br/>Search above to start chatting.</p>
              </div>
            </div>
          </div>

          <!-- Right Pane: Chat Thread -->
          <div class="right-pane">
            <template v-if="activeConversation">
              <!-- Chat Header -->
              <div class="chat-header">
                <button class="mobile-back-btn" @click="mobileChatVisible = false">
                  <i class="bx bx-chevron-left"></i>
                </button>
                <div class="user-meta">
                  <div class="avatar">{{ getInitials(activeConversation.other_participant?.full_name) }}</div>
                  <div class="header-info">
                    <h3 class="name">{{ activeConversation.other_participant?.full_name }}</h3>
                    <p class="sub">{{ activeConversation.other_participant?.email }} &bull; {{ activeConversation.other_participant?.department || 'Student' }}</p>
                  </div>
                </div>
              </div>

              <!-- Message Stream -->
              <div ref="chatContainerRef" class="chat-stream" @click="closeActions">
                <!-- Messages Skeleton / Typing Loader -->
                <div v-if="loadingMessages" class="chat-loader">
                  <div class="typing-bubble">
                    <div class="typing-dots"><span></span><span></span><span></span></div>
                  </div>
                  <span class="loader-text">Loading history...</span>
                </div>

                <template v-else>
                  <div v-if="messages.length === 0" class="empty-chat">
                    <img src="https://cdn-icons-png.flaticon.com/512/1041/1041916.png" alt="Say Hi" class="empty-illustration"/>
                    <h3>Say Hello!</h3>
                    <p>Start the conversation with {{ activeConversation.other_participant?.full_name }}.</p>
                  </div>

                  <div class="message-wrapper">
                    <div
                      v-for="msg in messages"
                      :key="msg.id"
                      class="msg-row slide-up"
                      :class="[
                        isMyMessage(msg) ? 'me' : 'them',
                        { 'show-actions': activeActionMsgId === msg.id }
                      ]"
                    >

                      <!-- Action for 'me' messages -->
                      <div class="msg-actions" v-if="isMyMessage(msg)">
                        <button class="reply-icon-btn" @click.stop="startReply(msg)" title="Reply to message">
                          <i class="bx bx-reply"></i>
                        </button>
                      </div>
                      
                      <div 
                        class="bubble"
                        @touchstart="startPress(msg.id)"
                        @touchend="cancelPress"
                        @touchmove="cancelPress"
                        @touchcancel="cancelPress"
                        @mousedown="startPress(msg.id)"
                        @mouseup="cancelPress"
                        @mouseleave="cancelPress"
                      >
                        <!-- Quoted Reply Render -->
                        <div v-if="extractReply(msg.message).quoted" class="bubble-quote">
                          <i class="bx bx-reply"></i>
                          <span>{{ extractReply(msg.message).quoted }}</span>
                        </div>
                        
                        <p class="text">{{ extractReply(msg.message).content }}</p>
                        <span class="meta">
                          {{ formatTime(msg.created_at) }}
                          <i v-if="isMyMessage(msg)" class="bx" :class="msg.is_read ? 'bx-check-double read' : 'bx-check'"></i>
                        </span>
                      </div>

                      <!-- Action for 'them' messages -->
                      <div class="msg-actions" v-if="!isMyMessage(msg)">
                        <button class="reply-icon-btn" @click.stop="startReply(msg)" title="Reply to message">
                          <i class="bx bx-reply"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
              </div>

              <!-- Message Input -->
              <div class="composer-container">
                <!-- Reply Preview Box -->
                <div v-if="replyingTo" class="reply-preview slide-up">
                  <div class="reply-preview-content">
                    <span class="reply-label"><i class="bx bx-reply"></i> Replying to {{ isMyMessage(replyingTo) ? 'yourself' : activeConversation.other_participant?.full_name }}</span>
                    <p class="reply-text">{{ extractReply(replyingTo.message).content }}</p>
                  </div>
                  <button class="cancel-reply-btn" @click="cancelReply"><i class="bx bx-x"></i></button>
                </div>

                <form class="composer" :class="{ 'has-reply': replyingTo }" @submit.prevent="handleSend">
                  <input
                    ref="msgInputRef"
                    v-model="msgText"
                    type="text"
                    placeholder="Write a message..."
                    :disabled="sendingMessage"
                  />
                  <button type="submit" class="send-btn" :disabled="!msgText.trim() || sendingMessage">
                    <i class="bx" :class="sendingMessage ? 'bx-loader-alt bx-spin' : 'bx-send'"></i>
                  </button>
                </form>
              </div>
            </template>

            <!-- No Active Chat Selected -->
            <div v-else class="no-chat-selected">
              <div class="glass-icon"><i class="bx bx-chat"></i></div>
              <h3>Your Messages</h3>
              <p>Select a student from the left panel or start a new search to dive in.</p>
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
  selectConversation: rawSelectConversation,
  sendMessage,
  startConversationWithStudent,
  searchStudents,
} = useDirectMessages()

const searchInput = ref('')
const msgText = ref('')
const chatContainerRef = ref(null)
const msgInputRef = ref(null)
const replyingTo = ref(null)

const isSearchFocused = ref(false)
const mobileChatVisible = ref(false)

// Variables for hold-to-reply functionality
const activeActionMsgId = ref(null)
let pressTimer = null

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

const selectConversation = (convo) => {
  rawSelectConversation(convo)
  mobileChatVisible.value = true
}

const onSelectStudent = async (studentId) => {
  clearSearch()
  await startConversationWithStudent(studentId)
  mobileChatVisible.value = true
}

const isMyMessage = (msg) => {
  return currentUser.value && (parseInt(msg.sender_id, 10) === parseInt(currentUser.value.id, 10))
}

/* Long press / Hold functionality logic */
const startPress = (msgId) => {
  // Clear any existing timer
  if (pressTimer) clearTimeout(pressTimer)
  
  pressTimer = setTimeout(() => {
    // Toggle the reply button for this specific message
    activeActionMsgId.value = activeActionMsgId.value === msgId ? null : msgId
    
    // Haptic feedback (vibration) for mobile devices if supported
    if (typeof window !== 'undefined' && window.navigator && window.navigator.vibrate) {
      window.navigator.vibrate(50)
    }
  }, 500) // 500ms required for long press
}

const cancelPress = () => {
  if (pressTimer) {
    clearTimeout(pressTimer)
    pressTimer = null
  }
}

const closeActions = (e) => {
  // Close actions if clicking outside the message bubbles
  if (!e.target.closest('.msg-row')) {
    activeActionMsgId.value = null
  }
}

/* Reply functionality helpers */
const startReply = (msg) => {
  replyingTo.value = msg
  activeActionMsgId.value = null // Close the action button after clicking reply
  nextTick(() => {
    msgInputRef.value?.focus()
  })
}

const cancelReply = () => {
  replyingTo.value = null
}

const extractReply = (text) => {
  if (!text) return { quoted: null, content: '' }
  const match = text.match(/^\[REPLY:"([\s\S]*?)"\]\n([\s\S]*)$/)
  if (match) {
    return { quoted: match[1], content: match[2] }
  }
  return { quoted: null, content: text }
}

const handleSend = async () => {
  if (!msgText.value.trim() || sendingMessage.value) return
  
  let finalMessageText = msgText.value
  
  if (replyingTo.value) {
    const originalContent = extractReply(replyingTo.value.message).content
    finalMessageText = `[REPLY:"${originalContent}"]\n${finalMessageText}`
    replyingTo.value = null 
  }

  msgText.value = ''
  await sendMessage(finalMessageText)
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

watch(activeConversation, (newVal) => {
  if (newVal) mobileChatVisible.value = true
})

onMounted(() => {
  fetchConversations()
})
</script>

<style scoped>
/* ── Layout ── */
.layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: var(--page-bg, #f7f8fa);
  font-family: 'DM Sans', system-ui, sans-serif;
}

.main-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
}

.main-container {
  flex: 1;
  overflow-y: auto;
  padding: 2rem 2.5rem 3rem;
  scroll-behavior: smooth;
}

/* Main Layout Enhancements */
.messages-layout-card {
  display: flex;
  height: calc(100vh - 170px);
  width: 96%;
  min-height: 520px;
  margin-top: 20px;
  background: var(--surface-color, #ffffff);
  border: 1px solid var(--border-color, #cbd5e1); 
  border-radius: 20px; 
  overflow: hidden;
  box-shadow: 0 10px 40px -10px var(--shadow-color, rgba(0, 0, 0, 0.08));
  transition: all 0.3s ease;
  position: relative;
}

/* Left Pane (Conversations) */
.left-pane {
  width: 360px;
  border-right: 1px solid var(--border-color, #cbd5e1);
  display: flex;
  flex-direction: column;
  background: var(--surface-color, #ffffff);
  z-index: 10;
  flex-shrink: 0;
}

.search-box {
  padding: 20px;
  border-bottom: 1px solid var(--border-color, #f0f2f5);
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 36px;
  color: var(--text-secondary, #9ca3af);
  font-size: 20px;
}

.search-box input {
  width: 100%;
  height: 46px;
  padding: 0 36px 0 44px;
  border-radius: 24px;
  border: 1px solid transparent;
  background: var(--bg-secondary, #f3f4f6);
  color: var(--text-primary, #111827);
  font-size: 14px;
  font-weight: 500;
  outline: none;
  transition: all 0.3s ease;
}

.search-box input:focus {
  background: var(--surface-color, #ffffff);
  border: 1px solid var(--primary-color, #16a34a);
  box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.1);
}

.clear-btn {
  position: absolute;
  right: 32px;
  background: #e5e7eb;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  color: var(--text-secondary, #4b5563);
  font-size: 16px;
  cursor: pointer;
  transition: background 0.2s;
}
.clear-btn:hover { background: #d1d5db; }

/* Custom Scrollbar */
.convo-list::-webkit-scrollbar,
.search-results-list::-webkit-scrollbar,
.chat-stream::-webkit-scrollbar {
  width: 6px;
}
.convo-list::-webkit-scrollbar-thumb,
.search-results-list::-webkit-scrollbar-thumb,
.chat-stream::-webkit-scrollbar-thumb {
  background-color: var(--border-color, #d1d5db);
  border-radius: 10px;
}

.convo-list, .search-results-list {
  flex: 1;
  overflow-y: auto;
}

/* Converstation Items */
.convo-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  cursor: pointer;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.convo-card:hover {
  background: var(--surface-hover, #f8fafc);
}

.convo-card.is-active {
  background: var(--surface-hover, #f0fdf4);
  border-left: 3px solid var(--primary-color, #16a34a);
}

.avatar-wrapper {
  position: relative;
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  background: linear-gradient(135deg, var(--primary-color, #0e6008) 0%, var(--secondary-color, #22c55e) 100%);
  color: #ffffff;
  font-weight: 700;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(22, 163, 74, 0.2);
}

.online-indicator {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 14px;
  height: 14px;
  background: #ef4444;
  border: 2px solid #fff;
  border-radius: 50%;
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
  font-size: 14.5px;
  color: var(--text-primary, #111827);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-top .time {
  font-size: 11.5px;
  color: var(--text-secondary, #9ca3af);
  font-weight: 500;
}

.card-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.card-bottom .snippet {
  font-size: 13px;
  color: var(--text-secondary, #6b7280);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}

.convo-card.is-unread .name,
.convo-card.is-unread .snippet {
  font-weight: 600;
  color: var(--text-primary, #111827);
}

.card-bottom .badge {
  background: #ef4444;
  color: #ffffff;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
}

/* Skeleton Loaders */
.skeleton-list { padding: 10px 0; }
.skeleton-convo {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
}
.skeleton-avatar {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  background: #e2e8f0;
}
.skeleton-lines {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.skeleton-line {
  height: 12px;
  border-radius: 6px;
  background: #e2e8f0;
}
.skeleton-line.long { width: 70%; }
.skeleton-line.short { width: 40%; }

.shimmer {
  background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
  background-size: 200% 100%;
  animation: loadingShimmer 1.5s infinite;
}
@keyframes loadingShimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Search Dropdown Items */
.search-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  cursor: pointer;
  transition: background 0.2s;
  border-bottom: 1px solid var(--border-color, #f0f2f5);
}
.search-item:hover {
  background: var(--surface-hover, #f8fafc);
}
.search-item .info strong {
  display: block;
  font-size: 14px;
  color: var(--text-primary, #111827);
  margin-bottom: 2px;
}
.search-item .info span {
  font-size: 12px;
  color: var(--text-secondary, #6b7280);
}
.search-item .action-btn {
  background: var(--surface-hover, #f0fdf4);
  color: var(--primary-color, #16a34a);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  transition: transform 0.2s;
}
.search-item:hover .action-btn {
  transform: scale(1.1);
  background: var(--primary-color, #16a34a);
  color: #fff;
}

/* Right Pane (Chat Window) */
.right-pane {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: var(--bg-color, #f8fafc); 
  position: relative;
}

.chat-header {
  padding: 24px 30px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--border-color, #cbd5e1);
  display: flex;
  align-items: center;
  z-index: 5;
}

.mobile-back-btn {
  display: none;
  background: transparent;
  border: none;
  font-size: 32px;
  color: var(--text-primary, #111827);
  margin-right: 0; /* Removed the 8px margin */
  margin-left: -8px; /* Pulls the icon slightly left to reduce the optical gap */
  cursor: pointer;
  padding: 0;
}

.chat-header .user-meta {
  display: flex;
  align-items: center;
  gap: 12px; /* Adjusted gap to save space */
  flex: 1; /* Added for overflow fix */
  min-width: 0; /* Added for overflow fix */
}

.header-info {
  flex: 1; /* Added for overflow fix */
  min-width: 0; /* Added for overflow fix */
  display: flex;
  flex-direction: column;
}

.header-info .name {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary, #111827);
  white-space: nowrap; /* Fixes text wrapping over back button */
  overflow: hidden; 
  text-overflow: ellipsis;
  width: 100%;
}

.header-info .sub {
  margin: 2px 0 0;
  font-size: 12.5px;
  color: var(--text-secondary, #6b7280);
  white-space: nowrap; /* Fixes email wrapping awkwardly */
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
}

.chat-stream {
  flex: 1;
  overflow-y: auto;
  padding: 24px 30px;
  display: flex;
  flex-direction: column;
}

.message-wrapper {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Chat Messages */
.msg-row {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  margin: 0;
}
.slide-up {
  animation: slideUp 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) both;
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.msg-row.me { justify-content: flex-end; }
.msg-row.them { justify-content: flex-start; }

/* Reply Buttons Actions */
.msg-actions {
  opacity: 0;
  visibility: hidden;
  transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  margin-bottom: 4px;
  transform: scale(0.8);
}
.msg-row:hover .msg-actions,
.msg-row.show-actions .msg-actions { /* Enabled for Hold-To-Reply class */
  opacity: 1;
  visibility: visible;
  transform: scale(1);
}
.reply-icon-btn {
  background: var(--surface-color, #ffffff);
  border: 1px solid var(--border-color, #cbd5e1);
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--text-secondary, #6b7280);
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  transition: all 0.2s;
}
.reply-icon-btn:hover {
  color: var(--primary-color, #16a34a);
  border-color: var(--primary-color, #16a34a);
  transform: translateY(-2px);
}

.bubble {
  max-width: 65%;
  padding: 12px 18px;
  position: relative;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  cursor: pointer; /* Signal interactivity */
  -webkit-user-select: none; /* Prevent text selection during long press on mobile */
  user-select: none;
}

.msg-row.me .bubble {
  background: linear-gradient(135deg, var(--primary-color, #16a34a), #22c55e);
  color: #ffffff;
  border-radius: 20px 20px 4px 20px; 
}

.msg-row.them .bubble {
  background: var(--surface-color, #ffffff);
  color: var(--text-primary, #111827);
  border: 1px solid var(--border-color, #cbd5e1);
  border-radius: 20px 20px 20px 4px;
}

/* Quoted Reply Inside Bubble */
.bubble-quote {
  font-size: 12px;
  padding: 6px 12px;
  border-radius: 8px;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(0, 0, 0, 0.05);
  border-left: 3px solid rgba(0, 0, 0, 0.2);
}
.msg-row.me .bubble-quote {
  background: rgba(255, 255, 255, 0.2);
  border-left-color: rgba(255, 255, 255, 0.6);
  color: rgba(255, 255, 255, 0.95);
}
.msg-row.them .bubble-quote {
  background: var(--bg-secondary, #f1f5f9);
  border-left-color: var(--primary-color, #16a34a);
  color: var(--text-secondary, #4b5563);
}
.bubble-quote span {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 220px;
}

.bubble .text {
  margin: 0;
  font-size: 14.5px;
  line-height: 1.5;
  font-weight: 400;
  white-space: pre-wrap; 
  word-wrap: break-word;
}

.bubble .meta {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 6px;
  font-size: 11px;
  margin-top: 6px;
  font-weight: 500;
}

.msg-row.me .bubble .meta { color: rgba(255, 255, 255, 0.8); }
.msg-row.them .bubble .meta { color: var(--text-secondary, #9ca3af); }
.meta .read { color: #60a5fa; font-size: 14px;}

/* Input Area (Composer) */
.composer-container {
  padding: 0 30px 24px 30px; 
  background: transparent;
  position: relative;
}

/* Reply Preview Layout */
.reply-preview {
  background: var(--bg-secondary, #f8fafc);
  border: 1px solid var(--border-color, #cbd5e1);
  border-bottom: none;
  border-radius: 20px 20px 0 0;
  padding: 10px 16px 16px 16px;
  margin: 0 12px -12px 12px; 
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  z-index: 1;
}
.reply-preview-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow: hidden;
}
.reply-label {
  font-size: 11px;
  color: var(--primary-color, #16a34a);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 4px;
}
.reply-text {
  font-size: 13px;
  color: var(--text-secondary, #6b7280);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin: 0;
}
.cancel-reply-btn {
  background: none;
  border: none;
  color: var(--text-secondary, #9ca3af);
  font-size: 20px;
  cursor: pointer;
  padding: 4px;
  transition: color 0.2s;
}
.cancel-reply-btn:hover { color: #ef4444; }

.composer {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--surface-color, #ffffff);
  padding: 8px 8px 8px 24px;
  border-radius: 30px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  border: 1px solid var(--border-color, #cbd5e1);
  transition: box-shadow 0.3s ease, border-color 0.3s;
  position: relative;
  z-index: 2;
}

.composer.has-reply {
  border-radius: 0 0 24px 24px; 
}

.composer:focus-within {
  border-color: var(--primary-color, #16a34a);
  box-shadow: 0 6px 24px rgba(22, 163, 74, 0.12);
}

.composer input {
  flex: 1;
  height: 40px;
  border: none;
  background: transparent;
  color: var(--text-primary, #111827);
  outline: none;
  font-size: 14.5px;
}
.composer input::placeholder {
  color: #9ca3af;
}

.send-btn {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--primary-color, #16a34a);
  color: #ffffff;
  border: none;
  cursor: pointer;
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 10px rgba(22, 163, 74, 0.3);
}
.send-btn:hover:not(:disabled) {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 6px 14px rgba(22, 163, 74, 0.4);
}
.send-btn:disabled {
  background: #9ca3af;
  box-shadow: none;
  cursor: not-allowed;
}

/* Empty States & Specific Loaders */
.no-chat-selected, .empty-chat, .pane-notice {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.glass-icon {
  width: 80px;
  height: 80px;
  background: rgba(22, 163, 74, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}
.glass-icon i {
  font-size: 40px;
  color: var(--primary-color, #16a34a);
}

.no-chat-selected h3, .empty-chat h3 {
  font-size: 1.4rem;
  color: var(--text-primary, #111827);
  margin-bottom: 8px;
  font-weight: 700;
}
.no-chat-selected p, .empty-chat p {
  color: var(--text-secondary, #6b7280);
  font-size: 14px;
  max-width: 300px;
  line-height: 1.5;
}

.empty-illustration {
  width: 120px;
  margin-bottom: 20px;
  opacity: 0.8;
}

.empty-icon-wrap {
  width: 64px;
  height: 64px;
  background: #f3f4f6;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}
.empty-icon-wrap i {
  font-size: 32px;
  color: #9ca3af;
}

/* Centered Message Loading "Typing" Indicator */
.chat-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  gap: 12px;
}
.typing-bubble {
  background: var(--surface-color, #ffffff);
  border: 1px solid var(--border-color, #cbd5e1);
  padding: 16px 24px;
  border-radius: 20px;
  display: inline-block;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.typing-dots {
  display: flex;
  align-items: center;
  gap: 6px;
  height: 20px;
}
.typing-dots span {
  width: 8px;
  height: 8px;
  background-color: var(--primary-color, #16a34a);
  border-radius: 50%;
  animation: typing 1.4s infinite ease-in-out both;
}
.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }
@keyframes typing {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1); }
}

.loader-text {
  font-size: 13px;
  color: #9ca3af;
  font-weight: 500;
}

.modern-loader-container {
  padding: 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  color: var(--text-secondary, #6b7280);
}
.bouncing-dots {
  display: flex;
  gap: 6px;
}
.bouncing-dots span {
  width: 10px;
  height: 10px;
  background: var(--primary-color, #16a34a);
  border-radius: 50%;
  animation: bounce 0.6s infinite alternate;
}
.bouncing-dots span:nth-child(2) { animation-delay: 0.2s; }
.bouncing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes bounce {
  to { transform: translateY(-8px); }
}

/* ------------------- RESPONSIVENESS (MOBILE SLIDING VIEW) ------------------- */

@media (max-width: 992px) {
  .left-pane {
    width: 320px;
  }
}

@media (max-width: 768px) {
  /* Hide padding to make full screen */
  .main-container {
    padding: 0 !important;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .page-header-wrapper {
    display: none; /* Hide header completely to maximize space */
  }

  .messages-layout-card {
    height: 100% !important; /* Fill the whole container */
    width: 100% !important;
    margin: 0 !important;
    border-radius: 0 !important;
    border: none !important;
    box-shadow: none !important;
    min-height: auto;
  }

  /* List takes full width initially */
  .left-pane {
    width: 100%;
    height: 100%;
    border-right: none;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* Chat window is hidden off-screen to the right by default */
  .right-pane {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    transform: translateX(100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 20;
    background: var(--bg-color, #f8fafc);
  }

  /* When active, slide both panes left */
  .messages-layout-card.mobile-chat-open .left-pane {
    transform: translateX(-30%); /* Subtle parallax effect */
  }
  .messages-layout-card.mobile-chat-open .right-pane {
    transform: translateX(0);
  }
  
  /* Show Back Button */
  .mobile-back-btn {
    display: block;
  }

  /* Fix Header size overflow for smaller screen */
  .header-info .name { font-size: 14.5px; }
  .header-info .sub { font-size: 11.5px; }

  .chat-header {
    padding: 16px 20px;
  }

  .chat-stream {
    padding: 16px 20px;
  }

  .composer-container {
    padding: 0 20px 16px 20px;
  }

  .bubble {
    max-width: 85%;
  }
  
  .search-box {
    padding: 12px 16px;
  }
  
  .reply-icon-btn {
    width: 28px;
    height: 28px;
    font-size: 16px;
  }
}

/* ------------------- DARK MODE OVERRIDES ------------------- */
:global([data-theme="dark"]) .messages-layout-card {
  background: #111827;
  border-color: #374151;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
}

:global([data-theme="dark"]) .left-pane,
:global([data-theme="dark"]) .search-box,
:global([data-theme="dark"]) .chat-header {
  background: #111827;
  border-color: #374151;
}

:global([data-theme="dark"]) .mobile-back-btn {
  color: #f9fafb;
}

:global([data-theme="dark"]) .search-box input {
  background: #1f2937;
  color: #f9fafb;
}

:global([data-theme="dark"]) .search-box input:focus {
  background: #1f2937;
  border-color: #22c55e;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
}

:global([data-theme="dark"]) .right-pane {
  background: #0b0f19;
}

:global([data-theme="dark"]) .convo-card:hover,
:global([data-theme="dark"]) .search-item:hover {
  background: #1f2937;
}

:global([data-theme="dark"]) .convo-card.is-active {
  background: rgba(34, 197, 94, 0.1);
  border-left-color: #22c55e;
}

:global([data-theme="dark"]) .composer,
:global([data-theme="dark"]) .reply-preview {
  background: #1f2937;
  border-color: #374151;
}
:global([data-theme="dark"]) .reply-icon-btn {
  background: #1f2937;
  border-color: #374151;
  color: #9ca3af;
}
:global([data-theme="dark"]) .composer input {
  color: #f9fafb;
}

:global([data-theme="dark"]) .msg-row.them .bubble,
:global([data-theme="dark"]) .typing-bubble {
  background: #1f2937;
  color: #f9fafb;
  border-color: #374151;
}

:global([data-theme="dark"]) .msg-row.them .bubble-quote {
  background: #111827;
  color: #cbd5e1;
}

:global([data-theme="dark"]) .card-top .name,
:global([data-theme="dark"]) .header-info .name,
:global([data-theme="dark"]) .search-item .info strong,
:global([data-theme="dark"]) .no-chat-selected h3,
:global([data-theme="dark"]) .empty-chat h3 {
  color: #f9fafb;
}

:global([data-theme="dark"]) .card-top .time,
:global([data-theme="dark"]) .card-bottom .snippet,
:global([data-theme="dark"]) .header-info .sub,
:global([data-theme="dark"]) .search-item .info span,
:global([data-theme="dark"]) .no-chat-selected p,
:global([data-theme="dark"]) .empty-chat p,
:global([data-theme="dark"]) .reply-text {
  color: #9ca3af;
}

:global([data-theme="dark"]) .skeleton-avatar,
:global([data-theme="dark"]) .skeleton-line,
:global([data-theme="dark"]) .empty-icon-wrap {
  background: #1f2937;
}
:global([data-theme="dark"]) .shimmer {
  background: linear-gradient(90deg, #1f2937 25%, #374151 50%, #1f2937 75%);
  background-size: 200% 100%;
}
</style>