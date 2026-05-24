<template>
  <main>
    <div class="login-container">

      <!-- Blur orb decorations — CSS hides these on desktop, shows on mobile -->
      <div class="mobile-bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
        <div class="gradient-circle"></div>
      </div>

      <!-- LEFT CONTAINER -->
      <div class="left-container">

        <!-- Mobile-only brand badge -->
        <div class="mobile-brand" ref="mobileBrandRef">
          <div class="mobile-brand-dot"></div>
          <span class="mobile-brand-label">LeanOn Bot &mdash; Mental Wellness Support</span>
        </div>

        <h1 class="login-title">Verify your account</h1>
        <p class="login-subtitle">
          The code has been sent to your email, please enter your code.
        </p>

        <form class="login-form" @submit.prevent="handleVerify">

          <div class="otp-container">
            <input class="otp-input" maxlength="1" v-model="otp1" @input="next(1,$event)" ref="i1">
            <input class="otp-input" maxlength="1" v-model="otp2" @input="next(2,$event)" ref="i2">
            <input class="otp-input" maxlength="1" v-model="otp3" @input="next(3,$event)" ref="i3">
            <input class="otp-input" maxlength="1" v-model="otp4" @input="next(4,$event)" ref="i4">
            <input class="otp-input" maxlength="1" v-model="otp5" @input="next(5,$event)" ref="i5">
            <input class="otp-input" maxlength="1" v-model="otp6" @input="next(6,$event)" ref="i6">
          </div>

          <!-- ✅ VERIFY BUTTON -->
          <LoadingButton
            :loading="isVerifying"
            type="submit"
            class="login-button"
          >
            Verify OTP
          </LoadingButton>

          <!-- ✅ RESEND BUTTON -->
          <LoadingButton
            :loading="isResending"
            :disabled="timeLeft > 0"
            @click="resendOtp"
            class="login-button"
          >
            {{ timeLeft > 0 ? 'Resend in ' + timeLeft + 's' : 'Resend OTP' }}
          </LoadingButton>

          <router-link to="/forgotPass" class="back-button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 12H5M5 12l7 7M5 12l7-7"/>
            </svg>
            <span>Back</span>
          </router-link>
        </form>
      </div>

      <!-- RIGHT CONTAINER -->
      <div class="right-container" ref="rightRef">
        <div class="overlay" ref="overlayRef"></div>

         <!-- GC Logo Badge — top-right of right panel -->
        <div class="app-logo-badge" ref="appLogoRef">
        <img src="/leanOnBot.png" alt="Gordon College" class="app-logo-img" /></div>

         <!-- GC Logo Badge — top-right of right panel -->
        <div class="gc-logo-badge" ref="gcLogoRef">
        <img src="/gc-logo.png" alt="Gordon College" class="gc-logo-img" /></div>
        
        <div class="headings">
          <h1 class="title" ref="rightTitleRef">LeanOn <span>Bot</span></h1>
          <p class="subtitle" ref="rightSubtitleRef">Always There. Always Ready.</p>

          <div class="yellow-line" ref="lineRef"></div>

          <p class="subheading" ref="subheadingRef">
            An AI-Assisted Mental Health Wellness Support System for Students
          </p>
        </div>

        <div class="footer">
          <div class="footer-container" ref="footerContainerRef">
            <div class="features" ref="feature1Ref">
              <div class="green-circle"></div>
              <p class="feature-text">24/7 Available</p>
            </div>

            <div class="features" ref="feature2Ref">
              <div class="green-circle"></div>
              <p class="feature-text">Student Privacy</p>
            </div>

            <div class="features" ref="feature3Ref">
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
import { ref, onMounted, onUnmounted } from "vue"
import { useToast } from "vue-toastification"
import { useRouter, useRoute } from "vue-router"
import axios from "axios"
import { gsap } from "gsap"
import LoadingButton from "@/views/loadingButton.vue"

// OTP fields
const otp1 = ref("")
const otp2 = ref("")
const otp3 = ref("")
const otp4 = ref("")
const otp5 = ref("")
const otp6 = ref("")

// Loading states
const isVerifying = ref(false)
const isResending = ref(false)

// Refs for inputs
const i1 = ref(null)
const i2 = ref(null)
const i3 = ref(null)
const i4 = ref(null)
const i5 = ref(null)
const i6 = ref(null)

