<template>
  <div class="layout">
    <SidebarAdmin
            :open="sidebarOpen"
            @toggle="sidebarOpen = !sidebarOpen"
        />

    <main>
      <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <div class="main-container">

        <!-- Page Header -->
        <div class="page-header">
          <div>
            <h2 class="page-title">My Profile</h2>
            <p class="page-subtitle">Manage your account information and credentials</p>
          </div>
        </div>

        <div class="profile-wrapper">

          <!-- LEFT: Info Card -->
          <div class="info-card">

            <!-- Avatar -->
            <div class="avatar-section">
              <label for="upload-photo" class="avatar-label">
                <img
                  :src="preview || profile.profile_image_url || 'https://via.placeholder.com/100'"
                  class="avatar-img"
                  alt="Profile Photo"
                />
                <div class="avatar-overlay">
                  <i class='bx bx-camera'></i>
                </div>
              </label>
              <input type="file" id="upload-photo" accept="image/*" @change="handleUpload" hidden />
              <h3 class="avatar-name">{{ profile.first_name }} {{ profile.last_name }}</h3>
              <span class="avatar-role">{{ profile.role === 'guidance' ? 'Guidance Counselor' : 'Administrator' }}</span>
            </div>

            <hr class="divider" />

            <!-- Info List -->
            <div class="info-list">

              <div class="info-row">
                <div class="info-icon-wrap">
                  <i class='bx bx-envelope'></i>
                </div>
                <div class="info-text">
                  <span class="info-key">Email</span>
                  <p class="info-val">{{ profile.email || 'N/A' }}</p>
                </div>
              </div>

              <div class="info-row">
                <div class="info-icon-wrap">
                  <i class='bx bx-phone'></i>
                </div>
                <div class="info-text">
                  <span class="info-key">Phone</span>
                  <p class="info-val">{{ profile.phone_number || 'N/A' }}</p>
                </div>
              </div>

              <div class="info-row">
                <div class="info-icon-wrap">
                  <i class='bx bx-buildings'></i>
                </div>
                <div class="info-text">
                  <span class="info-key">Unit</span>
                  <p class="info-val">{{ profile.unit || 'N/A' }}</p>
                </div>
              </div>

              <div class="info-row">
                <div class="info-icon-wrap">
                  <i class='bx bx-id-card'></i>
                </div>
                <div class="info-text">
                  <span class="info-key">Role</span>
                  <p class="info-val">{{ profile.role === 'guidance' ? 'Guidance Counselor' : 'Administrator' }}</p>
                </div>
              </div>

            </div>
          </div>

          <!-- RIGHT: Forms -->
          <div class="forms-column">

            <!-- Profile Information -->
            <div class="form-card">
              <div class="form-card-header">
                <div>
                  <h3 class="form-card-title">Profile Information</h3>
                  <p class="form-card-desc">Update your personal details below.</p>
                </div>
              </div>

              <hr class="form-hr" />

              <div class="form-grid">

                <div class="form-group">
                  <label class="form-label">First Name</label>
                  <input
                    type="text"
                    class="form-input"
                    v-model="form.first_name"
                    @input="form.first_name = form.first_name.replace(/[^a-zA-Z\s]/g, '')"
                    placeholder="Enter first name..."
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">Last Name</label>
                  <input
                    type="text"
                    class="form-input"
                    v-model="form.last_name"
                    @input="form.last_name = form.last_name.replace(/[^a-zA-Z\s]/g, '')"
                    placeholder="Enter last name..."
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-input" v-model="form.email" placeholder="Enter email address..." />
                </div>

                <div class="form-group">
                  <label class="form-label">Phone Number</label>
                  <input
                    type="tel"
                    class="form-input"
                    v-model="form.phone_number"
                    @input="validatePhone"
                    placeholder="Enter phone number..."
                    maxlength="11"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">Unit</label>
                  <select class="form-input" v-model="form.unit">
                    <option value="" disabled>Select Unit</option>
                    <option value="Gordon College">Gordon College</option>
                    <option value="Guidance Unit">Guidance Unit</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label">Role</label>
                  <select class="form-input" v-model="form.role">
                    <option value="" disabled>Select Role</option>
                    <option value="admin">Administrator</option>
                    <option value="guidance">Guidance Counselor</option>
                  </select>
                </div>

              </div>

              <div class="form-action">
                <button class="btn-save" @click="submitProfile" :disabled="isSaving">
                  <i class='bx bx-save'></i>
                  {{ isSaving ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </div>

            <!-- Change Password -->
            <div class="form-card">
              <div class="form-card-header">
                <div>
                  <h3 class="form-card-title">Change Password</h3>
                  <p class="form-card-desc">Update your password to keep your account secure.</p>
                </div>
              </div>

              <hr class="form-hr" />

              <div class="form-grid">

                <div class="form-group">
                  <label class="form-label">Current Password</label>
                  <div class="input-icon-wrap">
                    <input
                      :type="showCurrent ? 'text' : 'password'"
                      class="form-input"
                      v-model="passwords.current"
                      placeholder="Enter current password..."
                    />
                    <i
                      v-if="passwords.current"
                      :class="showCurrent ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
                      class="eye-btn"
                      @click="showCurrent = !showCurrent"
                    ></i>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">New Password</label>
                  <div class="input-icon-wrap">
                    <input
                      :type="showNew ? 'text' : 'password'"
                      class="form-input"
                      v-model="passwords.new"
                      placeholder="Enter new password..."
                    />
                    <i
                      v-if="passwords.new"
                      :class="showNew ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
                      class="eye-btn"
                      @click="showNew = !showNew"
                    ></i>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Confirm New Password</label>
                  <div class="input-icon-wrap">
                    <input
                      :type="showConfirm ? 'text' : 'password'"
                      class="form-input"
                      v-model="passwords.confirm"
                      placeholder="Confirm new password..."
                    />
                    <i
                      v-if="passwords.confirm"
                      :class="showConfirm ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
                      class="eye-btn"
                      @click="showConfirm = !showConfirm"
                    ></i>
                  </div>
                </div>

              </div>

              <div class="form-action">
                <button class="btn-save" @click="submitPassword" :disabled="isSavingPassword">
                  <i class='bx bx-lock-alt'></i>
                  {{ isSavingPassword ? 'Saving...' : 'Update Password' }}
                </button>
              </div>
            </div>

          </div>
        </div>

        <!-- OTP Modal -->
        <transition name="fade-slide">
          <div v-if="showOTP" class="modal-overlay" @click.self="showOTP = false">
            <div class="otp-modal">

              <button class="otp-close" @click="showOTP = false">
                <i class='bx bx-x'></i>
              </button>

              <div class="otp-icon-wrap">
                <i class='bx bx-shield-quarter'></i>
              </div>

              <h3 class="otp-title">Verify Password Change</h3>
              <p class="otp-sub">We've sent a 6-digit code to your email address.</p>

              <div class="otp-inputs">
                <input
                  v-for="(digit, index) in otp"
                  :key="index"
                  type="text"
                  maxlength="1"
                  class="otp-box"
                  v-model="otp[index]"
                  @input="handleOtpInput($event, index)"
                  :id="'otp-' + index"
                />
              </div>

              <div v-if="otpTimer > 0" class="otp-timer">
                Resend code in <strong>{{ otpTimer }}s</strong>
              </div>
              <div v-else class="otp-resend" @click="sendOTP">
                Resend OTP
              </div>

              <button class="otp-verify-btn" @click="finalizePasswordChange" :disabled="isSavingPassword">
                {{ isSavingPassword ? 'Verifying...' : 'Verify & Update Password' }}
              </button>

            </div>
          </div>
        </transition>

      </div>
    </main>
  </div>
</template>

<script setup>
import SidebarAdmin from '@/components/sidebarAdmin.vue'
import HeaderAdmin from '@/components/headerAdmin.vue'
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const toast = useToast()

const isLoading = ref(false)
const isSaving = ref(false)
const isSavingPassword = ref(false)
const preview = ref(null)
const showOTP = ref(false)
const otpTimer = ref(0)
let timerInterval = null
const sidebarOpen = ref(false)
const showCurrent = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)

const profile = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone_number: '',
  unit: '',
  role: '',
  joined: '',
  profile_image_url: '',
})

