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

          <button type="submit" class="login-button">Verify OTP</button>
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
import { useRouter } from "vue-router"

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
const handleVerify = ()=>{
  const otp = otp1.value + otp2.value + otp3.value + otp4.value + otp5.value + otp6.value

  // Validation: check if all 6 digits entered
  if (otp.length < 6) {
    toast.error("Please enter the 6-digit OTP correctly!")
    return
  }

  // Success
  toast.success(`OTP verified successfully!`)
  router.push('/login') // redirect after OTP success
}
</script>

<style scoped src="../assets/login/OTP.css"></style>