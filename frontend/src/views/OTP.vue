<template>
  <main>
    <div class="login-container">

      <div class="left-container">
        <h1 class="login-title">Verify your account</h1>
        <p class="login-subtitle">The code has been sent to your email, please enter your code.</p>

        <form class="login-form" @submit.prevent="handleVerify">

          <div class="otp-container">
            <input class="otp-input" maxlength="1" v-model="otp1" @input="next(1,$event)" ref="i1">
            <input class="otp-input" maxlength="1" v-model="otp2" @input="next(2,$event)" ref="i2">
            <input class="otp-input" maxlength="1" v-model="otp3" @input="next(3,$event)" ref="i3">
            <input class="otp-input" maxlength="1" v-model="otp4" @input="next(4,$event)" ref="i4">
            <input class="otp-input" maxlength="1" v-model="otp5" @input="next(5,$event)" ref="i5">
            <input class="otp-input" maxlength="1" v-model="otp6" @input="next(6,$event)" ref="i6">
          </div>
          <p class="otp-timer">
            <span v-if="timeLeft > 0">
              OTP expires in {{ timeLeft }}s
            </span>

            <span v-else class="expired"> 
              OTP expired
            </span>
          </p>
          <button type="submit" class="login-button">Verify OTP</button>

            <!-- ✅ correct route for signup OTP flow -->
            <router-link to="/signup" class="back-button">
              Back
            </router-link>

            <div class="resend-otp">
              <p
                type="button"
                class="resend-otp"
                :disabled="timeLeft > 0"
                @click="handleResend"
              >
                Didn't get a code? <span>{{ timeLeft > 0 ? 'Resend in ' + timeLeft + 's' : 'Resend OTP' }}</span>
              </p>
            </div>

        </form>
      </div>

      <div class="right-container">
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
import { ref } from "vue"
import { useToast } from "vue-toastification"
import { useRouter } from "vue-router"
import axios from "axios"
import { useRoute } from 'vue-router'
import { onMounted, onUnmounted } from 'vue'
import { gsap } from 'gsap'

// OTP fields
const otp1 = ref("")
const otp2 = ref("")
const otp3 = ref("")
const otp4 = ref("")
const otp5 = ref("")
const otp6 = ref("")

// Refs for input auto-focus
const i1 = ref(null)
const i2 = ref(null)
const i3 = ref(null)
const i4 = ref(null)
const i5 = ref(null)
const i6 = ref(null)

const toast = useToast()
const router = useRouter()
const route = useRoute()

const timeLeft = ref(0)
let timer = null

const startTimer = () => {
  clearInterval(timer)

  const expiry = localStorage.getItem('signup_otp_expiry')

  if (!expiry) return

  timer = setInterval(() => {
    const now = new Date().getTime()
    const expiryTime = new Date(expiry).getTime()

    const diff = Math.floor((expiryTime - now) / 1000)

    if (diff > 0) {
      timeLeft.value = diff
    } else {
      timeLeft.value = 0
      clearInterval(timer)
    }
  }, 1000)
}

onMounted(() => {
  const email = localStorage.getItem('signup_email')

  if (!email) {
    toast.error('Missing email. Please sign up again.')
    router.push('/signup')
    return
  }

  startTimer()
})

onUnmounted(() => {
  clearInterval(timer)
})

// Move to next input & allow numbers only
const next = (index, e) => {
  e.target.value = e.target.value.replace(/[^0-9]/g, '')

  const inputs = [i1, i2, i3, i4, i5, i6]

  if (e.target.value.length === 1 && index < 6) {
    inputs[index].value.focus()
  }
}

// Handle OTP verification
const handleVerify = async () => {
  const email = localStorage.getItem('signup_email') // ✅ ALWAYS GET HERE

  console.log("EMAIL:", email)

  const otp = otp1.value + otp2.value + otp3.value + otp4.value + otp5.value + otp6.value
  console.log("OTP ENTERED:", otp)

  if (otp.length < 6) {
    toast.error("Please enter the 6-digit OTP correctly!")
    return
  }

  if (timeLeft.value === 0) {
    toast.error('OTP expired. Please resend.')
    return
  }

  try {
    const response = await axios.post('http://127.0.0.1:8000/api/verify-otp', {
      email: email,
      otp: otp
    })

    localStorage.setItem('token', response.data.token)

    toast.success("OTP verified successfully!")
    router.push('/login')

  } catch (error) {
    console.log("ERROR:", error)

    if (error.response) {
      toast.error(error.response.data.message)
    } else {
      toast.error("Something went wrong")
    }
  }
}

const handleResend = async () => {
  if (timeLeft.value > 0) return // 🔥 use real expiry

  const email = localStorage.getItem('signup_email')

  if (!email) {
    toast.error("Session expired")
    return
  }

  try {
    const response = await axios.post('http://127.0.0.1:8000/api/resend-otp', {
      email: email
    })

    // 🔥 update expiry from backend
    localStorage.setItem('signup_otp_expiry', response.data.expires_at)

    toast.success("OTP resent successfully!")

    startTimer() 

  } catch (error) {
    toast.error(error.response?.data?.message || "Failed to resend OTP")
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

  gsap.set(containerRef.value, { opacity: 1 })

  tl.from(leftRef.value, { x: -70, opacity: 0 }, 0)
  tl.from(rightRef.value, { x: 70, opacity: 0 }, 0)

  tl.from(titleRef.value, { y: 40, opacity: 0 }, 0.3)
  tl.from(subtitleRef.value, { y: 25, opacity: 0 }, 0.4)

  tl.from(formGroupRefs.value, {
    y: 25,
    opacity: 0,
    stagger: 0.15
  }, 0.5)

  tl.from(".forgot-password", { y: 15, opacity: 0 }, 0.7)

  tl.from(loginBtnRef.value, {
    scale: 0.92,
    opacity: 0
  }, 0.8)

  tl.from(".divider", { y: 15, opacity: 0 }, 0.9)

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

  tl.from(".new-student", { y: 15, opacity: 0 }, 1.1)

  tl.from(".overlay", { opacity: 0 }, 0.3)
  tl.from(".title", { y: 50, opacity: 0 }, 0.4)
  tl.from(".subtitle", { y: 25, opacity: 0 }, 0.5)
  tl.from(".yellow-line", { width: 0, opacity: 0 }, 0.6)
  tl.from(".subheading", { y: 25, opacity: 0 }, 0.7)

  tl.from(".features", {
    y: 25,
    opacity: 0,
    stagger: 0.2
  }, 0.9)
})

</script>

<style scoped src="../assets/login/OTP.css"></style>