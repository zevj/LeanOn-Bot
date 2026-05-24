<template>
  <main>
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

        <!-- Mobile-only brand badge -->
        <div class="mobile-brand" ref="mobileBrandRef">
          <div class="mobile-brand-dot"></div>
          <span class="mobile-brand-label">LeanOn Bot &mdash; Mental Wellness Support</span>
        </div>

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

          <!-- Password Checklist -->
          <div class="pw-checklist" v-if="password">
            <p class="pw-checklist-title">Password requirements:</p>
            <ul>
              <li :class="{ met: checks.length }">
                <i :class="checks.length ? 'bx bx-check-circle' : 'bx bx-circle'"></i>
                At least 12 characters
              </li>
              <li :class="{ met: checks.uppercase }">
                <i :class="checks.uppercase ? 'bx bx-check-circle' : 'bx bx-circle'"></i>
                At least one uppercase letter (A–Z)
              </li>
              <li :class="{ met: checks.lowercase }">
                <i :class="checks.lowercase ? 'bx bx-check-circle' : 'bx bx-circle'"></i>
                At least one lowercase letter (a–z)
              </li>
              <li :class="{ met: checks.number }">
                <i :class="checks.number ? 'bx bx-check-circle' : 'bx bx-circle'"></i>
                At least one number (0–9)
              </li>
              <li :class="{ met: checks.special }">
                <i :class="checks.special ? 'bx bx-check-circle' : 'bx bx-circle'"></i>
                At least one special character (!@#$%...)
              </li>
              <li :class="{ met: checks.match }" v-if="confirmPassword">
                <i :class="checks.match ? 'bx bx-check-circle' : 'bx bx-circle'"></i>
                Passwords match
              </li>
            </ul>
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
            <div class="features" v-for="(f,i) in features" :key="i" :ref="setFeatureRef">
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
import { ref, computed, onMounted, nextTick } from 'vue'
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

/* PASSWORD REQUIREMENT CHECKS */
const checks = computed(() => {
  const pw = password.value
  return {
    length:    pw.length >= 12,
    uppercase: /[A-Z]/.test(pw),
    lowercase: /[a-z]/.test(pw),
    number:    /[0-9]/.test(pw),
    special:   /[^a-zA-Z0-9]/.test(pw),
    match:     pw.length > 0 && pw === confirmPassword.value,
  }
})

const allChecksPassed = computed(() =>
  checks.value.length &&
  checks.value.uppercase &&
  checks.value.lowercase &&
  checks.value.number &&
  checks.value.special
)

/* ✅ RESET PASSWORD */
const handleResetPassword = async () => {
  if (isLoading.value) return

  const email = localStorage.getItem('reset_email')
  const otp = localStorage.getItem('reset_otp')

  if (!email || !otp) {
    toast.error('Session expired. Please try again.')
    router.push('/forgotPass')
    return
  }

  if (!password.value || !confirmPassword.value) {
    toast.error('Please fill in all fields!')
    return
  }

  if (!allChecksPassed.value) {
    toast.error('Password does not meet all requirements. Please check the checklist.')
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
      otp: otp,
      password: password.value,
      password_confirmation: confirmPassword.value
    })

    toast.success('Password successfully reset!')

    localStorage.removeItem('otp_expiry')
    localStorage.removeItem('reset_email')
    localStorage.removeItem('reset_otp')

    router.push('/login')

  } catch (error) {
    console.log(error.response)
    toast.error(error.response?.data?.message || 'Reset failed')
  } finally {
    isLoading.value = false
  }
}

/* ANIMATION REFS */
const containerRef    = ref(null)
const leftRef         = ref(null)
const rightRef        = ref(null)
const titleRef        = ref(null)
const subtitleRef     = ref(null)
const newPassRef      = ref(null)
const confirmPassRef  = ref(null)
const enterBtnRef     = ref(null)
const backBtnRef      = ref(null)
const mobileBrandRef  = ref(null)   // ← new
/* GC LOGO */
const gcLogoRef = ref(null)
const appLogoRef = ref(null)

const features = ['24/7 Available', 'Student Privacy', 'Fully Confidential']
const featureRefs  = ref([])
const setFeatureRef = el => { if (el) featureRefs.value.push(el) }

onMounted(async () => {
  await nextTick()

  const isMobile = window.innerWidth <= 768

  const tl = gsap.timeline({
    defaults: { duration: 0.6, ease: 'power2.out' }
  })

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
      tl.from(mobileBrandRef.value, { y: -12, opacity: 0 }, 0.1)
    }

    tl.from(leftRef.value, { y: 28, opacity: 0 }, 0)

  } else {
    // Desktop — original slide-in, untouched
    tl.from(leftRef.value,  { x: -50, opacity: 0 }, 0)
    tl.from(rightRef.value, { x:  50, opacity: 0 }, 0)

    tl.from('.overlay',     { opacity: 0        }, 0.3)
    tl.from('.title',       { y: 50, opacity: 0 }, 0.4)
    tl.from('.subtitle',    { y: 25, opacity: 0 }, 0.5)
    tl.from('.yellow-line', { width: 0, opacity: 0 }, 0.6)
    tl.from('.subheading',  { y: 25, opacity: 0 }, 0.7)

    if (featureRefs.value.length) {
      tl.from(featureRefs.value, { y: 25, opacity: 0, stagger: 0.2 }, 0.9)
    }
  }

  // These run on both mobile and desktop
  tl.from(titleRef.value,    { y: 30, opacity: 0 }, 0.1)
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

<style scoped>
/* ── Password Requirement Checklist ── */
.pw-checklist {
  width: 100%;
  max-width: 600px;
  background: #f8fdf8;
  border: 1px solid #d1fae5;
  border-radius: 10px;
  padding: 14px 18px;
  margin-bottom: 4px;
  box-sizing: border-box;
}

.pw-checklist-title {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 10px 0;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.pw-checklist ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.pw-checklist li {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12.5px;
  color: #9ca3af;
  transition: color 0.2s ease;
}

.pw-checklist li i {
  font-size: 15px;
  color: #d1d5db;
  flex-shrink: 0;
  transition: color 0.2s ease;
}

.pw-checklist li.met {
  color: #065f46;
}

.pw-checklist li.met i {
  color: #059669;
}

[data-theme="dark"] .pw-checklist {
  background: rgba(6, 78, 59, 0.1);
  border-color: rgba(52, 211, 153, 0.2);
}

[data-theme="dark"] .pw-checklist-title {
  color: #d1fae5;
}

[data-theme="dark"] .pw-checklist li {
  color: #6b7280;
}

[data-theme="dark"] .pw-checklist li.met {
  color: #6ee7b7;
}

[data-theme="dark"] .pw-checklist li.met i {
  color: #34d399;
}
</style>