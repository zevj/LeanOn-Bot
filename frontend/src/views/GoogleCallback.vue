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

const router = useRouter()
const route = useRoute()
const toast = useToast()

onMounted(() => {
  const { token, user, session_log_id } = route.query

  if (token && user) {
    // Store token and user info
    localStorage.setItem('token', token)
    localStorage.setItem('user', user) // Already JSON string from backend
    if (session_log_id) {
      localStorage.setItem('session_log_id', session_log_id)
    }

    const userData = JSON.parse(user)
    toast.success(`Welcome back, ${userData.first_name || 'Student'}!`)

    // Redirect to appropriate dashboard
    // Terms check is handled by sidebarStudent.vue (shows TermsModal if terms_accepted_at is null)
    // Backend EnsureTermsAccepted middleware also blocks API calls until accepted
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
