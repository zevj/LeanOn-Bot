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
          <button class="login-button" :disabled="timeLeft > 0 || isResending" @click="resendOtp">
            {{ timeLeft > 0 ? 'Resend in ' + timeLeft + 's' : 'Resend OTP' }}
          </button>
          <router-link to="/forgotPass" class="back-button">Back</router-link>

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
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { onMounted, onUnmounted } from 'vue'

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

const timeLeft = ref(60)
const isResending = ref(false)
let timer = null

const startTimer = () => {
  const expiry = localStorage.getItem('otp_expiry')

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
  startTimer()
})

onUnmounted(() => {
  clearInterval(timer)
})

// Move to next input & allow numbers only
const next = (index,e)=>{
  // Remove non-numeric characters
  e.target.value = e.target.value.replace(/[^0-9]/g,'')
  
  const inputs = [i1,i2,i3,i4,i5,i6]
  
  if(e.target.value.length === 1 && index < 6){
    inputs[index].value.focus()
  }
}

// Handle OTP verification
const handleVerify = async () => {
  const otp = otp1.value + otp2.value + otp3.value + otp4.value + otp5.value + otp6.value

  if (otp.length < 6) {
    toast.error("Please enter the 6-digit OTP correctly!")
    return
  }
  if (timeLeft.value === 0) {
    toast.error('OTP expired. Please resend.')
    return
  }

  try {
    await axios.post('/api/forgot-password/verify-otp', {
      email: route.query.email,
      otp: otp
    })

    toast.success("OTP verified!")

    localStorage.setItem('reset_email', route.query.email)

    router.push('/NewPass')

  } catch (error) {
    toast.error(error.response?.data?.message || 'Invalid OTP')
  }
}

const resendOtp = async () => {
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

    // ✅ now response exists
    localStorage.setItem('otp_expiry', response.data.expires_at)

    toast.success('OTP resent!')

    startTimer() // 🔥 restart timer

  } catch (error) {
    console.log(error.response)
    toast.error(error.response?.data?.message || 'Failed to resend OTP')
  } finally {
    isResending.value = false
  }
}
</script>

<style scoped src="../assets/login/OTPFPass.css"></style>