<template>
  <main>
    <div class="login-container">

      <div class="left-container">
        <h1 class="login-title">Forgot your <span>Password?</span></h1>
        <p class="login-subtitle">Forgot the password, don't worry and we've got you back</p>

        <!-- Forgot Password Form -->
        <form class="login-form" @submit.prevent="handleReset">

<div class="form-group">
  <label>New Password</label>
  <input type="password" v-model="password" />
</div>

<div class="form-group">
  <label>Confirm Password</label>
  <input type="password" v-model="confirmPassword" />
</div>

<button type="submit" class="login-button">Reset Password</button>

</form>

      </div>

      <div class="right-container">
        <div class="overlay"></div>

        <div class="headings">
          <h1 class="title">LeanOn <span>Bot</span></h1>
          <p class="subtitle">Always There. Always Ready.</p>

          <div class="yellow-line"></div>

          <p class="subheading">
            An AI-Assisted Mental Health Wellness Support System for Students
          </p>
        </div>

        <div class="footer">
          <div class="footer-container">
            <div class="features">
              <div class="green-circle"></div>
              <p class="feature-text">24/7 Available</p>
            </div>

            <div class="features">
              <div class="green-circle"></div>
              <p class="feature-text">Student Privacy</p>
            </div>

            <div class="features">
              <div class="green-circle"></div>
              <p class="feature-text">Fully Confidential</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute
const router = useRouter()
const toast = useToast()
const email = localStorage.getItem('reset_email')

const password = ref('')
const confirmPassword = ref('')

const handleReset = async () => {

const email = localStorage.getItem('reset_email') // ✅ get email first

// 🔒 SAFETY CHECK (PUT IT HERE)
if (!email) {
  toast.error('Session expired. Please try again.')
  router.push('/forgotPass')
  return
}

// 1️⃣ Check empty fields
if (!password.value || !confirmPassword.value) {
  toast.error('Please fill in all fields!')
  return
}

// 2️⃣ Minimum password length
if (password.value.length < 6) {
  toast.error('Password must be at least 6 characters!')
  return
}

// 3️⃣ Check match
if (password.value !== confirmPassword.value) {
  toast.error('Passwords do not match!')
  return
}

try {
  await axios.post('/api/forgot-password/reset', {
    email: email, // ✅ use localStorage email
    password: password.value,
    password_confirmation: confirmPassword.value
  })

  toast.success('Password successfully reset!')
  
  // 🧹 CLEANUP
  localStorage.removeItem('otp_expiry')
  localStorage.removeItem('reset_email')

  router.push('/login')

} catch (error) {
  console.log(error.response)
  toast.error(error.response?.data?.message || 'Reset failed')
}
}
</script>


<style scoped src="../assets/login/forgotPass.css"></style>