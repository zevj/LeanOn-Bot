<template>
  <OtpModal
    v-model="showOtp"
    :email="username"
    @verified="onOtpVerified"
  />

  <main ref="mainRef">
    <div class="login-container" ref="containerRef">

      <!-- Blur orb decorations — CSS hides these on desktop, shows on mobile -->
      <div class="mobile-bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
        <div class="gradient-circle"></div>
      </div>

      <div class="left-container" ref="leftRef">

        <!-- Mobile-only brand badge (replaces hidden right panel branding) -->
        <div class="mobile-brand" ref="mobileBrandRef">
          <div class="mobile-brand-dot"></div>
          <span class="mobile-brand-label">LeanOn Bot &mdash; Mental Wellness Support</span>
        </div>

        <h1 class="login-title" ref="titleRef">
          Welcome to 
          <router-link to="/" class="logo-link">
            <span>LeanOn Bot</span>
          </router-link>
        </h1>

        <p class="login-subtitle" ref="subtitleRef">
          Your safe space for mental wellness
        </p>

        <form class="login-form" @submit.prevent="handleLogin" ref="formRef">

          <div class="form-group email-group" ref="formGroupRefs">
            <label for="username">Email</label>
            <div class="input-wrapper">
              <i class="bx bx-envelope email-icon"></i>
              <input
                type="text"
                id="username"
                placeholder="Enter your email"
                v-model="username"
                ref="emailInputRef"
              />
            </div>
          </div>

          <div class="form-group password-group" ref="formGroupRefs">
            <label for="password">Password</label>
            <div class="input-wrapper password-wrapper">
              <i class="bx bx-lock password-icon"></i>
              <input
                :type="showPassword ? 'text' : 'password'"
                id="password"
                placeholder="Enter your password"
                v-model="password"
                ref="passwordInputRef"
              />
              <i
                :class="showPassword ? 'bx bx-show' : 'bx bx-hide'"
                class="eye-icon"
                @click="togglePassword"
                ref="eyeRef"
              ></i>
            </div>
          </div>

          <router-link
            to="/forgotPass"
            class="forgot-password"
          >
            Forgot Password?
          </router-link>

          <div ref="loginBtnRef">
            <LoadingButton
              :loading="isLoading"
              type="submit"
              class="login-button"
            >
              Sign In
            </LoadingButton>
          </div>

          <div class="divider">
            <span></span>
            <p>or</p>
            <span></span>
          </div>

          <button type="button" class="google-signin" @click="loginWithGoogle" ref="googleBtnRef">
            Sign in with Google Account
          </button>

        </form>
      </div>

      <!-- RIGHT SIDE — unchanged, hidden via CSS on mobile -->
      <div class="right-container" ref="rightRef">
        <div class="overlay"></div>

        <div class="headings">
          <h1 class="title">
            LeanOn <span>Bot</span>
          </h1>

          <p class="subtitle">
            Always There. Always Ready.
          </p>

          <div class="yellow-line"></div>

          <p class="subheading">
            An AI-Assisted Mental Health Wellness Support System for Gordon College Students
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
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { gsap } from 'gsap'
import LoadingButton from '@/views/loadingButton.vue'
import OtpModal from '@/components/OtpModal.vue'

const showOtp = ref(false)

const username = ref('')
const password = ref('')
const showPassword = ref(false)
const isLoading = ref(false)

const toast = useToast()
const router = useRouter()

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

/* =========================
   LOGIN LOGIC
========================= */
const handleLogin = async () => {
  if (isLoading.value) return

  if (!username.value || !password.value) {
    toast.error('Please enter both email and password!')
    return
  }

  isLoading.value = true

  try {
    const res = await axios.post('/api/login', {
      email: username.value,
      password: password.value
    })

    if (res.data.status === 'OTP_REQUIRED') {
      // First-time login: store temp token, show OTP modal
      localStorage.setItem('token', res.data.token)
      localStorage.setItem('user', JSON.stringify(res.data.user))
      showOtp.value = true
      toast.success('OTP sent to your email!')
    } else {
      // Already verified: normal login
      localStorage.setItem('token', res.data.token)
      localStorage.setItem('user', JSON.stringify(res.data.user))
      const role = res.data.user?.role || 'student'
      role === 'guidance' ? router.push('/adminDashboard') : router.push('/ChatConvo')
    }

  } catch (err) {
    toast.error(err.response?.data?.message || 'Login failed!')
  } finally {
    isLoading.value = false
  }
}

