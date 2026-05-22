<template>
  <OtpModal
    v-model="showOtp"
    :email="username"
    @verified="onOtpVerified"
  />
 
  <!-- Dedicated Terms of Use modal -->
  <TermsOfUseModal
    :visible="showTermsOfUse"
    @close="showTermsOfUse = false"
  />
 
  <!-- Dedicated Privacy Policy modal -->
  <PrivacyPolicyModal
    :visible="showPrivacyPolicy"
    @close="showPrivacyPolicy = false"
  />
 
  <main ref="mainRef" class="login-main">
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
 
          <!-- Forgot password + terms agreement row -->
          <div class="forgot-terms-row" ref="forgotTermsRef">
            <router-link to="/forgotPass" class="forgot-password">
              Forgot Password?
            </router-link>
 
            
          </div>
          <!-- Cloudflare Turnstile Captcha Widget -->
          <div class="turnstile-wrapper">
            <p v-if="!turnstileToken" class="turnstile-message">Verifying you are human. This may take a few seconds...</p>
            <div id="turnstile-container"></div>
          </div>

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
 
          <button class="google-signin">
            <div class="btn-inner">
              <div class="g-logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
    </div>
    <div class="separator"></div>
    <div class="btn-text-group">
      <span class="btn-label">Continue with Google</span>
    </div>
  </div>
