import { ref, computed, onUnmounted } from 'vue'
import axios from 'axios'
import { subscribeToPrivateChannel, unsubscribeFromChannel } from '@/services/echo'

// Global shared reactive state
const isOpen = ref(false)
const conversations = ref([])
const activeConversation = ref(null)
const messages = ref([])
const totalUnreadCount = ref(0)

const loadingConversations = ref(false)
const loadingMessages = ref(false)
const sendingMessage = ref(false)

const searchQuery = ref('')
const searchResults = ref([])
const searchingStudents = ref(false)

let pollInterval = null
let currentChannelName = null

const authConfig = () => ({
  headers: {
    Authorization: `Bearer ${localStorage.getItem('token') || ''}`,
  },
})

export function useDirectMessages() {
  const fetchConversations = async () => {
    try {
      loadingConversations.value = true
      const res = await axios.get('/api/direct-messages/conversations', authConfig())
      conversations.value = res.data.conversations || []
      totalUnreadCount.value = res.data.total_unread || 0
    } catch (err) {
      console.error('Error fetching direct conversations:', err)
    } finally {
      loadingConversations.value = false
    }
  }

  const selectConversation = async (convo) => {
    activeConversation.value = convo
    if (!convo) {
      messages.value = []
      if (currentChannelName) {
        unsubscribeFromChannel(currentChannelName)
        currentChannelName = null
      }
      return
    }

    await fetchMessages(convo.id)

    // Subscribe to Reverb Broadcast Channel
    if (currentChannelName && currentChannelName !== `direct-chat.${convo.id}`) {
      unsubscribeFromChannel(currentChannelName)
    }
    currentChannelName = `direct-chat.${convo.id}`

    subscribeToPrivateChannel(currentChannelName, 'DirectMessageSent', (eventData) => {
      if (activeConversation.value && activeConversation.value.id === eventData.conversation_id) {
        const exists = messages.value.some((m) => m.id === eventData.id)
        if (!exists) {
          messages.value.push(eventData)
          markConversationAsRead(eventData.conversation_id)
        }
      } else {
        fetchConversations()
      }
    })
  }

  const fetchMessages = async (conversationId) => {
    if (!conversationId) return
    try {
      loadingMessages.value = true
      const res = await axios.get(
        `/api/direct-messages/conversations/${conversationId}/messages`,
        authConfig()
      )
      messages.value = res.data.messages || []

      // Optimistically update unread count for this conversation
      const foundIndex = conversations.value.findIndex((c) => c.id === conversationId)
      if (foundIndex !== -1) {
        const prevUnread = conversations.value[foundIndex].unread_count || 0
        conversations.value[foundIndex].unread_count = 0
        totalUnreadCount.value = Math.max(0, totalUnreadCount.value - prevUnread)
      }
    } catch (err) {
      console.error('Error fetching direct messages:', err)
    } finally {
      loadingMessages.value = false
    }
  }

  const sendMessage = async (text) => {
    if (!activeConversation.value || !text || !text.trim() || sendingMessage.value) return
    const content = text.trim()
    try {
      sendingMessage.value = true
      const convoId = activeConversation.value.id
      const res = await axios.post(
        `/api/direct-messages/conversations/${convoId}/messages`,
        { message: content },
        authConfig()
      )

      const newMsg = res.data
      const exists = messages.value.some((m) => m.id === newMsg.id)
      if (!exists) {
        messages.value.push(newMsg)
      }

      // Update active conversation's last message snippet
      if (activeConversation.value) {
        activeConversation.value.last_message = content
        activeConversation.value.last_message_at = newMsg.created_at
      }

      fetchConversations()
    } catch (err) {
      console.error('Error sending direct message:', err)
      throw err
    } finally {
      sendingMessage.value = false
    }
  }

  const markConversationAsRead = async (conversationId) => {
    try {
      await axios.post(`/api/direct-messages/conversations/${conversationId}/read`, {}, authConfig())
      fetchConversations()
    } catch (err) {
      // Ignore read notification error
    }
  }

  const startConversationWithStudent = async (studentId) => {
    try {
      isOpen.value = true
      loadingConversations.value = true
      const res = await axios.post(
        '/api/direct-messages/conversations',
        { student_id: studentId },
        authConfig()
      )
      const convo = res.data
      await fetchConversations()
      const targetConvo = conversations.value.find((c) => c.id === convo.id) || convo
      await selectConversation(targetConvo)
    } catch (err) {
      console.error('Error starting conversation with student:', err)
    } finally {
      loadingConversations.value = false
    }
  }

  const searchStudents = async (q = '') => {
    const searchStr = typeof q === 'string' ? q : ''
    searchQuery.value = searchStr
    try {
      searchingStudents.value = true
      const res = await axios.get(
        `/api/direct-messages/students/search?q=${encodeURIComponent(searchStr.trim())}`,
        authConfig()
      )
      searchResults.value = res.data.students || []
    } catch (err) {
      console.error('Error searching students:', err)
      searchResults.value = []
    } finally {
      searchingStudents.value = false
    }
  }

  const openDrawer = () => {
    isOpen.value = true
    fetchConversations()
    startPolling()
  }

  const closeDrawer = () => {
    isOpen.value = false
    stopPolling()
  }

  const toggleDrawer = () => {
    if (isOpen.value) {
      closeDrawer()
    } else {
      openDrawer()
    }
  }

  const startPolling = () => {
    stopPolling()
    pollInterval = setInterval(async () => {
      if (isOpen.value) {
        await fetchConversations()
        if (activeConversation.value) {
          const res = await axios.get(
            `/api/direct-messages/conversations/${activeConversation.value.id}/messages`,
            authConfig()
          )
          const fetchedMsgs = res.data.messages || []
          if (fetchedMsgs.length !== messages.value.length) {
            messages.value = fetchedMsgs
          }
        }
      } else {
        // Poll unread count periodically when closed
        const res = await axios.get('/api/direct-messages/conversations', authConfig())
        totalUnreadCount.value = res.data.total_unread || 0
      }
    }, 4000)
  }

  const stopPolling = () => {
    if (pollInterval) {
      clearInterval(pollInterval)
      pollInterval = null
    }
  }

  return {
    isOpen,
    conversations,
    activeConversation,
    messages,
    totalUnreadCount,
    loadingConversations,
    loadingMessages,
    sendingMessage,
    searchQuery,
    searchResults,
    searchingStudents,
    fetchConversations,
    selectConversation,
    fetchMessages,
    sendMessage,
    startConversationWithStudent,
    searchStudents,
    openDrawer,
    closeDrawer,
    toggleDrawer,
    startPolling,
    stopPolling,
  }
}
