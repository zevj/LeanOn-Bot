<template>
    <div class="layout">
        <SidebarStudent></SidebarStudent>

        <main>
            <HeaderStudent></HeaderStudent>

            <div class="main-container">
                <!-- Empty State -->
                <div v-if="messages.length === 0" class="empty-state">
                    <div class="title-container">
                        <h1 class="main-title">What's going on your mind?</h1>
                    </div>

                    <div class="main-buttons">
                        <button class="sugguestion-buttons" @click="sendSuggestion('I’m feeling stressed, what would you recommend to calm down?')">I’m feeling stressed, what would you recommend to calm down?</button>
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
                        
                        <!-- Quick Replies (only show after specific bot messages if needed, here mimicking the mockup) -->
                        <div v-if="msg.isBot && msg.quickReplies && msg.quickReplies.length > 0" class="quick-replies">
                            <button v-for="reply in msg.quickReplies" :key="reply" class="quick-reply-pill" @click="sendSuggestion(reply)">
                                {{ reply }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <SendChat @send="handleSend"></SendChat>
        </main>
    </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import axios from 'axios';
import SidebarStudent from '@/components/sidebar.vue';
import HeaderStudent from '@/components/header.vue';
import SendChat from '@/components/SendChat.vue';

const messages = ref([]);
const chatContainer = ref(null);

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

    // Add user message
    messages.value.push({
        text: text,
        isBot: false,
        time: getTimeString(),
    });
    
    scrollToBottom();

    try {
        const response = await axios.post('http://localhost:8000/api/chat', {
            message: text
        }, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        const replyData = response.data;
        
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

const sendSuggestion = (text) => {
    handleSend(text);
};
</script>

<style scoped src="../assets/users/ChatConvo.css"></style>