</button>
          <div class="terms-container">
            <p class="terms-agreement">
              By signing in you agree to our
              <button type="button" class="terms-link" @click="showTermsOfUse = true">Terms of Use</button>
              &amp;
              <button type="button" class="terms-link privacy" @click="showPrivacyPolicy = true">Privacy Policy</button>
            </p>
          </div>
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
import { ref, onMounted, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { gsap } from 'gsap'
import LoadingButton from '@/views/loadingButton.vue'
import OtpModal from '@/components/OtpModal.vue'
import TermsOfUseModal from '@/components/TermsofuseModal.vue'
import PrivacyPolicyModal from '@/components/PrivacypolicyModal.vue'
 
const showOtp = ref(false)
 
const username = ref('')
const password = ref('')
const showPassword = ref(false)
const isLoading = ref(false)

const turnstileToken = ref('')
const turnstileWidgetId = ref(null)
 
const toast = useToast()
const router = useRouter()
 
const togglePassword = () => {
  showPassword.value = !showPassword.value
}
 
/* =========================
   TERMS MODALS
========================= */
const showTermsOfUse = ref(false)
const showPrivacyPolicy = ref(false)
 
/* =========================
   LOGIN LOGIC
========================= */
const handleLogin = async () => {
  if (isLoading.value) return
 
  if (!username.value || !password.value) {
    toast.error('Please enter both email and password!')
    return
  }

  if (!turnstileToken.value) {
    toast.error('Please complete the security check!')
    return
  }
 
  isLoading.value = true
 
  try {
    const res = await axios.post('/api/login', {
      email: username.value,
      password: password.value,
      turnstile_token: turnstileToken.value
    })
 
    if (res.data.status === 'OTP_REQUIRED') {
      localStorage.setItem('token', res.data.token)
      localStorage.setItem('user', JSON.stringify(res.data.user))
      showOtp.value = true
      toast.success('OTP sent to your email!')
    } else {
      localStorage.setItem('token', res.data.token)
      localStorage.setItem('user', JSON.stringify(res.data.user))
      const role = res.data.user?.role || 'student'
      role === 'guidance' ? router.push('/adminDashboard') : router.push('/ChatConvo')
    }
 
  } catch (err) {
    toast.error(err.response?.data?.message || 'Login failed!')
    // Reset Turnstile on login failure so they can try again
    if (window.turnstile && turnstileWidgetId.value) {
      window.turnstile.reset(turnstileWidgetId.value)
      turnstileToken.value = ''
    }
  } finally {
    isLoading.value = false
  }
}
 
const onOtpVerified = (data) => {
  if (data?.token) localStorage.setItem('token', data.token)
  if (data?.user) localStorage.setItem('user', JSON.stringify(data.user))
  const user = data?.user || JSON.parse(localStorage.getItem('user'))
  const role = user?.role || 'student'
  role === 'guidance' ? router.push('/adminDashboard') : router.push('/ChatConvo')
}
 
/* =========================
   GOOGLE LOGIN
========================= */
const loginWithGoogle = () => {
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
const forgotTermsRef = ref(null)
const loginBtnRef = ref(null)
const googleBtnRef = ref(null)
const mobileBrandRef = ref(null)
 
/* =========================
   GSAP ANIMATION
========================= */
onMounted(() => {
  const isMobile = window.innerWidth <= 768
 
  const tl = gsap.timeline({
    defaults: { duration: 0.8, ease: 'power2.out' }
  })
 
  gsap.set(containerRef.value, { opacity: 1 })
 
  if (isMobile) {
    gsap.from('.orb', { opacity: 0, scale: 0.6, duration: 1.4, stagger: 0.25, ease: 'power2.out' })
    gsap.from('.gradient-circle', { opacity: 0, scale: 0.5, duration: 1.8, ease: 'power2.out', delay: 0.3 })
    if (mobileBrandRef.value) tl.from(mobileBrandRef.value, { y: -12, opacity: 0 }, 0.1)
    tl.from(leftRef.value, { y: 28, opacity: 0 }, 0)
  } else {
    tl.from(leftRef.value, { x: -70, opacity: 0 }, 0)
    tl.from(rightRef.value, { x: 70, opacity: 0 }, 0)
  }
 
  tl.from(titleRef.value, { y: 40, opacity: 0 }, 0.3)
  tl.from(subtitleRef.value, { y: 25, opacity: 0 }, 0.4)
  tl.from(formGroupRefs.value, { y: 25, opacity: 0, stagger: 0.15 }, 0.5)
  tl.from(forgotTermsRef.value, { y: 15, opacity: 0 }, 0.7)
  tl.from(loginBtnRef.value, { scale: 0.92, opacity: 0 }, 0.8)
  tl.from('.divider', { y: 15, opacity: 0 }, 0.9)
 
  gsap.set(googleBtnRef.value, { opacity: 0, y: 20, scale: 0.95 })
  tl.to(googleBtnRef.value, { opacity: 1, y: 0, scale: 1 }, 1.0)

 
  if (!isMobile) {
    tl.from('.overlay', { opacity: 0 }, 0.3)
    tl.from('.title', { y: 50, opacity: 0 }, 0.4)
    tl.from('.subtitle', { y: 25, opacity: 0 }, 0.5)
    tl.from('.yellow-line', { width: 0, opacity: 0 }, 0.6)
    tl.from('.subheading', { y: 25, opacity: 0 }, 0.7)
    tl.from('.features', { y: 25, opacity: 0, stagger: 0.2 }, 0.9)
  }
 
  const urlParams = new URLSearchParams(window.location.search)
  const error = urlParams.get('error')
  if (error) {
    if (error === 'invalid_domain') toast.error('Only Gordon College email accounts are allowed.')
    else if (error === 'user_not_found') toast.error('Account not found. Please register first.')
    else toast.error('Google authentication failed.')
  }

  // Load Cloudflare Turnstile Script Dynamically
  if (!window.turnstile) {
    let script = document.getElementById('cf-turnstile-script')
    if (!script) {
      script = document.createElement('script')
      script.id = 'cf-turnstile-script'
      script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onloadTurnstileCallback'
      script.async = true
      script.defer = true
      document.head.appendChild(script)
    }

    window.onloadTurnstileCallback = () => {
      renderTurnstile()
    }
  } else {
    renderTurnstile()
  }
})

const renderTurnstile = () => {
  if (window.turnstile) {
    turnstileWidgetId.value = window.turnstile.render('#turnstile-container', {
      sitekey: import.meta.env.VITE_TURNSTILE_SITE_KEY,
      callback: (token) => {
        turnstileToken.value = token
      },
      'error-callback': () => {
        toast.error('Turnstile security check failed to load.')
      },
      'expired-callback': () => {
        turnstileToken.value = ''
        if (turnstileWidgetId.value) {
          window.turnstile.reset(turnstileWidgetId.value)
        }
      }
    })
  }
}

onUnmounted(() => {
  if (window.turnstile && turnstileWidgetId.value !== null) {
    window.turnstile.remove(turnstileWidgetId.value)
  }
})
</script>

<style scoped src="../assets/login/Login.css"></style>

<style scoped>
.login-main {
  min-height: 100vh;
  min-height: 100dvh;
  width: 100%;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
}

.terms-container {
  margin-top: 1.5rem;
  width: 100%;
  display: flex;
  justify-content: center;
}

.turnstile-wrapper {
  margin: 1.25rem 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
}

.turnstile-message {
  font-size: 0.85rem;
  color: #666;
  text-align: center;
  margin: 0;
  animation: pulseText 1.5s infinite alternate;
}

[data-theme="dark"] .turnstile-message {
  color: #aaa;
}

@keyframes pulseText {
  from { opacity: 0.7; }
  to { opacity: 1; }
}
</style>