<template>
  <main>
    <div class="login-container">

      <div class="left-container">
        <h1 class="login-title">Welcome <span>LeanOn Bot</span></h1>
        <p class="login-subtitle">Your safe space for mental wellness</p>

        <form class="login-form" @submit.prevent="handleLogin">

          <!-- Email input with icon -->
          <div class="form-group email-group">
            <label for="username">Email</label>
            <div class="input-wrapper">
              <i class="bx bx-envelope email-icon"></i>
              <input
                type="text"
                id="username"
                placeholder="Enter your email"
                v-model="username"
              />
            </div>
          </div>

          <!-- Password input with icon and toggle -->
          <div class="form-group password-group">
            <label for="password">Password</label>
            <div class="input-wrapper password-wrapper">
              <i class="bx bx-lock password-icon"></i>
              <input
                :type="showPassword ? 'text' : 'password'"
                id="password"
                placeholder="Enter your password"
                v-model="password"
              />
              <i
                :class="showPassword ? 'bx bx-show' : 'bx bx-hide'"
                class="eye-icon"
                @click="togglePassword"
              ></i>
            </div>
          </div>

          <router-link to="/forgotPass" class="forgot-password">Forgot Password?</router-link>

          <button type="submit" class="login-button">Sign In</button>

          <div class="divider">
            <span></span>
            <p>or</p>
            <span></span>
          </div>

          <button type="button" class="google-signin">
            Sign in with Google Account
          </button>

          <p class="new-student">
            New Student? <router-link to="/signup">Learn more about LeanOn Bot</router-link>
          </p>

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
// import { GoogleLogin } from 'vue3-google-login'

const username = ref('')
const password = ref('')
const showPassword = ref(false)

const toast = useToast()
const router = useRouter()

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const handleLogin = async () => {
  if (!username.value || !password.value) {
    toast.error('Please enter both email and password!')
    return
  }

  try {
    const res = await axios.post('http://127.0.0.1:8000/api/login', {
      email: username.value,
      password: password.value
    })

    console.log(res.data) // debug
    localStorage.setItem('token', res.data.token)
    localStorage.setItem('user', JSON.stringify(res.data.user))
    
    toast.success('Login successful!')

    const role = res.data.user.role || 'student'
    
    if (role === 'guidance') {
      router.push('/adminDashboard')
    } else {
      router.push('/Dashboard')
    }

  } catch (err) {
    toast.error(err.response?.data?.message || 'Login failed!')
  }
}

// const handleGoogleLogin = async (response) => {
//   try {
//     const res = await axios.post('http://127.0.0.1:8000/api/google-login', {
//       token: response.credential
//     })

//     // ✅ store token
//     localStorage.setItem('token', res.data.token)
//     localStorage.setItem('user', JSON.stringify(res.data.user))

//     toast.success('Google login successful!')
//     router.push('/dashboard')

//   } catch (err) {
//     toast.error(err.response?.data?.message || 'Google login failed')
//   }
// }
</script>

<style scoped src="../assets/login/Login.css"></style>