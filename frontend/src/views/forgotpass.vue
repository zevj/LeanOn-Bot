<template>
  <main>
    <div class="login-container">

      <div class="left-container">
        <h1 class="login-title">Forgot your <span>Password?</span></h1>
        <p class="login-subtitle">Forgot the password, don't worry and we've got you back</p>

        <!-- Forgot Password Form -->
        <form class="login-form" @submit.prevent="handleForgotPassword">

          <div class="form-group email-group">
            <label for="email">Email</label>
            <div class="input-wrapper">
              <i class="bx bx-envelope email-icon"></i>
              <input
                type="text"
                id="email"
                placeholder="Enter your email"
                v-model="email"
              />
            </div>
          </div>

          <!-- Submit button -->
          <button type="submit" class="login-button">Enter</button>
          <router-link to="/login" class="back-button">Back</router-link>

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
import { useRouter } from 'vue-router'
import axios from 'axios'

// Form field
const email = ref('')

// Router & Toast
const router = useRouter()
const toast = useToast()

const handleForgotPassword = async () => {
  if (!email.value) {
    toast.error('Please enter your email!')
    return
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailPattern.test(email.value)) {
    toast.error('Please enter a valid email!')
    return
  }

  try {
    const response = await axios.post('/api/forgot-password/send-otp', {
      email: email.value
    })

    localStorage.setItem('otp_expiry', response.data.expires_at)
    localStorage.setItem('reset_email', email.value)
    toast.success('OTP sent!')

    // pass email to next page
    router.push({
      path: '/OTPFPass',
      query: { email: email.value }
    })

  } catch (error) {
    console.log(error) // 👈 ADD
  console.log(error.response) // 👈 ADD
    toast.error(error.response?.data?.message || 'Error sending OTP')
  }
}
</script>

<style scoped src="../assets/login/forgotPass.css"></style>