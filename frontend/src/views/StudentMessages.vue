<template>
  <div class="layout">
    <SidebarStudent
      :open="sidebarOpen"
      @toggle="sidebarOpen = !sidebarOpen"
    />

    <main class="main-area">
      <HeaderStudent @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <div class="main-container">
        <div class="page-header-wrapper">
          <div class="header-title">
            <h1 class="title">Direct Messages</h1>
            <p class="subtext">Communicate directly with students in real time.</p>
          </div>
        </div>

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
            <div ref="chatContainerRef" class="chat-stream" @click="closeActions">
              <!-- Loading Notice -->
              <div v-if="loadingConversations || loadingMessages" class="chat-loader">
                <div class="typing-bubble">
                  <div class="typing-dots"><span></span><span></span><span></span></div>
                </div>
                <span class="loader-text">Connecting to Guidance Counselor...</span>
              </div>

              <!-- Empty Conversation State -->
              <div v-else-if="messages.length === 0" class="empty-chat">
                <img src="https://cdn-icons-png.flaticon.com/512/1041/1041916.png" alt="Say Hi" class="empty-illustration"/>
                <h3>Start a Conversation</h3>
                <p>Send a direct message to communicate privately with your Guidance Counselor.</p>
              </div>

              <!-- Messages List -->
              <div v-else class="message-wrapper">
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
            </div>

            <!-- Input Composer -->
            <div class="composer-container">
              <!-- Reply Preview Box -->
              <div v-if="replyingTo" class="reply-preview slide-up">
                <div class="reply-preview-content">
                  <span class="reply-label">
                    <i class="bx bx-reply"></i> 
                    Replying to {{ isMyMessage(replyingTo) ? 'yourself' : otherParticipant?.full_name || 'Counselor' }}
                  </span>
                  <p class="reply-text">{{ extractReply(replyingTo.message).content }}</p>
                </div>
                <button class="cancel-reply-btn" @click="cancelReply"><i class="bx bx-x"></i></button>
              </div>

              <form class="composer" :class="{ 'has-reply': replyingTo }" @submit.prevent="handleSend">
                <input
                  ref="msgInputRef"
                  v-model="msgText"
                  type="text"
                  placeholder="Type your message to the Guidance Counselor..."
                  :disabled="sendingMessage"
                />
                <button type="submit" class="send-btn" :disabled="!msgText.trim() || sendingMessage">
                  <i class="bx" :class="sendingMessage ? 'bx-loader-alt bx-spin' : 'bx-send'"></i>
                </button>
              </form>
            </div>

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
const msgInputRef = ref(null)
const replyingTo = ref(null)

// Variables for hold-to-reply functionality
const activeActionMsgId = ref(null)
let pressTimer = null

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

/* Long press / Hold functionality logic */
const startPress = (msgId) => {
  if (pressTimer) clearTimeout(pressTimer)
  
  pressTimer = setTimeout(() => {
    activeActionMsgId.value = activeActionMsgId.value === msgId ? null : msgId
    if (typeof window !== 'undefined' && window.navigator && window.navigator.vibrate) {
      window.navigator.vibrate(50)
    }
  }, 500)
}

const cancelPress = () => {
  if (pressTimer) {
    clearTimeout(pressTimer)
    pressTimer = null
  }
}

const closeActions = (e) => {
  if (!e.target.closest('.msg-row')) {
    activeActionMsgId.value = null
  }
}

