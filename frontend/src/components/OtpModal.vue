<template>
  <Teleport to="body">
    <Transition name="backdrop">
      <div v-if="modelValue" class="otp-backdrop" @click.self="$emit('update:modelValue', false)">
        <Transition name="modal-pop">
          <div v-if="modelValue" class="otp-modal">
            <button class="otp-close" @click="$emit('update:modelValue', false)">✕</button>

            <div class="otp-icon-ring">
              <i class="bx bx-lock-alt"></i>
            </div>

            <h2 class="otp-title">Verify Your Identity</h2>
            <p class="otp-sub">
              We sent a 6-digit code to<br>
              <span>{{ maskedEmail }}</span>
            </p>

            <div class="otp-inputs" ref="inputsContainer">
              <input
                v-for="(digit, i) in digits"
                :key="i"
                :ref="el => inputRefs[i] = el"
                type="text"
                maxlength="1"
                inputmode="numeric"
                :class="{ filled: digit !== '' }"
                v-model="digits[i]"
                @input="onInput(i)"
                @keydown="onKeydown($event, i)"
                @paste="onPaste($event)"
              />
            </div>

            <LoadingButton
              :loading="isVerifying"
              class="otp-verify-btn"
              @click="handleVerify"
            >
              Verify & Continue
            </LoadingButton>

            <p class="otp-resend">
              Didn't receive it?
              <a @click="handleResend" :class="{ disabled: timer > 0 }">Resend code</a>
              <span v-if="timer > 0" class="otp-timer">0:{{ String(timer).padStart(2, '0') }}</span>
            </p>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import LoadingButton from '@/views/loadingButton.vue'

const props = defineProps({
  modelValue: Boolean,
  email: String
})

const emit = defineEmits(['update:modelValue', 'verified'])

const toast = useToast()
const digits = ref(Array(6).fill(''))
const inputRefs = ref([])
const isVerifying = ref(false)
const timer = ref(45)
let timerInterval = null

const maskedEmail = computed(() => {
  if (!props.email) return ''
  const [user, domain] = props.email.split('@')
  return user[0] + '*'.repeat(user.length - 1) + '@' + domain
})

const startTimer = () => {
  timer.value = 45
  clearInterval(timerInterval)
  timerInterval = setInterval(() => {
    if (timer.value <= 0) return clearInterval(timerInterval)
    timer.value--
  }, 1000)
}

watch(() => props.modelValue, val => {
  if (val) {
    digits.value = Array(6).fill('')
    startTimer()
    setTimeout(() => inputRefs.value[0]?.focus(), 100)
  }
})

onUnmounted(() => clearInterval(timerInterval))

const onInput = (i) => {
  const val = digits.value[i].replace(/\D/g, '')
  digits.value[i] = val
  if (val && i < 5) inputRefs.value[i + 1]?.focus()
}

const onKeydown = (e, i) => {
  if (e.key === 'Backspace' && !digits.value[i] && i > 0) {
    digits.value[i - 1] = ''
    inputRefs.value[i - 1]?.focus()
  }
}

const onPaste = (e) => {
  e.preventDefault()
  const text = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
  text.split('').forEach((ch, j) => { digits.value[j] = ch })
  inputRefs.value[Math.min(text.length, 5)]?.focus()
}

const handleVerify = async () => {
  const code = digits.value.join('')
  if (code.length < 6) return toast.error('Please enter all 6 digits.')

  isVerifying.value = true
  try {
    const res = await axios.post('/api/verify-otp', {
      email: props.email,
      otp: code
    })
    toast.success(res.data.message || 'Email verified!')
    emit('verified', res.data)
    emit('update:modelValue', false)
  } catch (err) {
    const msg = err.response?.data?.message || 'Invalid or expired code. Try again.'
    toast.error(msg)
    digits.value = Array(6).fill('')
    inputRefs.value[0]?.focus()
  } finally {
    isVerifying.value = false
  }
}

const handleResend = async () => {
  if (timer.value > 0) return
  try {
    await axios.post('/api/resend-otp', { email: props.email })
    toast.success('Code resent!')
    startTimer()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to resend code.')
  }
}
</script>

<style scoped>
.otp-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.otp-modal {
  background: #fff;
  border-radius: 20px;
  padding: 40px 36px 36px;
  width: 380px;
  text-align: center;
  position: relative;
}

.otp-close {
  position: absolute;
  top: 14px; right: 16px;
  width: 28px; height: 28px;
  border-radius: 50%;
  border: none;
  background: #f2f2f2;
  color: #666;
  font-size: 16px;
  cursor: pointer;
  transition: background 0.2s;
}
.otp-close:hover { background: #e0e0e0; }

.otp-icon-ring {
  width: 60px; height: 60px;
  border-radius: 50%;
  background: #e8f5e9;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px;
  font-size: 26px;
  color: #049516;
}

.otp-title {
  font-size: 19px;
  font-weight: 700;
  color: #111;
  margin-bottom: 6px;
}

.otp-sub {
  font-size: 13px;
  color: #666;
  line-height: 1.5;
  margin-bottom: 28px;
}
.otp-sub span { color: #049516; font-weight: 600; }

.otp-inputs {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-bottom: 28px;
}

.otp-inputs input {
  width: 46px; height: 54px;
  border: 2px solid #ddd;
  border-radius: 12px;
  text-align: center;
  font-size: 20px;
  font-weight: 700;
  color: #111;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
  caret-color: #049516;
}
.otp-inputs input:focus {
  border-color: #049516;
  box-shadow: 0 0 0 3px rgba(4,149,22,0.12);
  transform: translateY(-2px);
}
.otp-inputs input.filled {
  border-color: #049516;
  background: #f0faf1;
}

.otp-verify-btn {
  width: 100%;
  height: 50px;
  background: #0E6008;
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s;
  margin-bottom: 18px;
}
.otp-verify-btn:hover { background: #0b4706; }
.otp-verify-btn:active { transform: scale(0.98); }

.otp-resend { font-size: 12px; color: #888; }
.otp-resend a {
  color: #049516; font-weight: 600; cursor: pointer;
}
.otp-resend a.disabled { color: #aaa; cursor: default; }
.otp-resend a:not(.disabled):hover { text-decoration: underline; }

.otp-timer {
  display: inline-block;
  background: #f5f5f5;
  border-radius: 20px;
  padding: 2px 10px;
  font-size: 11px;
  color: #555;
  margin-left: 6px;
  font-weight: 500;
}

/* Backdrop transition */
.backdrop-enter-active, .backdrop-leave-active { transition: opacity 0.3s ease; }
.backdrop-enter-from, .backdrop-leave-to { opacity: 0; }

/* Modal pop transition */
.modal-pop-enter-active {
  transition: opacity 0.4s ease, transform 0.42s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-pop-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.modal-pop-enter-from { opacity: 0; transform: scale(0.78) translateY(24px); }
.modal-pop-leave-to  { opacity: 0; transform: scale(0.92) translateY(10px); }
</style>