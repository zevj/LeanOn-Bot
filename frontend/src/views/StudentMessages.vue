<template>
  <div class="layout">
    <SidebarStudent
      :open="sidebarOpen"
      @toggle="sidebarOpen = !sidebarOpen"
    />

    <main class="main-area">
      <HeaderStudent @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <div class="messages-page-container">
        <!-- Direct Message Main Container -->
        <div class="dm-card">
          
          <!-- Header Banner -->
          <div class="dm-header">
            <div class="counselor-info">
              <div class="counselor-avatar">
                <template v-if="otherParticipant?.profile_image_url">
                  <img :src="otherParticipant.profile_image_url" alt="Counselor Profile" />
                </template>
                <template v-else>
                  {{ getInitials(otherParticipant?.full_name || 'Guidance Counselor') }}
                </template>
              </div>
              <div class="counselor-details">
                <h2 class="counselor-name">
                  {{ otherParticipant?.full_name || 'Guidance Counselor' }}
                </h2>
                <div class="counselor-status">
                  <span class="status-dot"></span>
                  <span class="status-text">Counseling & Guidance Support</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Messages Stream Container -->
          <div ref="chatContainerRef" class="dm-body">
            <!-- Loading Notice -->
            <div v-if="loadingConversations || loadingMessages" class="loading-state">
              <div class="mini-spinner"></div>
              <span>Connecting to Guidance Counselor...</span>
            </div>

            <!-- Empty Conversation State -->
            <div v-else-if="messages.length === 0" class="empty-messages">
              <div class="empty-icon-wrap">
                <i class="bx bx-message-rounded-dots"></i>
              </div>
              <h3>Start a Conversation</h3>
              <p>Send a direct message to communicate privately with your Guidance Counselor.</p>
            </div>

            <!-- Messages List -->
            <template v-else>
              <div
                v-for="msg in messages"
                :key="msg.id"
                class="message-row"
                :class="isMyMessage(msg) ? 'my-message-row' : 'counselor-message-row'"
              >
                <div v-if="!isMyMessage(msg)" class="avatar-small">
                  {{ getInitials(msg.sender_name || otherParticipant?.full_name) }}
                </div>

                <div class="message-bubble" :class="isMyMessage(msg) ? 'my-bubble' : 'counselor-bubble'">
                  <div class="sender-label" v-if="!isMyMessage(msg)">
                    {{ msg.sender_name || otherParticipant?.full_name || 'Counselor' }}
                  </div>
                  <p class="message-text">{{ msg.message }}</p>
                  <div class="message-meta">
                    <span class="message-time">{{ formatTime(msg.created_at) }}</span>
                    <span v-if="isMyMessage(msg)" class="read-status">
                      <i :class="msg.is_read ? 'bx bx-check-double read' : 'bx bx-check'"></i>
                    </span>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Input Composer -->
          <div class="dm-footer">
            <form @submit.prevent="handleSend" class="input-form">
              <input
                v-model="msgText"
                type="text"
                class="message-input"
                placeholder="Type your message to the Guidance Counselor..."
                :disabled="sendingMessage"
                @keydown.enter.exact.prevent="handleSend"
              />
              <button
                type="submit"
                class="send-btn"
                :disabled="!msgText.trim() || sendingMessage"
              >
                <i v-if="!sendingMessage" class="bx bx-paper-plane"></i>
                <div v-else class="mini-spinner button-spinner"></div>
              </button>
            </form>
          </div>

        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import SidebarStudent from '@/components/sidebarStudent.vue'
import HeaderStudent from '@/components/headerStudent.vue'
import { useDirectMessages } from '@/composables/useDirectMessages'

const sidebarOpen = ref(false)
const msgText = ref('')
const chatContainerRef = ref(null)

const {
  conversations,
  activeConversation,
  messages,
  loadingConversations,
  loadingMessages,
  sendingMessage,
  fetchConversations,
  selectConversation,
  sendMessage,
  startConversationWithStudent,
  startPolling,
  stopPolling
} = useDirectMessages()