const onOtpVerified = (data) => {
  // Update token/user from verify response if provided
  if (data?.token) {
    localStorage.setItem('token', data.token)
  }
  if (data?.user) {
    localStorage.setItem('user', JSON.stringify(data.user))
  }
  const user = data?.user || JSON.parse(localStorage.getItem('user'))
  const role = user?.role || 'student'
  role === 'guidance' ? router.push('/adminDashboard') : router.push('/ChatConvo')
}

/* =========================
   GOOGLE LOGIN
========================= */
const loginWithGoogle = () => {
  // Redirect to backend's Google auth endpoint
  const baseURL = axios.defaults.baseURL || 'http://127.0.0.1:8000'
  window.location.href = `${baseURL}/api/auth/google/redirect`
}

/* =========================
   REFS
========================= */
const containerRef = ref(null)
const leftRef = ref(null)
const rightRef = ref(null)

const titleRef = ref(null)
const subtitleRef = ref(null)

const formGroupRefs = ref([])

const loginBtnRef = ref(null)
const googleBtnRef = ref(null)
const mobileBrandRef = ref(null)

/* =========================
   GSAP ANIMATION
========================= */
onMounted(() => {
  const isMobile = window.innerWidth <= 768

  const tl = gsap.timeline({
    defaults: {
      duration: 0.8,
      ease: 'power2.out'
    }
  })

  gsap.set(containerRef.value, { opacity: 1 })

  if (isMobile) {
    // Fade in orbs on mobile
    gsap.from('.orb', {
      opacity: 0,
      scale: 0.6,
      duration: 1.4,
      stagger: 0.25,
      ease: 'power2.out'
    })

    // Animate the moving gradient circle
    gsap.from('.gradient-circle', {
      opacity: 0,
      scale: 0.5,
      duration: 1.8,
      ease: 'power2.out',
      delay: 0.3
    })

    // Animate brand badge
    if (mobileBrandRef.value) {
      tl.from(mobileBrandRef.value, { y: -12, opacity: 0 }, 0.1)
    }

    tl.from(leftRef.value, { y: 28, opacity: 0 }, 0)
  } else {
    // Original desktop animations — unchanged
    tl.from(leftRef.value, { x: -70, opacity: 0 }, 0)
    tl.from(rightRef.value, { x: 70, opacity: 0 }, 0)
  }

  tl.from(titleRef.value, { y: 40, opacity: 0 }, 0.3)
  tl.from(subtitleRef.value, { y: 25, opacity: 0 }, 0.4)

  tl.from(formGroupRefs.value, {
    y: 25,
    opacity: 0,
    stagger: 0.15
  }, 0.5)

  tl.from('.forgot-password', { y: 15, opacity: 0 }, 0.7)

  tl.from(loginBtnRef.value, {
    scale: 0.92,
    opacity: 0
  }, 0.8)

  tl.from('.divider', { y: 15, opacity: 0 }, 0.9)

  gsap.set(googleBtnRef.value, {
    opacity: 0,
    y: 20,
    scale: 0.95
  })

  tl.to(googleBtnRef.value, {
    opacity: 1,
    y: 0,
    scale: 1
  }, 1.0)

  tl.from('.new-student', { y: 15, opacity: 0 }, 1.1)

  if (!isMobile) {
    tl.from('.overlay', { opacity: 0 }, 0.3)
    tl.from('.title', { y: 50, opacity: 0 }, 0.4)
    tl.from('.subtitle', { y: 25, opacity: 0 }, 0.5)
    tl.from('.yellow-line', { width: 0, opacity: 0 }, 0.6)
    tl.from('.subheading', { y: 25, opacity: 0 }, 0.7)

    tl.from('.features', {
      y: 25,
      opacity: 0,
      stagger: 0.2
    }, 0.9)
  }

  // Handle Google Auth errors from URL
  const urlParams = new URLSearchParams(window.location.search)
  const error = urlParams.get('error')
  if (error) {
    if (error === 'invalid_domain') {
      toast.error('Only Gordon College email accounts are allowed.')
    } else if (error === 'user_not_found') {
      toast.error('Account not found. Please register first.')
    } else {
      toast.error('Google authentication failed.')
    }
  }
})
</script>

<style scoped src="../assets/login/Login.css"></style>