/* Reply functionality helpers */
const startReply = (msg) => {
  replyingTo.value = msg
  activeActionMsgId.value = null
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

  // Attach reply formatting if replying
  if (replyingTo.value) {
    const originalContent = extractReply(replyingTo.value.message).content
    finalMessageText = `[REPLY:"${originalContent}"]\n${finalMessageText}`
    replyingTo.value = null 
  }

  msgText.value = ''

  // If no active conversation exists, start conversation with counselor
  if (!activeConversation.value) {
    await startConversationWithStudent(null)
  }

  await sendMessage(finalMessageText)
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
/* ── Layout ── */
.layout {
  display: flex;
  height: 100dvh;
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
  display: flex;
  flex-direction: column;
  overflow: hidden; 
  padding: 2rem 2.5rem 2rem;
}

.page-header-wrapper {
  flex-shrink: 0;
}

.messages-page-container {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 0; 
}

/* Main Layout Enhancements */
.dm-card {
  display: flex;
  flex-direction: column;
  flex: 1; 
  height: 100%;
  width: 100%;
  max-width: 1100px;
  margin-top: 10px;
  background: var(--surface-color, #ffffff);
  border: 1px solid var(--border-color, #cbd5e1); 
  border-radius: 20px; 
  overflow: hidden;
  box-shadow: 0 10px 40px -10px var(--shadow-color, rgba(0, 0, 0, 0.08));
  transition: all 0.3s ease;
  position: relative;
  min-height: 0; 
}

/* Header */
.dm-header {
  padding: 20px 30px;
  background: var(--surface-color, #ffffff); 
  border-bottom: 1px solid var(--border-color, #cbd5e1);
  display: flex;
  align-items: center;
  z-index: 5;
  flex-shrink: 0;
}

.counselor-info {
  display: flex;
  align-items: center;
  gap: 14px;
}

.counselor-avatar {
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
  overflow: hidden;
}

.counselor-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.counselor-details {
  display: flex;
  flex-direction: column;
}

.counselor-name {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary, #111827);
}

.counselor-status {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 2px;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #22c55e;
  box-shadow: 0 0 8px rgba(34, 197, 94, 0.6);
}

.status-text {
  font-size: 12.5px;
  color: var(--text-secondary, #6b7280);
}

/* Chat Stream */
.chat-stream {
  flex: 1;
  overflow-y: auto;
  padding: 24px 30px;
  display: flex;
  flex-direction: column;
  background: var(--bg-color, #f8fafc);
}

.chat-stream::-webkit-scrollbar {
  width: 6px;
}
.chat-stream::-webkit-scrollbar-thumb {
  background-color: var(--border-color, #d1d5db);
  border-radius: 10px;
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
.msg-row.show-actions .msg-actions {
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
  cursor: pointer;
  -webkit-user-select: none;
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
  background: var(--bg-color, #f8fafc);
  position: relative;
  flex-shrink: 0;
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

/* Empty States & Loaders */
.empty-chat {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.empty-chat h3 {
  font-size: 1.4rem;
  color: var(--text-primary, #111827);
  margin-bottom: 8px;
  font-weight: 700;
}
.empty-chat p {
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


/* ------------------- RESPONSIVENESS (MOBILE FULL SCREEN) ------------------- */

@media (max-width: 768px) {
  .main-container {
    padding: 0 !important;
  }

  .page-header-wrapper {
    display: none; 
  }

  .messages-page-container {
    padding: 0;
  }

  .dm-card {
    height: 100% !important; 
    width: 100% !important;
    margin: 0 !important;
    border-radius: 0 !important;
    border: none !important;
    box-shadow: none !important;
    min-height: 0 !important; /* Critical constraint */
  }

  .chat-header, .dm-header {
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
  
  .reply-icon-btn {
    width: 28px;
    height: 28px;
    font-size: 16px;
  }
}

/* ------------------- DARK MODE OVERRIDES ------------------- */
:global([data-theme="dark"]) .dm-card {
  background: #111827 !important;
  border-color: #374151 !important;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4) !important;
}

:global([data-theme="dark"]) .dm-header {
  background: #111827 !important;
  border-bottom-color: #374151 !important;
}

:global([data-theme="dark"]) .counselor-name,
:global([data-theme="dark"]) .empty-chat h3 {
  color: #f9fafb;
}

:global([data-theme="dark"]) .status-text,
:global([data-theme="dark"]) .empty-chat p,
:global([data-theme="dark"]) .reply-text {
  color: #9ca3af;
}

:global([data-theme="dark"]) .chat-stream,
:global([data-theme="dark"]) .composer-container {
  background: #0b0f19;
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

:global([data-theme="dark"]) .msg-row.them .bubble .meta {
  color: #9ca3af;
}
</style>