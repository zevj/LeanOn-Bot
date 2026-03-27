<template>
  <div class="send-container">
    <div class="chat-send-input-container">
      <input
        type="text"
        placeholder="Share how you're feeling..."
        v-model="message"
        @keyup.enter="sendMessage"
      />
      <button class="send" @click="sendMessage">
        <i class="bx bx-paper-plane" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const message = ref('')

const sendMessage = () => {
  if (!message.value.trim()) return
  console.log('Message sent:', message.value)
  message.value = ''
}
</script>

<style scoped src="../assets/Header & Sidebar/SendChat.css"></style>

