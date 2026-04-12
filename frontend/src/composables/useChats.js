import { ref } from 'vue'
import axios from 'axios'

const chats = ref([])

export function useChats() {
    const fetchConversations = async () => {
        try {
            const res = await axios.get('/api/conversations')
            chats.value = res.data
        } catch (error) {
            console.error("Failed to fetch conversations", error)
        }
    }

    const addConversation = (chat) => {
        chats.value.unshift(chat)
    }

    const removeConversation = (id) => {
        const idx = chats.value.findIndex(c => c.id === id)
        if (idx !== -1) chats.value.splice(idx, 1)
    }

    const updateConversation = (id, updates) => {
        const chat = chats.value.find(c => c.id === id)
        if (chat) {
            Object.assign(chat, updates)
        }
    }

    return {
        chats,
        fetchConversations,
        addConversation,
        removeConversation,
        updateConversation
    }
}
