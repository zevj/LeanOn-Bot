<template>
  <main ref="mainRef">
    <div class="login-container" ref="containerRef">

      <div class="left-container" ref="leftRef">
        <h1 class="login-title" ref="titleRef">
          Welcome <span>LeanOn Bot</span>
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
            ref="forgotRef"
          >
            Forgot Password?
          </router-link>

          <button type="submit" class="login-button" ref="loginBtnRef">
            Sign In
          </button>

          <div class="divider" ref="dividerRef">
            <span></span>
            <p>or</p>
            <span></span>
          </div>

          <button type="button" class="google-signin" ref="googleBtnRef">
            Sign in with Google Account
          </button>

          <p class="new-student" ref="studentRef">
            New Student?
            <router-link to="/signup">Learn more about LeanOn Bot</router-link>
          </p>

        </form>
      </div>

      <!-- RIGHT SIDE -->
      <div class="right-container" ref="rightRef">
        <div class="overlay" ref="overlayRef"></div>

        <div class="headings" ref="headingRef">
          <h1 class="title" ref="rightTitleRef">
            LeanOn <span>Bot</span>
          </h1>

          <p class="subtitle" ref="rightSubtitleRef">
            Always There. Always Ready.
          </p>

          <div class="yellow-line" ref="lineRef"></div>

          <p class="subheading" ref="subheadingRef">
            An AI-Assisted Mental Health Wellness Support System for Students
          </p>
        </div>

        <div class="footer" ref="footerRef">
          <div class="footer-container">

            <div class="features" ref="featureRefs">
              <div class="green-circle"></div>
              <p class="feature-text">24/7 Available</p>
            </div>

            <div class="features" ref="featureRefs">
              <div class="green-circle"></div>
              <p class="feature-text">Student Privacy</p>
            </div>

            <div class="features" ref="featureRefs">
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
import { gsap } from 'gsap'

const username = ref('')
const password = ref('')
const showPassword = ref(false)

const toast = useToast()
const router = useRouter()

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const handleLogin = () => {
  if (!username.value || !password.value) {
    toast.error('Please enter both email and password!')
    return
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailPattern.test(username.value)) {
    toast.error('Please enter a valid email address!')
    return
  }

  if (username.value === 'test@example.com' && password.value === '123456') {
    toast.success('Login successful!')
    router.push('/dashboard')
  } else {
    toast.error('Invalid email or password!')
  }
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

/* =========================
   GSAP ANIMATION
========================= */
onMounted(() => {
  const tl = gsap.timeline({
    defaults: {
      duration: 0.8,
      ease: "power2.out"
    }
  })

  // show container (no flicker)
  gsap.set(containerRef.value, { opacity: 1 })

  /* LEFT + RIGHT (SYNC) */
  tl.from(leftRef.value, {
    x: -70,
    opacity: 0
  }, 0)

  tl.from(rightRef.value, {
    x: 70,
    opacity: 0
  }, 0)

  /* LEFT SIDE */
  tl.from(titleRef.value, {
    y: 40,
    opacity: 0
  }, 0.3)

  tl.from(subtitleRef.value, {
    y: 25,
    opacity: 0
  }, 0.4)

  tl.from(formGroupRefs.value, {
    y: 25,
    opacity: 0,
    stagger: 0.15
  }, 0.5)

  tl.from(".forgot-password", {
    y: 15,
    opacity: 0
  }, 0.7)

  tl.from(loginBtnRef.value, {
    scale: 0.92,
    opacity: 0
  }, 0.8)

  tl.from(".divider", {
    y: 15,
    opacity: 0
  }, 0.9)

  /* ✅ GOOGLE BUTTON (FIXED USING REF) */
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

  tl.from(".new-student", {
    y: 15,
    opacity: 0
  }, 1.1)

  /* RIGHT SIDE */
  tl.from(".overlay", {
    opacity: 0
  }, 0.3)

  tl.from(".title", {
    y: 50,
    opacity: 0
  }, 0.4)

  tl.from(".subtitle", {
    y: 25,
    opacity: 0
  }, 0.5)

  tl.from(".yellow-line", {
    width: 0,
    opacity: 0
  }, 0.6)

  tl.from(".subheading", {
    y: 25,
    opacity: 0
  }, 0.7)

  tl.from(".features", {
    y: 25,
    opacity: 0,
    stagger: 0.2
  }, 0.9)
})
</script>

<style scoped src="../assets/login/Login.css"></style>