const form = ref({ ...profile.value })

const passwords = ref({
  current: '',
  new: '',
  confirm: '',
})

const otp = ref(['', '', '', '', '', ''])

onMounted(() => {
  fetchProfile()
})

async function fetchProfile() {
  isLoading.value = true
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('http://127.0.0.1:8000/api/admin/profile', {
      headers: { Authorization: `Bearer ${token}` }
    })
    profile.value = {
      ...res.data,
      joined: res.data.created_at
        ? new Date(res.data.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
        : 'N/A'
    }
    form.value = { ...profile.value }
  } catch {
    toast.error('Failed to load profile')
  } finally {
    isLoading.value = false
  }
}

async function handleUpload(event) {
  const file = event.target.files[0]
  if (!file) return
  preview.value = URL.createObjectURL(file)
  const formData = new FormData()
  formData.append('profile_image', file)
  try {
    const token = localStorage.getItem('token')
    const res = await axios.post('http://127.0.0.1:8000/api/admin/profile/image', formData, {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'multipart/form-data' }
    })
    profile.value.profile_image_url = res.data.profile_image_url
    toast.success('Profile photo updated!')
  } catch (error) {
    toast.error(error.response?.data?.message || 'Failed to upload image')
  }
}

function validatePhone() {
  form.value.phone_number = form.value.phone_number.replace(/\D/g, '').slice(0, 11)
}

