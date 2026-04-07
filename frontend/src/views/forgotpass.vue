<template>
  <main ref="mainRef">
    <div class="login-container" ref="containerRef">

      <!-- LEFT SIDE -->
      <div class="left-container" ref="leftRef">
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
              ref="submitBtnRef"
            >
              Enter
            </LoadingButton>
          </div>
            <div ref="backBtnRef">
            <router-link to="/login" class="back-button">
              Back
            </router-link>
          </div>
          </div>

        </form>
      </div>

      <!-- RIGHT SIDE -->
      <div class="right-container" ref="rightRef">
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
const containerRef = ref(null)
const leftRef = ref(null)
const rightRef = ref(null)
const titleRef = ref(null)
const subtitleRef = ref(null)
const emailRef = ref(null)
const submitBtnRef = ref(null)
const backBtnRef = ref(null)

const features = ['24/7 Available', 'Student Privacy', 'Fully Confidential']
const featureRefs = ref([])
const setFeatureRef = el => { if (el) featureRefs.value.push(el) }

/* =========================
   GSAP ANIMATION
========================= */
onMounted(() => {
  featureRefs.value = [] // 🔥 reset refs

  const tl = gsap.timeline({ defaults: { duration: 0.8, ease: 'power2.out' } })

  tl.from(leftRef.value, { x: -50, opacity: 0 }, 0)
  tl.from(rightRef.value, { x: 50, opacity: 0 }, 0)

  tl.from(titleRef.value, { y: 30, opacity: 0 }, 0.2)
  tl.from(subtitleRef.value, { y: 20, opacity: 0 }, 0.3)
  tl.from(emailRef.value, { y: 20, opacity: 0 }, 0.4)

  tl.from([submitBtnRef.value, backBtnRef.value], {
    y: 10,
    opacity: 0,
    stagger: 0.1
  }, 0.5)

  tl.from(".overlay", { opacity: 0 }, 0.3)
  tl.from(".title", { y: 50, opacity: 0 }, 0.4)
  tl.from(".subtitle", { y: 25, opacity: 0 }, 0.5)
  tl.from(".yellow-line", { width: 0, opacity: 0 }, 0.6)
  tl.from(".subheading", { y: 25, opacity: 0 }, 0.7)

  if (featureRefs.value.length) {
    tl.from(featureRefs.value, {
      y: 25,
      opacity: 0,
      stagger: 0.2
    }, 0.9)
  }
})

/* =========================
   FORGOT PASSWORD LOGIC (YOURS ✅)
========================= */
const handleForgotPassword = async () => {
  if (isLoading.value) return // 🔒 prevent double click

  if (!email.value) {
    toast.error('Please enter your email!')
    return
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailPattern.test(email.value)) {
    toast.error('Please enter a valid email!')
    return
  }

  isLoading.value = true // ✅ start loading

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
    isLoading.value = false // ✅ stop loading
  }
}
</script>

<style scoped src="../assets/login/forgotPass.css"></style>