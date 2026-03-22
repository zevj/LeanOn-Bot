<template>
  <main ref="mainRef">
    <div class="login-container" ref="containerRef">

      <!-- LEFT SIDE -->
      <div class="left-container" ref="leftRef">
        <h1 class="login-title" ref="titleRef">
          Learn more about <span>LeanOn Bot</span>
        </h1>
        <p class="login-subtitle" ref="subtitleRef">
          Sign up for better experience and exploration
        </p>

        <form class="login-form" @submit.prevent="handleSignup" ref="formRef">

          <!-- First Name -->
          <div class="form-group input-wrapper" ref="firstNameRef">
            <label for="firstName">First Name</label>
            <i class="bx bx-user input-icon"></i>
            <input
              type="text"
              id="firstName"
              placeholder="Enter your first name..."
              v-model="firstName"
              @input="firstName = firstName.replace(/[^a-zA-Z]/g, '')"
            />
          </div>

          <!-- Last Name -->
          <div class="form-group input-wrapper" ref="lastNameRef">
            <label for="lastName">Last Name</label>
            <i class="bx bx-user input-icon"></i>
            <input type="text" id="lastName" placeholder="Enter your last name..." v-model="lastName" @input="lastName = lastName.replace(/[^a-zA-Z]/g, '')"/>
          </div>

          <!-- Email -->
          <div class="form-group input-wrapper" ref="emailRef">
            <label for="email">Email Address</label>
            <i class="bx bx-envelope input-icon"></i>
            <input type="text" id="email" placeholder="Enter your email..." v-model="email"/>
          </div>

          <!-- Password Row -->
          <div class="password-row">

            <!-- Password -->
            <div class="form-group input-wrapper" ref="passwordRef">
              <label for="password">Create Password</label>
              <i class="bx bx-lock input-icon"></i>
              <input :type="showPassword ? 'text' : 'password'" id="password" placeholder="Enter your new password..." v-model="password"/>
              <i :class="showPassword ? 'bx bx-show eye-icon' : 'bx bx-hide eye-icon'" @click="togglePassword"></i>
            </div>

            <!-- Confirm Password -->
            <div class="form-group input-wrapper" ref="confirmPasswordRef">
              <label for="confirmPassword">Confirm Password</label>
              <i class="bx bx-lock input-icon"></i>
              <input :type="showConfirmPassword ? 'text' : 'password'" id="confirmPassword" placeholder="Confirm your password..." v-model="confirmPassword"/>
              <i :class="showConfirmPassword ? 'bx bx-show eye-icon' : 'bx bx-hide eye-icon'" @click="toggleConfirmPassword"></i>
            </div>

          </div>

          <div class="group-buttons">
            <button type="submit" class="login-button" ref="signupBtnRef">Enter</button>

            <div ref="backBtnRef">
               <router-link to="/login" class="back-button" ref="backBtnRef">Back</router-link>
            </div>
            
          </div>

          <div class="divider" ref="dividerRef">
            <span></span>
            <p>or</p>
            <span></span>
          </div>

          <button type="button" class="google-signin" ref="googleBtnRef">
            Sign in with Google Account
          </button>

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

        <div class="footer">
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

/* FORM DATA */
const firstName = ref('')
const lastName = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)

/* REFS */
const containerRef = ref(null)
const leftRef = ref(null)
const rightRef = ref(null)
const titleRef = ref(null)
const subtitleRef = ref(null)
const signupBtnRef = ref(null)
const backBtnRef = ref(null)
const dividerRef = ref(null)
const googleBtnRef = ref(null)
const overlayRef = ref(null)
const rightTitleRef = ref(null)
const rightSubtitleRef = ref(null)
const subheadingRef = ref(null)
const lineRef = ref(null)

/* INPUT REFS */
const firstNameRef = ref(null)
const lastNameRef = ref(null)
const emailRef = ref(null)
const passwordRef = ref(null)
const confirmPasswordRef = ref(null)

/* FEATURE REFS */
const featureRefs = ref([])
const setFeatureRef = el => { if(el) featureRefs.value.push(el) }
const features = ['24/7 Available', 'Student Privacy', 'Fully Confidential']

/* TOAST + ROUTER */
const toast = useToast()
const router = useRouter()

/* TOGGLE PASSWORD */
const togglePassword = () => showPassword.value = !showPassword.value
const toggleConfirmPassword = () => showConfirmPassword.value = !showConfirmPassword.value

/* SIGNUP HANDLER */
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

/* GSAP ANIMATION */
onMounted(() => {
  const tl = gsap.timeline({ defaults: { duration: 0.8, ease: 'power2.out' } })

  gsap.set(containerRef.value, { opacity: 1 })
  gsap.set(googleBtnRef.value, { opacity: 0, y: 20, scale: 0.95 })

  // LEFT + RIGHT CONTAINERS
  tl.from(leftRef.value, { x: -70, opacity: 0 }, 0)
  tl.from(rightRef.value, { x: 70, opacity: 0 }, 0)

  // LEFT SIDE
  tl.from(titleRef.value, { y: 40, opacity: 0 }, 0.3)
  tl.from(subtitleRef.value, { y: 25, opacity: 0 }, 0.4)

  const inputs = [firstNameRef.value, lastNameRef.value, emailRef.value, passwordRef.value, confirmPasswordRef.value]
  inputs.forEach((input, i) => tl.from(input, { y: 30, opacity: 0, ease: 'power2.out' }, 0.5 + i*0.1))

  // Buttons & Divider
  tl.from(signupBtnRef.value, { scale: 0.95, opacity: 0 }, 0.8)
  tl.from(backBtnRef.value, { y: 15, opacity: 0 }, 0.85)
  tl.from(dividerRef.value, { y: 15, opacity: 0 }, 0.9)
  tl.to(googleBtnRef.value, { opacity: 1, y: 0, scale: 1 }, 1.0)

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

  featureRefs.value.forEach((f, i) => tl.from(f, { y: 25, opacity: 0, ease: 'power2.out' }, 0.9 + i*0.1))
})
</script>

<style scoped src="../assets/login/signup.css"></style>