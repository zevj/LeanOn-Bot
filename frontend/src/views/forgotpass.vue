<template>
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

      <!-- LEFT SIDE -->
      <div class="left-container" ref="leftRef">

        <!-- Mobile-only brand badge (replaces hidden right panel branding) -->
        <div class="mobile-brand" ref="mobileBrandRef">
          <div class="mobile-brand-dot"></div>
          <span class="mobile-brand-label">LeanOn Bot &mdash; Mental Wellness Support</span>
        </div>

        <h1 class="login-title" ref="titleRef">Forgot your <span>Password?</span></h1>
        <p class="login-subtitle" ref="subtitleRef">Don't worry and we've got you back</p>

        <form class="login-form" @submit.prevent="handleForgotPassword">

          <!-- Email -->
          <div class="form-group email-group" ref="emailRef">
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

          <div class="group-buttons">
            <div ref="submitBtnRef">
              <LoadingButton
                type="submit"
                class="login-button"
                :loading="isLoading"
              >
                Enter
              </LoadingButton>
            </div>
            <div ref="backBtnRef">
            <router-link to="/login" class="back-button">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M5 12l7 7M5 12l7-7"/>
              </svg>
              <span>Back</span>
            </router-link>
            </div>
          </div>

        </form>
      </div>

      <!-- RIGHT SIDE -->
      <div class="right-container" ref="rightRef">
        <div class="overlay"></div>

         <!-- GC Logo Badge — top-right of right panel -->
        <div class="app-logo-badge" ref="appLogoRef">
        <img src="/leanOnBot.png" alt="Gordon College" class="app-logo-img" /></div>

         <!-- GC Logo Badge — top-right of right panel -->
        <div class="gc-logo-badge" ref="gcLogoRef">
        <img src="/gc-logo.png" alt="Gordon College" class="gc-logo-img" /></div>
        
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
            <div
              class="features"
              v-for="(f, i) in features"
              :key="i"
              :ref="setFeatureRef"
            >
              <div class="green-circle"></div>
              <p class="feature-text">{{ f }}</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { gsap } from 'gsap'
import { useToast } from 'vue-toastification'
import { useRouter } from 'vue-router'
import axios from 'axios'
import LoadingButton from '@/views/loadingButton.vue'

const isLoading = ref(false)

// Form field
const email = ref('')

// Router & Toast
const router = useRouter()
const toast = useToast()

/* =========================
   REFS (GSAP)
========================= */
const containerRef   = ref(null)
const leftRef        = ref(null)
const rightRef       = ref(null)
const titleRef       = ref(null)
const subtitleRef    = ref(null)
const emailRef       = ref(null)
const submitBtnRef   = ref(null)
const backBtnRef     = ref(null)
const mobileBrandRef = ref(null)
/* GC LOGO */
const gcLogoRef = ref(null)
const appLogoRef = ref(null)


const features = ['24/7 Available', 'Student Privacy', 'Fully Confidential']
const featureRefs  = ref([])
const setFeatureRef = el => { if (el) featureRefs.value.push(el) }

/* =========================
   GSAP ANIMATION
========================= */
onMounted(() => {
  featureRefs.value = [] // reset refs

  const isMobile = window.innerWidth <= 768

  const tl = gsap.timeline({ defaults: { duration: 0.8, ease: 'power2.out' } })

  gsap.set(containerRef.value, { opacity: 1 })

  if (isMobile) {
    // Fade in orbs
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
    // Desktop — slide in both panels
    tl.from(leftRef.value,  { x: -50, opacity: 0 }, 0)
    tl.from(rightRef.value, { x:  50, opacity: 0 }, 0)
  }

  tl.from(titleRef.value,    { y: 30, opacity: 0 }, 0.2)
  tl.from(subtitleRef.value, { y: 20, opacity: 0 }, 0.3)
  tl.from(emailRef.value,    { y: 20, opacity: 0 }, 0.4)

  tl.from([submitBtnRef.value, backBtnRef.value], {
    y: 10,
    opacity: 0,
    stagger: 0.1
  }, 0.5)

  if (!isMobile) {
    tl.from('.overlay',      { opacity: 0        }, 0.3)
    tl.from('.title',        { y: 50, opacity: 0 }, 0.4)
    tl.from('.subtitle',     { y: 25, opacity: 0 }, 0.5)
    tl.from('.yellow-line',  { width: 0, opacity: 0 }, 0.6)
    tl.from('.subheading',   { y: 25, opacity: 0 }, 0.7)
    tl.from(gcLogoRef.value, { y: -20, opacity: 0, duration: 0.6 }, 0.3)
    tl.from(appLogoRef.value, { y: -20, opacity: 0, duration: 0.6 }, 0.3)


    if (featureRefs.value.length) {
      tl.from(featureRefs.value, {
        y: 25,
        opacity: 0,
        stagger: 0.2
      }, 0.9)
    }
  }
})

/* =========================
   FORGOT PASSWORD LOGIC ✅
========================= */
const handleForgotPassword = async () => {
  if (isLoading.value) return // prevent double click

  if (!email.value) {
    toast.error('Please enter your email!')
    return
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailPattern.test(email.value)) {
    toast.error('Please enter a valid email!')
    return
  }

  isLoading.value = true

  try {
    const response = await axios.post('/api/forgot-password/send-otp', {
      email: email.value
    })

    localStorage.setItem('otp_expiry', response.data.expires_at)
    localStorage.setItem('reset_email', email.value)

    toast.success('OTP sent!')

    router.push({
      path: '/OTPFPass',
      query: { email: email.value }
    })

  } catch (error) {
    console.log(error)
    toast.error(error.response?.data?.message || 'Error sending OTP')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped src="../assets/login/forgotPass.css"></style>