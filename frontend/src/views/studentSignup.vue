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
              @input="firstName = firstName.replace(/[^a-zA-Z\s]/g, '')"
            />
          </div>

          <!-- Last Name -->
          <div class="form-group input-wrapper" ref="lastNameRef">
            <label for="lastName">Last Name</label>
            <i class="bx bx-user input-icon"></i>
            <input type="text" id="lastName" placeholder="Enter your last name..." v-model="lastName" @input="lastName = lastName.replace(/[^a-zA-Z\s]/g, '')"/>
          </div>

          <!-- Email -->
          <div class="form-group input-wrapper" ref="emailRef">
            <label for="email">Email Address</label>
            <i class="bx bx-envelope input-icon"></i>
            <input type="text" id="email" placeholder="Enter your email..." v-model="email"/>
          </div>

          <!-- Department -->
          <div class="form-group input-wrapper" ref="departmentRef">
            <label for="department">Department</label>
            <i class="bx bx-buildings input-icon"></i>
            <select id="department" v-model="department" required @change="onDepartmentChange">
              <option value="" disabled>Select Department</option>
              <option value="CAHS">College of Allied Health Studies (CAHS)</option>
              <option value="CBA">College of Business and Accountancy (CBA)</option>
              <option value="CCS">College of Computer Studies (CCS)</option>
              <option value="CEAS">College of Education, Arts, and Sciences (CEAS)</option>
              <option value="CHTM">College of Hospitality and Tourism Management (CHTM)</option>
            </select>
          </div>

          <!-- Program -->
          <div class="form-group input-wrapper" ref="programRef">
            <label for="program">Program</label>
            <i class="bx bx-book input-icon"></i>
            <select id="program" v-model="program" required :disabled="!department">
              <option value="" disabled>Select Program</option>
              <option v-for="p in availablePrograms" :key="p" :value="p">{{ p }}</option>
            </select>
            <p v-if="!department" class="program-hint" style="color: #888; font-size: 11px; margin-top: -15px; margin-bottom: 10px;">Please select a department to see programs</p>
          </div>

          <!-- Year Level -->
          <div class="form-group input-wrapper" ref="yearLevelRef">
            <label for="yearLevel">Year Level</label>
            <i class="bx bx-list-ul input-icon"></i>
            <select id="yearLevel" v-model="yearLevel" required>
              <option value="" disabled>Select Year Level</option>
              <option value="1st Year">1st Year</option>
              <option value="2nd Year">2nd Year</option>
              <option value="3rd Year">3rd Year</option>
              <option value="4th Year">4th Year</option>
            </select>
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
            <LoadingButton type="submit" class="login-button" :loading="isLoading" ref="signupBtnRef">Sign Up</LoadingButton>

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
import { ref, onMounted, computed } from 'vue'
import { gsap } from 'gsap'
import { useToast } from 'vue-toastification'
import { useRouter } from 'vue-router'
import axios from 'axios'
import LoadingButton from '@/views/loadingButton.vue'

/* FORM DATA */
const firstName = ref('')
const lastName = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const department = ref('')
const program = ref('')
const yearLevel = ref('')

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
const departmentRef = ref(null)
const programRef = ref(null)
const yearLevelRef = ref(null)
const passwordRef = ref(null)
const confirmPasswordRef = ref(null)

/* FEATURE REFS */
const featureRefs = ref([])
const setFeatureRef = el => { if(el) featureRefs.value.push(el) }
const features = ['24/7 Available', 'Student Privacy', 'Fully Confidential']

/* TOAST + ROUTER */
const toast = useToast()
const router = useRouter()
const isLoading = ref(false)

/* TOGGLE PASSWORD */
const togglePassword = () => showPassword.value = !showPassword.value
const toggleConfirmPassword = () => showConfirmPassword.value = !showConfirmPassword.value

const programsMap = {
  CAHS: [
    'Bachelor of Science in Nursing',
    'Bachelor of Science in Midwifery'
  ],
  CBA: [
    'Bachelor of Science in Accountancy',
    'BSBA Major in Financial Management',
    'BSBA Major in Human Resource Management',
    'BSBA Major in Marketing Management',
    'Bachelor of Science in Customs Administration'
  ],
  CCS: [
    'Bachelor of Science in Computer Science',
    'Bachelor of Science in Entertainment and Multimedia Computing',
    'Bachelor of Science in Information Technology'
  ],
  CEAS: [
    'Bachelor of Arts in Communication',
    'Bachelor of Early Childhood Education',
    'Bachelor of Culture and Arts Education',
    'Bachelor of Physical Education',
    'Bachelor of Elementary Education (General Education)',
    'BSEd Major in English',
    'BSEd Major in Filipino',
    'BSEd Major in Mathematics',
    'BSEd Major in Social Studies',
    'BSEd Major in Sciences',
    'Teacher Certificate Program (TCP)'
  ],
  CHTM: [
    'Bachelor of Science in Hospitality Management',
    'Bachelor of Science in Tourism Management'
  ]
}

const availablePrograms = computed(() => {
  return department.value ? (programsMap[department.value] || []) : []
})

const onDepartmentChange = () => {
  program.value = ''
}

const handleSignup = async () => {
  if (isLoading.value) return // 🚫 prevent double click

  if (!firstName.value || !lastName.value || !email.value || !password.value || !confirmPassword.value || !department.value || !program.value || !yearLevel.value) {
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

  isLoading.value = true // 🔥 START LOADING

  try {
    const response = await axios.post('http://127.0.0.1:8000/api/register', {
      first_name: firstName.value,
      last_name: lastName.value,
      email: email.value,
      password: password.value,
      password_confirmation: confirmPassword.value,
      department: department.value,
      program: program.value,
      year_level: yearLevel.value
    })

    localStorage.setItem('signup_otp_expiry', response.data.otp_expires_at)
    localStorage.setItem('signup_email', email.value)

    toast.success('Signup successful! Please verify OTP.')
    router.push('/OTPVerification')

  } catch (error) {
    console.error(error)

    if (error.response) {
      toast.error(error.response.data.message || 'Signup failed')
    } else {
      toast.error('Server error')
    }

  } finally {
    isLoading.value = false // 🔥 ALWAYS STOP LOADING
  }
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

  const inputs = [
    firstNameRef.value, 
    lastNameRef.value, 
    emailRef.value, 
    departmentRef.value,
    programRef.value,
    yearLevelRef.value,
    passwordRef.value, 
    confirmPasswordRef.value
  ]
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