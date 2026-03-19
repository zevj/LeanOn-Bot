<template>
  <main>
    <div class="login-container">

      <div class="left-container">
        <h1 class="login-title">Forgot your <span>Password?</span></h1>
        <p class="login-subtitle">Forgot the password, don't worry and we've got you back</p>

        <!-- Forgot Password Form -->
      <form class="login-form" @submit.prevent="handleResetPassword">

  <!-- New Password -->
  <div class="form-group">
    <label for="password">Create New Password</label>
    <div class="input-wrapper">
      <input
        type="password"
        id="password"
        placeholder="Enter your New Password..."
        v-model="password"
      />
    </div>
  </div>

  <!-- Confirm Password -->
  <div class="form-group">
    <label for="confirmPassword">Confirm Password</label>
    <div class="input-wrapper">
      <input
        type="password"
        id="confirmPassword"
        placeholder="Confirm your Password..."
        v-model="confirmPassword"
      />
    </div>
  </div>

  <!-- Submit -->
  <router-link to="/login" class="login-button">Enter</router-link>

  <!-- Back -->
  <router-link to="/OTPFPass" class="back-button">Back</router-link>

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

const password = ref('')
const confirmPassword = ref('')

const router = useRouter()
const toast = useToast()

const handleResetPassword = () => {

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

  // 3️⃣ Check if passwords match
  if (password.value !== confirmPassword.value) {
    toast.error('Passwords do not match!')
    return
  }

  // ✅ Real success (no simulation)
  toast.success('Password successfully reset!')

  // Redirect to login
  router.push('/login')
}
</script>


<style scoped src="../assets/login/forgotPass.css"></style>