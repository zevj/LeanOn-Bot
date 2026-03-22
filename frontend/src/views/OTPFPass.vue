<template>
  <main>
    <div class="login-container">

      <!-- LEFT CONTAINER -->
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

          <router-link to="/NewPass" class="login-button">Verify OTP</router-link>
          <router-link to="/forgotPass" class="back-button">Back</router-link>

        </form>
      </div>

      <!-- RIGHT CONTAINER -->
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
import { ref, onMounted } from "vue"
import { useToast } from "vue-toastification"
import { useRouter } from "vue-router"
import gsap from "gsap"

// OTP fields
const otp1 = ref("")
const otp2 = ref("")
const otp3 = ref("")
const otp4 = ref("")
const otp5 = ref("")
const otp6 = ref("")

// Refs for inputs
const i1 = ref(null)
const i2 = ref(null)
const i3 = ref(null)
const i4 = ref(null)
const i5 = ref(null)
const i6 = ref(null)

// Refs for right-side animation
const rightRef = ref(null)
const overlayRef = ref(null)
const rightTitleRef = ref(null)
const rightSubtitleRef = ref(null)
const lineRef = ref(null)
const subheadingRef = ref(null)
const footerContainerRef = ref(null)
const feature1Ref = ref(null)
const feature2Ref = ref(null)
const feature3Ref = ref(null)

const featureRefs = [feature1Ref, feature2Ref, feature3Ref]

const toast = useToast()
const router = useRouter()

// Move to next input & allow numbers only
const next = (index, e) => {
  e.target.value = e.target.value.replace(/[^0-9]/g,'')
  const inputs = [i1,i2,i3,i4,i5,i6]
  if(e.target.value.length === 1 && index < 6){
    inputs[index].value.focus()
  }
}

// Handle OTP verification
const handleVerify = () => {
  const otp = otp1.value + otp2.value + otp3.value + otp4.value + otp5.value + otp6.value
  if (otp.length < 6) {
    toast.error("Please enter the 6-digit OTP correctly!")
    return
  }
  toast.success("OTP verified successfully!")
  router.push('/login')
}

// GSAP animation for right side only
onMounted(() => {
  // Overlay
  gsap.from(overlayRef.value, { opacity: 0, duration: 0.8, ease: "power2.out" })

  // Title & subtitle
  gsap.from([rightTitleRef.value, rightSubtitleRef.value], { 
    y: 30, opacity: 0, stagger: 0.15, duration: 0.6, ease: "power2.out"
  })

  // Yellow line
  gsap.from(lineRef.value, { width: 0, opacity: 0, duration: 0.5, ease: "power2.out" })

  // Subheading
  gsap.from(subheadingRef.value, { y: 25, opacity: 0, duration: 0.6, ease: "power2.out", delay: 0.1 })

  // Footer features
  gsap.from(featureRefs.map(f => f.value), { 
    y: 20, opacity: 0, stagger: 0.15, duration: 0.5, ease: "power2.out", delay: 0.2 
  })
})
</script>

<style scoped src="../assets/login/OTPFPass.css"></style>