const currentUser = computed(() => {
  try {
    const raw = localStorage.getItem('user')
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
})

const otherParticipant = computed(() => {
  if (!activeConversation.value) return null
  return activeConversation.value.other_participant || activeConversation.value.admin || null
})

const isMyMessage = (msg) => {
  return currentUser.value && (parseInt(msg.sender_id, 10) === parseInt(currentUser.value.id, 10))
}

const getInitials = (name) => {
  if (!name) return 'GC'
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

const handleSend = async () => {
  if (!msgText.value.trim() || sendingMessage.value) return
  const text = msgText.value
  msgText.value = ''

  // If no active conversation exists, start conversation with counselor
  if (!activeConversation.value) {
    await startConversationWithStudent(null)
  }

  await sendMessage(text)
  scrollToBottom()
}

watch(messages, () => {
  scrollToBottom()
}, { deep: true })

onMounted(async () => {
  await fetchConversations()

  if (conversations.value.length > 0) {
    await selectConversation(conversations.value[0])
  } else {
    // Automatically connect student to guidance counselor
    await startConversationWithStudent(null)
  }

  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
  background-color: var(--bg-color);
  color: var(--text-primary);
}

.main-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.messages-page-container {
  flex: 1;
  padding: 1.5rem;
  display: flex;
  justify-content: center;
  align-items: center;
}

.dm-card {
  width: 100%;
  max-width: 1000px;
  height: calc(100vh - 120px);
  background-color: var(--surface-color);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  box-shadow: 0 10px 25px var(--shadow-color);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Header */
.dm-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  background-color: var(--bg-secondary);
}

.counselor-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.counselor-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: var(--white-const);
  font-weight: 700;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.counselor-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.counselor-name {
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.counselor-status {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-top: 0.2rem;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #22c55e;
  box-shadow: 0 0 8px #22c55e;
}

.status-text {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

/* Body / Chat stream */
.dm-body {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  background-color: var(--bg-color);
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 3rem;
  color: var(--text-secondary);
}

.empty-messages {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  margin: auto;
  padding: 2rem;
  color: var(--text-secondary);
}

.empty-icon-wrap {
  font-size: 3rem;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background-color: var(--surface-color);
  border: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
  color: var(--primary-color);
}

.empty-messages h3 {
  font-size: 1.2rem;
  color: var(--text-primary);
  margin-bottom: 0.4rem;
}

/* Message Rows */
.message-row {
  display: flex;
  align-items: flex-end;
  gap: 0.6rem;
  max-width: 75%;
}

.my-message-row {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.counselor-message-row {
  align-self: flex-start;
}

.avatar-small {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--primary-color);
  color: var(--white-const);
  font-size: 0.75rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.message-bubble {
  padding: 0.8rem 1.1rem;
  border-radius: 18px;
  font-size: 0.95rem;
  line-height: 1.45;
  word-break: break-word;
  position: relative;
  box-shadow: 0 2px 6px var(--shadow-color);
}

.my-bubble {
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: var(--white-const);
  border-bottom-right-radius: 4px;
}

.counselor-bubble {
  background-color: var(--surface-color);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
  border-bottom-left-radius: 4px;
}

.sender-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--primary-color);
  margin-bottom: 0.2rem;
}

.message-text {
  margin: 0;
  white-space: pre-wrap;
  background: transparent !important;
  color: inherit;
  font-weight: 400 !important;
}

.message-meta {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.4rem;
  margin-top: 0.3rem;
  font-size: 0.7rem;
  opacity: 0.85;
}

.read-status .read {
  color: #60a5fa;
}

/* Footer / Input */
.dm-footer {
  padding: 1rem 1.5rem;
  background-color: var(--surface-color);
  border-top: 1px solid var(--border-color);
}

.input-form {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.message-input {
  flex: 1;
  padding: 0.85rem 1.2rem;
  border-radius: 24px;
  border: 1px solid var(--border-color);
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  font-size: 0.95rem;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.message-input:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(14, 96, 8, 0.15);
}

.send-btn {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: var(--white-const);
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.2s, opacity 0.2s;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.send-btn:hover:not(:disabled) {
  transform: scale(1.05);
}

.send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.mini-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: var(--white-const);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .messages-page-container {
    padding: 0.5rem;
  }
  
  .dm-card {
    height: calc(100vh - 80px);
    border-radius: 0;
  }
  
  .message-row {
    max-width: 88%;
  }
}

/* Explicit Dark Mode Overrides */
:global([data-theme="dark"]) .dm-card {
  background-color: #1e293b;
  border-color: #334155;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
}

:global([data-theme="dark"]) .dm-header {
  background-color: #0f172a;
  border-bottom-color: #334155;
}

:global([data-theme="dark"]) .counselor-name {
  color: #f8fafc;
}

:global([data-theme="dark"]) .status-text {
  color: #94a3b8;
}

:global([data-theme="dark"]) .dm-body {
  background-color: #0b1120;
}

:global([data-theme="dark"]) .dm-body::-webkit-scrollbar-thumb {
  background-color: #334155;
  border-radius: 4px;
}

:global([data-theme="dark"]) .loading-state {
  color: #94a3b8;
}

:global([data-theme="dark"]) .loading-state .mini-spinner {
  border-color: rgba(74, 222, 128, 0.3);
  border-top-color: #4ade80;
}

:global([data-theme="dark"]) .empty-icon-wrap {
  background-color: #1e293b;
  border-color: #334155;
  color: #4ade80;
}

:global([data-theme="dark"]) .empty-messages h3 {
  color: #f8fafc;
}

:global([data-theme="dark"]) .empty-messages p {
  color: #94a3b8;
}

:global([data-theme="dark"]) .avatar-small {
  background: #16a34a;
  color: #ffffff;
}

:global([data-theme="dark"]) .counselor-bubble {
  background-color: #1e293b;
  color: #f8fafc;
  border-color: #334155;
}

:global([data-theme="dark"]) .counselor-bubble .sender-label {
  color: #4ade80;
}

:global([data-theme="dark"]) .counselor-bubble .message-text {
  color: #f8fafc;
}

:global([data-theme="dark"]) .counselor-bubble .message-time {
  color: #94a3b8;
}

:global([data-theme="dark"]) .my-bubble {
  background: linear-gradient(135deg, #0e6008, #16a34a);
  color: #ffffff;
}

:global([data-theme="dark"]) .dm-footer {
  background-color: #0f172a;
  border-top-color: #334155;
}

:global([data-theme="dark"]) .message-input {
  background-color: #1e293b;
  border-color: #334155;
  color: #f8fafc;
}

:global([data-theme="dark"]) .message-input::placeholder {
  color: #64748b;
}

:global([data-theme="dark"]) .message-input:focus {
  border-color: #4ade80;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.2);
}

:global([data-theme="dark"]) .send-btn {
  background: linear-gradient(135deg, #0e6008, #16a34a);
  color: #ffffff;
}
</style>
