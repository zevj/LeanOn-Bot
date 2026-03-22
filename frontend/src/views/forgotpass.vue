duoooo 🤸🏻
duoooo 🤸🏻 deleted a message
duoooo 🤸🏻
<template>
  <main ref="mainRef">
    <div class="login-container" ref="containerRef">

      <!-- LEFT SIDE -->
      <div class="left-container" ref="leftRef">
        <h1 class="login-title" ref="titleRef">Forgot your <span>Password?</span></h1>
        <p class="login-subtitle" ref="subtitleRef">Don't worry and we've got you back</p>

        <form class="login-form" @submit.prevent="handleForgotPassword" ref="formRef">

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

          <!-- Buttons -->
          <div class="group-buttons">
            <router-link to="/OTPFPass" class="login-button" ref="submitBtnRef">Enter</router-link>
            <router-link to="/login" class="back-button" ref="backBtnRef">Back</router-link>
          </div>

        </form>
      </div>

      <!-- RIGHT SIDE -->
      <div class="right-container" ref="rightRef">
        <div class="overlay" ref="overlayRef"></div>

        <div class="headings">
          <h1 class="title" ref="rightTitleRef">LeanOn <span>Bot</span></h1>
          <p class="subtitle" ref="rightSubtitleRef">Always There. Always Ready.</p>

          <div class="yellow-line" ref="lineRef"></div>

          <p class="subheading" ref="subheadingRef">
            An AI-Assisted Mental Health Wellness Support System for Students
          </p>
        </div>

        <div class="footer" ref="footerRef">
          <div class="footer-container">
            <div class="features" v-for="(f, i) in features" :key="i" ref="setFeatureRef">
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

// Form field
const email = ref('')

// Router & Toast
const router = useRouter()
const toast = useToast()

// Refs
const containerRef = ref(null)
const leftRef = ref(null)
const rightRef = ref(null)
const titleRef = ref(null)
const subtitleRef = ref(null)
const emailRef = ref(null)
const submitBtnRef = ref(null)
const backBtnRef = ref(null)
const overlayRef = ref(null)
const rightTitleRef = ref(null)
const rightSubtitleRef = ref(null)
const subheadingRef = ref(null)
const lineRef = ref(null)
const footerRef = ref(null)

// Features
const features = ['24/7 Available', 'Student Privacy', 'Fully Confidential']
const featureRefs = ref([])
const setFeatureRef = el => { if(el) featureRefs.value.push(el) }

// GSAP Animation
onMounted(() => {
  const tl = gsap.timeline({ defaults: { duration: 0.8, ease: 'power2.out' } })

  // Containers
  tl.from(leftRef.value, { x: -50, opacity: 0 }, 0)
  tl.from(rightRef.value, { x: 50, opacity: 0 }, 0)

  // Left side
  tl.from(titleRef.value, { y: 30, opacity: 0 }, 0.2)
  tl.from(subtitleRef.value, { y: 20, opacity: 0 }, 0.3)
  tl.from(emailRef.value, { y: 20, opacity: 0 }, 0.4)
  tl.from([submitBtnRef.value, backBtnRef.value], { y: 10, opacity: 0, stagger: 0.1 }, 0.5)

  // Right side
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

  // Footer Features
  featureRefs.value.forEach((f, i) => {
    tl.from(f, { y: 20, opacity: 0, ease: 'power2.out' }, 0.7 + i*0.1)
  })
})

// Handle Forgot Password
const handleForgotPassword = () => {
  if (!email.value) {
    toast.error('Please enter your email!')
    return
  }
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailPattern.test(email.value)) {
    toast.error('Please enter a valid email!')
    return
  }
  toast.success('OTP sent! Redirecting...')
  router.push('/OTPFPass')
}
</script>

<style scoped src="../assets/login/forgotPass.css"></style>