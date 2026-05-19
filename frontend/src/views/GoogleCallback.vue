<template>
  <div class="callback-container">
    <div class="loading-spinner">
      <div class="spinner"></div>
      <p>Completing authentication...</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'

import { decryptPayload } from '@/utils/crypto.js'

const router = useRouter()
const route = useRoute()
const toast = useToast()

onMounted(async () => {
  const { payload, token: directToken, user: directUser, session_log_id: directSessionLogId } = route.query

  if (payload) {
    try {
      const decrypted = await decryptPayload(payload)
      const { token, user, session_log_id } = decrypted

      if (token && user) {
        localStorage.setItem('token', token)
        localStorage.setItem('user', user) // Already JSON string from backend
        if (session_log_id) {
          localStorage.setItem('session_log_id', session_log_id)
        }

        const userData = JSON.parse(user)
        toast.success(`Welcome back, ${userData.first_name || 'Student'}!`)

        if (userData.role === 'guidance') {
          router.push('/adminDashboard')
        } else {
          router.push('/ChatConvo')
        }
      } else {
        toast.error('Authentication failed. Missing secure token.')
        router.push('/login')
      }
    } catch (err) {
      console.error('Secure decryption failed:', err)
      toast.error('Authentication failed. Decryption error.')
      router.push('/login')
    }
  } else if (directToken && directUser) {
    // Fallback to direct parameters if encryption is bypassed or disabled
    localStorage.setItem('token', directToken)
    localStorage.setItem('user', directUser)
    if (directSessionLogId) {
      localStorage.setItem('session_log_id', directSessionLogId)
    }

    const userData = JSON.parse(directUser)
    toast.success(`Welcome back, ${userData.first_name || 'Student'}!`)

    if (userData.role === 'guidance') {
      router.push('/adminDashboard')
    } else {
      router.push('/ChatConvo')
    }
  } else {
    toast.error('Authentication failed. Missing token.')
    router.push('/login')
  }
})
</script>

<style scoped>
.callback-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background-color: #f8f9fa;
}

.loading-spinner {
  text-align: center;
}

.spinner {
  border: 4px solid rgba(0, 0, 0, 0.1);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border-left-color: #ffd700;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
