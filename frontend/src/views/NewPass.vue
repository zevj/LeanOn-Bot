<template>
  <main>
    <div class="login-container" ref="containerRef">

      <!-- LEFT SIDE -->
      <div class="left-container" ref="leftRef">
        <h1 class="login-title" ref="titleRef">
          Forgot your <span>Password?</span>
        </h1>

        <p class="login-subtitle" ref="subtitleRef">
          Forgot the password, don't worry and we've got you back
        </p>

        <!-- ❌ removed submit -->
        <form class="login-form">

          <div class="password-row">

            <!-- New Password -->
            <div class="form-group" ref="newPassRef">
              <label>Create New Password</label>
              <div class="input-wrapper">
                <i class="bx bx-lock input-icon"></i>
                <input
                  :type="showPassword ? 'text' : 'password'"
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
              <label>Confirm Password</label>
              <div class="input-wrapper">
                <i class="bx bx-lock input-icon"></i>
                <input
                  :type="showConfirmPassword ? 'text' : 'password'"
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

          <!-- ✅ BUTTONS -->
          <div class="group-buttons">

            <div ref="enterBtnRef">
              <LoadingButton
                class="login-button"
                :loading="isLoading"
                @click="handleResetPassword"
              >
                Reset Password
              </LoadingButton>
            </div>

            <div ref="backBtnRef">
              <router-link to="/OTPFPass" class="back-button">
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
import axios from 'axios'
import LoadingButton from '@/views/loadingButton.vue'

const router = useRouter()
const toast = useToast()

/* FORM */
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)

/* ✅ LOADING */
const isLoading = ref(false)

/* TOGGLES */
const togglePassword = () => showPassword.value = !showPassword.value
const toggleConfirmPassword = () => showConfirmPassword.value = !showConfirmPassword.value

/* ✅ RESET PASSWORD */
const handleResetPassword = async () => {
  if (isLoading.value) return

  const email = localStorage.getItem('reset_email')

  if (!email) {
    toast.error('Session expired. Please try again.')
    router.push('/forgotPass')
    return
  }

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

  isLoading.value = true

  try {
    await axios.post('/api/forgot-password/reset', {
      email: email,
      password: password.value,
      password_confirmation: confirmPassword.value
    })

    toast.success('Password successfully reset!')

    localStorage.removeItem('otp_expiry')
    localStorage.removeItem('reset_email')

    router.push('/login')

  } catch (error) {
    console.log(error.response)
    toast.error(error.response?.data?.message || 'Reset failed')
  } finally {
    isLoading.value = false
  }
}

/* ANIMATION */
const containerRef = ref(null)
const leftRef = ref(null)
const rightRef = ref(null)
const titleRef = ref(null)
const subtitleRef = ref(null)
const newPassRef = ref(null)
const confirmPassRef = ref(null)
const enterBtnRef = ref(null)
const backBtnRef = ref(null)

const features = ['24/7 Available', 'Student Privacy', 'Fully Confidential']
const featureRefs = ref([])
const setFeatureRef = el => { if (el) featureRefs.value.push(el) }

onMounted(async () => {
  await nextTick()

  const tl = gsap.timeline({
    defaults: { duration: 0.6, ease: "power2.out" }
  })

  tl.from(leftRef.value, { x: -50, opacity: 0 }, 0)
  tl.from(titleRef.value, { y: 30, opacity: 0 }, 0.1)
  tl.from(subtitleRef.value, { y: 20, opacity: 0 }, 0.2)

  tl.from([newPassRef.value, confirmPassRef.value], {
    y: 20,
    opacity: 0,
    stagger: 0.08
  }, 0.3)

  tl.from([enterBtnRef.value, backBtnRef.value], {
    y: 15,
    opacity: 0,
    stagger: 0.08
  }, 0.4)
})
</script>

<style scoped src="../assets/login/forgotPass.css"></style>