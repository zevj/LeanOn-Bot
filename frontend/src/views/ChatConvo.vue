<template>
    <div class="layout">
        <SidebarStudent
            :open="sidebarOpen"
            @toggle="sidebarOpen = !sidebarOpen"
        />

        <main class="main-area">
            <HeaderStudent @toggle-sidebar="sidebarOpen = !sidebarOpen" />

            <div class="main-container">
                <!-- Empty State -->
                <div v-if="messages.length === 0" class="empty-state">
                    <div class="title-container">
                        <h1 class="main-title">What's going on your mind?</h1>
                    </div>

                    <div class="main-buttons">
                        <button class="sugguestion-buttons" @click="sendSuggestion('I\'m feeling stressed, what would you recommend to calm down?')">I'm feeling stressed, what would you recommend to calm down?</button>
                        <button class="sugguestion-buttons" @click="sendSuggestion('I feel anxious about my upcoming exams.')">I feel anxious about my upcoming exams.</button>
                        <button class="sugguestion-buttons" @click="sendSuggestion('I just need someone to talk to right now.')">I just need someone to talk to right now.</button>
                    </div>
                </div>

                <!-- Chat History State -->
                <div v-else class="chat-history-container" ref="chatContainer">
                    <div v-for="(msg, index) in messages" :key="index" class="message-row" :class="msg.isBot ? 'bot-row' : 'user-row'">
                        <div class="message-bubble" :class="msg.isBot ? 'bot-message' : 'user-message'">
                            <p>{{ msg.text }}</p>
                        </div>
                        <span class="message-time">{{ msg.time }}</span>

                        <div v-if="msg.isBot && msg.quickReplies && msg.quickReplies.length > 0" class="quick-replies">
                            <button v-for="reply in msg.quickReplies" :key="reply" class="quick-reply-pill" @click="sendSuggestion(reply)">
                                {{ reply }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <SendChat @send="handleSend" />
        </main>
    </div>

    <TermsModal
        :visible="showTermsModal"
        :userId="currentUserId"
        @accept="onTermsAccepted"
        @decline="onTermsDeclined"
    />
</template>

<script setup>
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import SidebarStudent from '@/components/sidebarStudent.vue';
import HeaderStudent from '@/components/headerStudent.vue';
import SendChat from '@/components/SendChat.vue';
import TermsModal from '@/components/TermsModal.vue';
import { useChats } from '@/composables/useChats';

// ── Sidebar state
const sidebarOpen = ref(false)

const currentUserId = computed(() => {
    try {
        const user = JSON.parse(localStorage.getItem('user') || 'null');
        return user?.id ?? 'guest';
    } catch {
        return 'guest';
    }
});

const messages = ref([]);
const chatContainer = ref(null);
const route = useRoute();
const router = useRouter();
const { addConversation, updateConversation } = useChats();
const isAutoCreating = ref(false);

// ── Terms Modal
const showTermsModal = ref(false);
const termsStorageKey = computed(() => `leanon_terms_v1_${currentUserId.value}`);

onMounted(() => {
    const alreadyAccepted = localStorage.getItem(termsStorageKey.value);
    if (!alreadyAccepted) {
        showTermsModal.value = true;
    } else {
        fetchHistory(route.query.conversation_id);
    }
});

const onTermsAccepted = () => {
    showTermsModal.value = false;
    fetchHistory(route.query.conversation_id);
};

const onTermsDeclined = () => {
    showTermsModal.value = false;
    router.push('/login');
};

const getTimeString = () => {
    const now = new Date();
    let hours = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    return `${hours}:${minutes}${ampm}`;
};

const scrollToBottom = async () => {
    await nextTick();
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const handleSend = async (text) => {
    if (!text.trim()) return;
    let conversationId = route.query.conversation_id;

    if (!conversationId) {
        isAutoCreating.value = true;
        try {
            const res = await axios.post('/api/conversations');
            conversationId = res.data.id;
            addConversation(res.data);
            await router.replace({ query: { conversation_id: conversationId } });
        } catch (error) {
            console.error("Failed to auto-create conversation", error);
            alert("Failed to start chat.");
            isAutoCreating.value = false;
            return;
        }
    }

    messages.value.push({ text, isBot: false, time: getTimeString() });
    scrollToBottom();

    try {
        const response = await axios.post('http://localhost:8000/api/chat', {
            message: text,
            conversation_id: conversationId
        }, {
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
        });

        const replyData = response.data;

        updateConversation(conversationId, {
            title: text.substring(0, 50) + (text.length > 50 ? '...' : ''),
            last_message: text.substring(0, 50) + (text.length > 50 ? '...' : ''),
            updated_at: new Date().toISOString()
        });

        messages.value.push({
            text: replyData.reply,
            isBot: true,
            time: getTimeString(),
            quickReplies: replyData.is_crisis ? [] : [
                "I'm feeling stressed",
                "I'm anxious about exams",
                "I just need someone to talk to"
            ]
        });
    } catch (error) {
        messages.value.push({
            text: 'Sorry, I am having trouble connecting to my brain right now.',
            isBot: true,
            time: getTimeString(),
        });
        console.error('API Error:', error);
    }

    scrollToBottom();
};

const fetchHistory = async (conversationId) => {
    if (!conversationId) { messages.value = []; return; }
    try {
        const response = await axios.get(`http://localhost:8000/api/chat/history?conversation_id=${conversationId}`);
        messages.value = response.data.map(msg => [
            {
                text: msg.message,
                isBot: false,
                time: new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            },
            {
                text: msg.reply,
                isBot: true,
                time: new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                quickReplies: msg.is_crisis ? [] : [
                    "I'm feeling stressed",
                    "I'm anxious about exams",
                    "I just need someone to talk to"
                ]
            }
        ]).flat();
        scrollToBottom();
    } catch (error) {
        console.error('API Error fetching history:', error);
    }
};

watch(() => route.query.conversation_id, (newId) => {
    if (isAutoCreating.value) { isAutoCreating.value = false; return; }
    fetchHistory(newId);
});

const sendSuggestion = (text) => handleSend(text);
</script>

<style scoped src="../assets/users/ChatConvo.css"></style>