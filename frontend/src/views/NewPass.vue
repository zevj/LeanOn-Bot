duoooo 🤸🏻
duoooo 🤸🏻 deleted a message
duoooo 🤸🏻
<template>
  <main ref="mainRef">
    <div class="login-container" ref="containerRef">

      <!-- LEFT SIDE -->
      <div class="left-container" ref="leftRef">
        <h1 class="login-title" ref="titleRef">Forgot your <span>Password?</span></h1>
        <p class="login-subtitle" ref="subtitleRef">Forgot the password, don't worry and we've got you back</p>

        <form class="login-form" @submit.prevent="handleResetPassword" ref="formRef">
          <div class="password-row" ref="passwordRowRef">

            <!-- New Password -->
            <div class="form-group" ref="newPassRef">
              <label for="password">Create New Password</label>
              <div class="input-wrapper">
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
            </div>

            <!-- Confirm Password -->
            <div class="form-group" ref="confirmPassRef">
              <label for="confirmPassword">Confirm Password</label>
              <div class="input-wrapper">
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
            </div>
          </div>

          <div class="group-buttons" ref="buttonsRef">
            <router-link to="/login" class="login-button" ref="enterBtnRef">Enter</router-link>
            <router-link to="/OTPFPass" class="back-button" ref="backBtnRef">Back</router-link>
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
            <div class="features" v-for="(f,i) in features" :key="i" ref="setFeatureRef">
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
import { ref, onMounted, nextTick } from 'vue'
import { gsap } from 'gsap'
import { useToast } from 'vue-toastification'
import { useRouter } from 'vue-router'

// Form fields
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const togglePassword = () => showPassword.value = !showPassword.value
const toggleConfirmPassword = () => showConfirmPassword.value = !showConfirmPassword.value

// Router & Toast
const router = useRouter()
const toast = useToast()

// Animation refs
const containerRef = ref(null)
const leftRef = ref(null)
const rightRef = ref(null)
const titleRef = ref(null)
const subtitleRef = ref(null)
const passwordRowRef = ref(null)
const newPassRef = ref(null)
const confirmPassRef = ref(null)
const buttonsRef = ref(null)
const enterBtnRef = ref(null)
const backBtnRef = ref(null)
const overlayRef = ref(null)
const rightTitleRef = ref(null)
const rightSubtitleRef = ref(null)
const lineRef = ref(null)
const subheadingRef = ref(null)
const featureRefs = ref([])
const setFeatureRef = el => { if(el) featureRefs.value.push(el) }

const features = ['24/7 Available', 'Student Privacy', 'Fully Confidential']

// Handle Reset Password
const handleResetPassword = () => {
  if (!password.value || !confirmPassword.value) {
    toast.error('Please fill in all fields!')
    return
  }
  if (password.value.length < 6) {
    toast.error('Password must be at least 6 characters!')
    return
  }
  if (password.value !== confirmPassword.value) {
    toast.error('Passwords do not match!')
    return
  }
  toast.success('Password successfully reset!')
  router.push('/login')
}

// GSAP Animation
onMounted(async ()=>{
  await nextTick()
  const tl = gsap.timeline({ defaults:{ duration:0.6, ease:"power2.out" } })

  // LEFT SIDE
  tl.from(leftRef.value, { x:-50, opacity:0 }, 0)
  tl.from(titleRef.value, { y:30, opacity:0 }, 0.1)
  tl.from(subtitleRef.value, { y:20, opacity:0 }, 0.2)
  tl.from([newPassRef.value, confirmPassRef.value], { y:20, opacity:0, stagger:0.08 }, 0.3)
  tl.from([enterBtnRef.value, backBtnRef.value], { y:15, opacity:0, stagger:0.08 }, 0.4)

  // RIGHT SIDE
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

  // Footer features
  featureRefs.value.forEach((f,i)=>{
    tl.from(f, { y:20, opacity:0, ease:"power2.out" }, 0.45 + i*0.08)
  })
})
</script>

<style scoped src="../assets/login/forgotPass.css"></style>
