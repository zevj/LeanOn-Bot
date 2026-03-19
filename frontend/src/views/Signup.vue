<template>
  <main>
    <div class="login-container">

      <div class="left-container">
        <h1 class="login-title">Learn more about <span>LeanOn Bot</span></h1>
        <p class="login-subtitle">Sign up for better experience and exploration</p>

        <form class="login-form" @submit.prevent="handleSignup">

          <!-- First Name -->
          <div class="form-group input-wrapper">
            <label for="firstName">First Name</label>
            <i class="bx bx-user input-icon"></i>
            <input
              type="text"
              id="firstName"
              placeholder="Enter your first name..."
              v-model="firstName"
            />
          </div>

          <!-- Last Name -->
          <div class="form-group input-wrapper">
            <label for="lastName">Last Name</label>
            <i class="bx bx-user input-icon"></i>
            <input
              type="text"
              id="lastName"
              placeholder="Enter your last name..."
              v-model="lastName"
            />
          </div>

          <!-- Email -->
          <div class="form-group input-wrapper">
            <label for="email">Email Address</label>
            <i class="bx bx-envelope input-icon"></i>
            <input
              type="text"
              id="email"
              placeholder="Enter your email..."
              v-model="email"
            />
          </div>

          <!-- Password -->
          <div class="form-group input-wrapper">
            <label for="password">Create Password</label>
            <i class="bx bx-lock input-icon"></i>
            <input
              :type="showPassword ? 'text' : 'password'"
              id="password"
              placeholder="Enter your new password..."
              v-model="password"
            />
            <i
              :class="showPassword ? 'bx bx-show eye-icon' : 'bx bx-hide eye-icon'"
              @click="togglePassword"
            ></i>
          </div>

          <!-- Confirm Password -->
          <div class="form-group input-wrapper">
            <label for="confirmPassword">Confirm Password</label>
            <i class="bx bx-lock input-icon"></i>
            <input
              :type="showConfirmPassword ? 'text' : 'password'"
              id="confirmPassword"
              placeholder="Confirm your password..."
              v-model="confirmPassword"
            />
            <i
              :class="showConfirmPassword ? 'bx bx-show eye-icon' : 'bx bx-hide eye-icon'"
              @click="toggleConfirmPassword"
            ></i>
          </div>

          <div class="group-buttons">
            <button type="submit" class="login-button">Enter</button>
            <router-link to="/login" class="back-button">Back</router-link>
          </div>

          <div class="divider">
            <span></span>
            <p>or</p>
            <span></span>
          </div>

          <button type="button" class="google-signin">
            Sign in with Google Account
          </button>

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

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const toast = useToast()
const router = useRouter()

const togglePassword = () => showPassword.value = !showPassword.value
const toggleConfirmPassword = () => showConfirmPassword.value = !showConfirmPassword.value

const handleSignup = () => {
  if (!firstName.value || !lastName.value || !email.value || !password.value || !confirmPassword.value) {
    toast.error('Please fill out all fields!')
    return
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailPattern.test(email.value)) {
    toast.error('Please enter a valid email!')
    return
  }

  if (password.value !== confirmPassword.value) {
    toast.error('Passwords do not match!')
    return
  }

  toast.success('Signup successful! Redirecting to OTP verification...')
  router.push('/OTPVerification')
}
</script>

<style scoped src="../assets/login/signup.css"></style>