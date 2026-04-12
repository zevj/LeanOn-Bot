<template>
  <transition name="modal">
    <div v-if="visible" class="modal-overlay" @click="cancel">
      <div class="modal-container" @click.stop>
        <h3 class="modal-title">{{ title }}</h3>
        <p class="modal-message">{{ message }}</p>
        <div class="modal-buttons">
          <button class="cancel-btn" @click="cancel">{{ cancelText }}</button>
          <button :class="['confirm-btn', type]" @click="confirm">{{ confirmText }}</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

defineProps({
  visible: { type: Boolean, required: true },
  title: { type: String, default: 'Confirm Action' },
  message: { type: String, default: 'Are you sure you want to proceed?' },
  confirmText: { type: String, default: 'Confirm' },
  cancelText: { type: String, default: 'Cancel' },
  type: { type: String, default: 'primary' } // 'primary' or 'danger'
})

const emit = defineEmits(['confirm', 'cancel'])

const confirm = () => {
  emit('confirm')
}

const cancel = () => {
  emit('cancel')
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-container {
  background: white;
  padding: 24px;
  border-radius: 12px;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  text-align: center;
}

.modal-title {
  font-size: 1.25rem;
  margin-bottom: 12px;
  color: #333;
}

.modal-message {
  font-size: 0.95rem;
  color: #666;
  margin-bottom: 24px;
}

.modal-buttons {
  display: flex;
  justify-content: center;
  gap: 12px;
}

button {
  padding: 8px 24px;
  border-radius: 8px;
  border: none;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.2s;
}

button:hover {
  opacity: 0.8;
}

.cancel-btn {
  background: #e2e8f0;
  color: #475569;
}

.confirm-btn.primary {
  background: #007bff;
  color: white;
}

.confirm-btn.danger {
  background: #ef4444;
  color: white;
}

/* Transitions */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.2s;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>