async function submitProfile() {
  isSaving.value = true
  try {
    const token = localStorage.getItem('token')
    await axios.put('http://127.0.0.1:8000/api/admin/profile', {
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      email: form.value.email,
      phone_number: form.value.phone_number,
      unit: form.value.unit,
      role: form.value.role,
    }, { headers: { Authorization: `Bearer ${token}` } })
    profile.value.first_name = form.value.first_name
    profile.value.last_name = form.value.last_name
    profile.value.email = form.value.email
    profile.value.phone_number = form.value.phone_number
    profile.value.unit = form.value.unit
    profile.value.role = form.value.role
    toast.success('Profile updated successfully!')
  } catch {
    toast.error('Failed to update profile')
  } finally {
    isSaving.value = false
  }
}

async function sendOTP() {
  if (otpTimer.value > 0) return
  try {
    const token = localStorage.getItem('token')
    await axios.post('http://127.0.0.1:8000/api/send-otp', {}, { headers: { Authorization: `Bearer ${token}` } })
    toast.success('OTP sent to your email')
    showOTP.value = true
    startTimer()
  } catch {
    toast.error('Failed to send OTP')
  }
}

function startTimer() {
  otpTimer.value = 60
  if (timerInterval) clearInterval(timerInterval)
  timerInterval = setInterval(() => {
    if (otpTimer.value > 0) otpTimer.value--
    else clearInterval(timerInterval)
  }, 1000)
}

function handleOtpInput(event, index) {
  const val = event.target.value.replace(/\D/g, '')
  otp.value[index] = val
  if (val && index < 5) document.getElementById(`otp-${index + 1}`).focus()
}

async function submitPassword() {
  if (!passwords.value.current || !passwords.value.new || !passwords.value.confirm) {
    toast.error('Please fill in all password fields!')
    return
  }
  if (passwords.value.new !== passwords.value.confirm) {
    toast.error('New passwords do not match!')
    return
  }
  await sendOTP()
}

async function finalizePasswordChange() {
  const otpValue = otp.value.join('')
  if (otpValue.length < 6) {
    toast.error('Please enter the full 6-digit OTP!')
    return
  }
  isSavingPassword.value = true
  try {
    const token = localStorage.getItem('token')
    const res = await axios.post('http://127.0.0.1:8000/api/change-password', {
      current_password: passwords.value.current,
      new_password: passwords.value.new,
      new_password_confirmation: passwords.value.confirm,
      otp: otpValue
    }, { headers: { Authorization: `Bearer ${token}` } })
    toast.success(res.data.message || 'Password updated!')
    passwords.value = { current: '', new: '', confirm: '' }
    otp.value = ['', '', '', '', '', '']
    showOTP.value = false
  } catch (error) {
    const msg = error.response?.data?.message
      || error.response?.data?.errors?.new_password?.[0]
      || 'Failed to update password'
    toast.error(msg)
  } finally {
    isSavingPassword.value = false
  }
}
</script>

<style scoped src="@/assets/admin/AdminProfile.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>