// Right-side animation refs
const rightRef          = ref(null)
const overlayRef        = ref(null)
const rightTitleRef     = ref(null)
const rightSubtitleRef  = ref(null)
const lineRef           = ref(null)
const subheadingRef     = ref(null)
const feature1Ref       = ref(null)
const feature2Ref       = ref(null)
const feature3Ref       = ref(null)
const mobileBrandRef    = ref(null)   // ← new
/* GC LOGO */
const gcLogoRef = ref(null)
const appLogoRef = ref(null)

const featureRefs = [feature1Ref, feature2Ref, feature3Ref]

const toast  = useToast()
const router = useRouter()
const route  = useRoute()

const timeLeft = ref(0)
let timer = null

/* =========================
   TIMER
========================= */
const startTimer = () => {
  clearInterval(timer)

  const expiry = localStorage.getItem('otp_expiry')
  if (!expiry) return

  timer = setInterval(() => {
    const now        = new Date().getTime()
    const expiryTime = new Date(expiry).getTime()
    const diff       = Math.floor((expiryTime - now) / 1000)

    if (diff > 0) {
      timeLeft.value = diff
    } else {
      timeLeft.value = 0
      clearInterval(timer)
    }
  }, 1000)
}

/* =========================
   INPUT HANDLER
========================= */
const next = (index, e) => {
  e.target.value = e.target.value.replace(/[^0-9]/g, '')
  const inputs = [i1, i2, i3, i4, i5, i6]
  if (e.target.value.length === 1 && index < 6) {
    inputs[index].value.focus()
  }
}

/* =========================
   VERIFY OTP
========================= */
const handleVerify = async () => {
  if (isVerifying.value) return

  const otp = otp1.value + otp2.value + otp3.value + otp4.value + otp5.value + otp6.value

  if (otp.length < 6) {
    toast.error("Please enter the 6-digit OTP correctly!")
    return
  }

  if (timeLeft.value === 0) {
    toast.error('OTP expired. Please resend.')
    return
  }

  isVerifying.value = true

  try {
    await axios.post('/api/forgot-password/verify-otp', {
      email: route.query.email,
      otp: otp
    })

    toast.success("OTP verified!")
    localStorage.setItem('reset_email', route.query.email)
    localStorage.setItem('reset_otp', otp)
    router.push('/NewPass')

  } catch (error) {
    toast.error(error.response?.data?.message || 'Invalid OTP')
  } finally {
    isVerifying.value = false
  }
}

/* =========================
   RESEND OTP
========================= */
const resendOtp = async () => {
  if (isResending.value || timeLeft.value > 0) return

  const email = route.query.email || localStorage.getItem('reset_email')

  if (!email) {
    toast.error('Session expired')
    return
  }

  isResending.value = true

  try {
    const response = await axios.post('/api/forgot-password/send-otp', {
      email: email
    })

    localStorage.setItem('otp_expiry', response.data.expires_at)

    toast.success('OTP resent!')
    startTimer()

  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to resend OTP')
  } finally {
    isResending.value = false
  }
}

/* =========================
   MOUNT
========================= */
onMounted(() => {
  startTimer()

  const isMobile = window.innerWidth <= 768

  if (isMobile) {
    // Fade in orbs
    gsap.from('.orb', {
      opacity: 0,
      scale: 0.6,
      duration: 1.4,
      stagger: 0.25,
      ease: 'power2.out'
    })

        tl.from(gcLogoRef.value, { y: -20, opacity: 0, duration: 0.6 }, 0.3)
        tl.from(appLogoRef.value, { y: -20, opacity: 0, duration: 0.6 }, 0.3)

    // Animate the moving gradient circle
    gsap.from('.gradient-circle', {
      opacity: 0,
      scale: 0.5,
      duration: 1.8,
      ease: 'power2.out',
      delay: 0.3
    })

    // Mobile brand badge
    if (mobileBrandRef.value) {
      gsap.from(mobileBrandRef.value, {
        y: -12,
        opacity: 0,
        duration: 0.8,
        ease: 'power2.out',
        delay: 0.1
      })
    }

  } else {
    // Desktop — original right-side animations, untouched
    gsap.from(overlayRef.value,     { opacity: 0, duration: 0.8 })
    gsap.from([rightTitleRef.value, rightSubtitleRef.value], {
      y: 30, opacity: 0, stagger: 0.15
    })
    gsap.from(lineRef.value,        { width: 0, opacity: 0 })
    gsap.from(subheadingRef.value,  { y: 25, opacity: 0 })
    gsap.from(featureRefs.map(f => f.value), {
      y: 20, opacity: 0, stagger: 0.15
    })
  }
})

onUnmounted(() => {
  clearInterval(timer)
})
</script>

<style scoped src="../assets/login/OTPFPass.css"></style>