<template>
  <teleport to="body">
    <transition name="modal">
      <div v-if="visible" class="confirmation-overlay" @click="cancel">
        <div class="confirmation-container" @click.stop>
          <h3 class="confirmation-title">{{ title }}</h3>
          <p class="confirmation-message">{{ message }}</p>
          <div class="confirmation-buttons">
            <button class="confirmation-cancel-btn" @click="cancel">{{ cancelText }}</button>
            <button :class="['confirmation-confirm-btn', type]" @click="confirm">{{ confirmText }}</button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

defineProps({
  visible: { type: Boolean, required: true },
  title: { type: String, default: 'Confirm Action' },
  message: { type: String, default: 'Are you sure you want to proceed?' },
  confirmText: { type: String, default: 'Confirm' },
  cancelText: { type: String, default: 'Cancel' },
  type: { type: String, default: 'primary' }
})

const emit = defineEmits(['confirm', 'cancel'])
const confirm = () => emit('confirm')
const cancel = () => emit('cancel')
</script>

<style scoped>
.confirmation-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999999;
}

.confirmation-container {
  background: white;
  padding: 28px 24px;
  border-radius: 14px;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
  text-align: center;
}

.confirmation-title {
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: 10px;
  color: #1a1a1a;
}

.confirmation-message {
  font-size: 0.92rem;
  color: #666;
  margin-bottom: 24px;
  line-height: 1.5;
}

.confirmation-buttons {
  display: flex;
  justify-content: center;
  gap: 12px;
}

.confirmation-cancel-btn {
  padding: 9px 24px;
  border-radius: 8px;
  border: none;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  background: #e2e8f0;
  color: #475569;
  transition: background 0.15s;
}

.confirmation-cancel-btn:hover {
  background: #cbd5e1;
}

.confirmation-confirm-btn {
  padding: 9px 24px;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  color: white;
  transition: opacity 0.15s;
}

.confirmation-confirm-btn:hover {
  opacity: 0.88;
}

.confirmation-confirm-btn.primary {
  background: #0E6008;
}

.confirmation-confirm-btn.danger {
  background: #ef4444;
}

/* Transition */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-active .confirmation-container,
.modal-leave-active .confirmation-container {
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .confirmation-container,
.modal-leave-to .confirmation-container {
  transform: scale(0.95);
  opacity: 0;
}
</style>