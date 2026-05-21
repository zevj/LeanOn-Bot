<template>
    <div class="layout">
        <SidebarStudent
            :open="sidebarOpen"
            @toggle="sidebarOpen = !sidebarOpen"
        />

        <main>
            <HeaderStudent @toggle-sidebar="sidebarOpen = !sidebarOpen" />

            <div class="main-container">
                <div class="account-info-separation">
                    <div class="left-side">
                        <div class="info-container">
                            <div class="photo-titleName">
                                    <!-- Clickable Image -->
                                        <label for="upload-photo">
                                        <img 
                                            :src="profileImage" 
                                            class="photo-preview" 
                                            alt="Upload Photo"
                                            style="object-fit: cover; border-radius: 50%;"
                                        >
                                        </label>

                                        <!-- Hidden File Input -->
                                        <input 
                                        type="file" 
                                        id="upload-photo" 
                                        accept="image/*" 
                                        @change="handleUpload" 
                                        hidden
                                        >
                                <h3 class="full-name">{{ profile.first_name }} {{ profile.last_name }}</h3>
                                <p class="domain-mail">{{ profile.email }}</p>
                                <hr>
                            </div>

                            <div class="other-infos">
                                
                                <div class="info-item">
                                    <i class='bx bx-phone'></i>
                                    <span>Phone:</span> <p>{{ profile.phone_number || 'N/A' }}</p>
                                </div>
                                
                                <!-- Age Info -->
                                <div class="info-item">
                                    <i class='bx bx-cake'></i>
                                    <span>Age:</span> <p>{{ profile.age || 'N/A' }}</p>
                                </div>

                                <!-- Gender Info -->
                                <div class="info-item">
                                    <i class='bx bx-user-pin'></i>
                                    <span>Gender:</span> <p>{{ profile.gender || 'N/A' }}</p>
                                </div>

                                <div class="info-item">
                                    <i class='bx bx-calendar'></i>
                                    <span>Joined:</span> <p>{{ profile.joined }}</p>
                                </div>

                                <div class="info-item">
                                    <i class='bx bx-user'></i>
                                    <span>Department:</span> <p>{{ profile.department }}</p> 
                                </div>

                                <div class="info-item">
                                    <i class='bx bx-book'></i>
                                    <span>Program:</span> <p>{{ profile.program }}</p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="right-side">
                        <div class="profile-input-container">
                            <div class="title-header">
                                <h3 class="profile-title">Profile Information</h3>
                                <p class="profile-desc">Personal details and credentials.</p>
                            </div>

                            <hr class="profile-hr">

                            <div class="input-info-container">

                                <!-- Row 1: First Name | Last Name -->
                                <div class="input-row">
                                    <div class="name-input">
                                        <label class="input-title">First Name</label>
                                        <input type="text" class="input-info" v-model="form.first_name" readonly>
                                    </div>
                                    <div class="name-input">
                                        <label class="input-title">Last Name</label>
                                        <input type="text" class="input-info" v-model="form.last_name" readonly>
                                    </div>
                                </div>

                                <!-- Row 2: Age | Gender -->
                                <div class="input-row">
                                    <div class="name-input">
                                        <label class="input-title">Age</label>
                                        <input type="text" class="input-info" v-model="form.age" readonly>
                                    </div>
                                    <div class="name-input">
                                        <label class="input-title">Gender</label>
                                        <input type="text" class="input-info" v-model="form.gender" readonly>
                                    </div>
                                </div>

                                <!-- Row 3: Email | Phone -->
                                <div class="input-row">
                                    <div class="name-input">
                                        <label class="input-title">Email</label>
                                        <input type="text" class="input-info" v-model="form.email" readonly>
                                    </div>
                                    <div class="name-input">
                                        <label class="input-title">Phone</label>
                                        <input type="text" class="input-info" v-model="form.phone_number" readonly>
                                    </div>
                                </div>

                                <!-- Row 4: Department | Year Level -->
                                <div class="input-row">
                                    <div class="name-input">
                                        <label class="input-title">Department</label>
                                        <input type="text" class="input-info" v-model="form.department" readonly>
                                    </div>
                                    <div class="name-input">
                                        <label class="input-title">Year Level</label>
                                        <input type="text" class="input-info" v-model="form.year_level" readonly>
                                    </div>
                                </div>

                                <!-- Row 5: Program (full width) -->
                                <div class="input-row">
                                    <div class="name-input full-width-input">
                                        <label class="input-title">Program</label>
                                        <input type="text" class="input-info" v-model="form.program" readonly>
                                    </div>
                                </div>

                            </div>
                            
                            <div class="action-btn">
                                <button class="save-btn" @click="submitProfile">
                                    {{ isSaving ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </div>

                        <div class="password-container">

                            <div class="password-header">
                                <h3 class="profile-title">Change Password</h3>
                                <p class="profile-desc">Update your password to secure your credentials.</p>
                            </div>

                            <hr class="profile-hr">
                        
                            <div class="password-whole-container">

                                <div class="password-separation">

                                     <!-- Current -->
                                        <div class="password-left-side password-wrapper">
                                            <label class="password-title">Current Password</label>
                                            <div class="input-with-icon">
                                                <input 
                                                :type="showCurrent ? 'text':'password'" 
                                                class="password-input" 
                                                v-model="passwords.current"
                                                >
                                                <i 
                                                v-if="passwords.current" 
                                                :class="showCurrent ? 'fa-solid fa-eye-slash eye-icon':'fa-solid fa-eye eye-icon'"
                                                @click="showCurrent = !showCurrent"
                                                ></i>
                                            </div>
                                        </div>

                                        <!-- Confirm Password -->
                                        <div class="password-left-side password-wrapper">
                                            <label class="password-title">Confirm New Password</label>
                                            <div class="input-with-icon">
                                                <input 
                                                :type="showConfirm ? 'text':'password'" 
                                                class="password-input" 
                                                v-model="passwords.confirm"
                                                >
                                                <i 
                                                v-if="passwords.confirm"
                                                :class="showConfirm ? 'fa-solid fa-eye-slash eye-icon':'fa-solid fa-eye eye-icon'"
                                                @click="showConfirm = !showConfirm"
                                                ></i>
                                            </div>
                                        </div>
                                </div>

                                <div class="password-separation">

                                    <!-- New Password -->
                                        <div class="password-right-side password-wrapper">
                                            <label class="password-title">New Password</label>
                                            <div class="input-with-icon">
                                                <input 
                                                :type="showNew ? 'text':'password'" 
                                                class="password-input" 
                                                v-model="passwords.new"
                                                >
                                                <i 
                                                v-if="passwords.new"
                                                :class="showNew ? 'fa-solid fa-eye-slash eye-icon':'fa-solid fa-eye eye-icon'"
                                                @click="showNew = !showNew"
                                                ></i>
                                            </div>
                                        </div>

                                      <div class="password-right-side" style="display: none;"></div>

                                            <!-- OTP Modal -->
                                            <transition name="fade-slide">
                                            <div v-if="showOTP" class="modal-overlay" @click.self="showOTP = false">
                                                <div class="modal-content" style="text-align: center; padding: 30px; background: white; border-radius: 12px; max-width: 400px; width: 90%;">
                                                <!-- Close button -->
                                                <button class="close-btn" @click="showOTP = false" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 24px; cursor: pointer;">
                                                    <i class="bx bx-x"></i>
                                                </button>

                                                <div class="otp-label-container">
                                                    <label class="otp-label" style="font-size: 1.2rem; font-weight: 600;">Verify Password Change</label>
                                                    <p style="font-size: 13px; color: #666; margin-top: 8px;">We've sent a 6-digit code to your email.</p>
                                                </div>

                                                <div class="otp-inputs" style="margin: 25px 0; display: flex; justify-content: center; gap: 8px;">
                                                    <input 
                                                        v-for="(digit, index) in otp" 
                                                        :key="index" 
                                                        type="text" 
                                                        maxlength="1" 
                                                        class="otp-input" 
                                                        v-model="otp[index]"
                                                        @input="handleOtpInput($event, index)"
                                                        :id="'otp-' + index"
                                                        style="width: 40px; height: 50px; text-align: center; font-size: 20px; border: 2px solid #ddd; border-radius: 8px; outline: none; transition: border-color 0.2s;"
                                                    />
                                                </div>

                                                <div v-if="otpTimer > 0" class="otp-countdown" style="margin-bottom: 20px; color: #666; font-size: 14px;">
                                                    Resend code in <strong>{{ Math.floor(otpTimer / 60) }}:{{ String(otpTimer % 60).padStart(2, '0') }}</strong>
                                                </div>
                                                <div v-else class="resend-link" @click="sendOTP" style="cursor: pointer; color: #0E6008; margin-bottom: 20px; font-weight: 600; font-size: 14px;">
                                                    Resend OTP
                                                </div>

                                                <button @click="finalizePasswordChange" :disabled="isSavingPassword" style="width: 100%; padding: 12px; background: #0E6008; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                                                    {{ isSavingPassword ? 'Verifying...' : 'Verify & Update Password' }}
                                                </button>
                                                </div>
                                            </div>
                                            </transition>
                                </div>
                            </div>

                            <div class="action-btn">
                                <button class="save-btn" @click="submitPassword" :disabled="isSavingPassword">
                                    {{ isSavingPassword ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import SidebarStudent from '@/components/sidebarStudent.vue';
import HeaderStudent from '@/components/headerStudent.vue';
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const toast = useToast();
const sidebarOpen = ref(false)
const isOTPVerified = ref(false)
const isLoading = ref(true)
const isSaving = ref(false)
const isSavingPassword = ref(false)
const otpTimer = ref(0)
let timerInterval = null

// Toggle states
const showCurrent = ref(false)
const showConfirm = ref(false)
const showNew = ref(false)

// UI states
const preview = ref(null)
const showOTP = ref(false)



// Profile display
const profile = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone_number: '',
    department: '',
    program: '',
    year_level: '',
    joined: ''
})

// Editable form
const form = ref({ ...profile.value })

// Computed for profile image
const profileImage = computed(() => {
    if (preview.value) return preview.value
    if (profile.value.profile_image_url) {
        // If it's already an absolute URL, return it. Otherwise, prefix with base URL
        if (profile.value.profile_image_url.startsWith('http')) {
            return profile.value.profile_image_url
        }
        const baseURL = axios.defaults.baseURL || 'http://127.0.0.1:8000'
        return `${baseURL}/storage/${profile.value.profile_image_url}`
    }
    return 'https://placehold.co/100x100'
})

// OTP
const otp = ref(['', '', '', '', '', ''])

// Passwords
const passwords = ref({
    current: '',
    new: '',
    confirm: ''
})

onMounted(async () => {
    fetchUser()
})

async function fetchUser() {
    isLoading.value = true
    try {
        const token = localStorage.getItem('token')
        const response = await axios.get('/api/user', {
            headers: { Authorization: `Bearer ${token}` }
        })
        profile.value = {
            ...response.data,
            joined: response.data.created_at
                ? new Date(response.data.created_at).toLocaleDateString()
                : 'N/A'
        }
        form.value = { ...profile.value }
    } catch {
        toast.error("Failed to load user data")
    } finally {
        isLoading.value = false
    }
}

// Upload
async function handleUpload(event) {
    const file = event.target.files[0]
    if (!file) return

    preview.value = URL.createObjectURL(file)

    const formData = new FormData()
    formData.append('profile_image', file)

    try {
        const token = localStorage.getItem('token')
        const response = await axios.post('/api/user/image', formData, {
            headers: { 
                Authorization: `Bearer ${token}`,
                'Content-Type': 'multipart/form-data'
            }
        })
        toast.success("Profile image updated!")
        profile.value.profile_image_url = response.data.user.profile_image_url
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to upload image")
    }
}

function handleOtpInput(event, index) {
    const val = event.target.value.replace(/\D/g, '')
    otp.value[index] = val
    if (val && index < 5) {
        document.getElementById(`otp-${index + 1}`).focus()
    }
}

// Profile submit
async function submitProfile() {
    isSaving.value = true
    try {
        const token = localStorage.getItem('token')
        await axios.put('/api/user', {
            phone_number: form.value.phone_number
        }, {
            headers: { Authorization: `Bearer ${token}` }
        })
        // Sync profile sidebar display
        profile.value.phone_number = form.value.phone_number
        toast.success("Profile updated successfully!")
    } catch {
        toast.error("Failed to update profile")
    } finally {
        isSaving.value = false
    }
}

// OTP
async function sendOTP() {
  if (otpTimer.value > 0) return
  
  try {
    const token = localStorage.getItem('token')
    const response = await axios.post('/api/send-otp', {
        current_password: passwords.value.current
    }, {
        headers: { Authorization: `Bearer ${token}` }
    })
    toast.success("OTP sent to your email");
    showOTP.value = true;
    isOTPVerified.value = false
    // Use backend expiration to calculate timer
    if (response.data.expires_at) {
      const expiresAt = new Date(response.data.expires_at).getTime()
      const now = Date.now()
      const secondsLeft = Math.max(0, Math.floor((expiresAt - now) / 1000))
      startTimer(secondsLeft)
    } else {
      startTimer(300) // fallback: 5 minutes
    }
  } catch (error) {
    const msg = error.response?.data?.message || "Failed to send OTP";
    toast.error(msg)
  }
}

function startTimer(seconds = 300) {
    otpTimer.value = seconds
    if (timerInterval) clearInterval(timerInterval)
    timerInterval = setInterval(() => {
        if (otpTimer.value > 0) otpTimer.value--
        else clearInterval(timerInterval)
    }, 1000)
}

// Password
async function submitPassword() {
    if (!passwords.value.new || !passwords.value.confirm || !passwords.value.current) {
        toast.error("Complete all fields!")
        return
    }
    if (passwords.value.new !== passwords.value.confirm) {
        toast.error("Passwords do not match!")
        return
    }
    await sendOTP()
}

async function finalizePasswordChange() {
    const otpValue = otp.value.join('')
    if (otpValue.length < 6) {
        toast.error("Please enter the full 6-digit OTP!")
        return
    }

    isSavingPassword.value = true
    try {
        const token = localStorage.getItem('token')
        const response = await axios.post('/api/change-password', {
            current_password: passwords.value.current,
            new_password: passwords.value.new,
            new_password_confirmation: passwords.value.confirm,
            otp: otpValue
        }, {
            headers: { Authorization: `Bearer ${token}` }
        })
        toast.success(response.data.message || "Password updated!")
        passwords.value = { current: '', new: '', confirm: '' }
        isOTPVerified.value = false
        otp.value = ['', '', '', '', '', '']
        showOTP.value = false
    } catch (error) {
        const msg = error.response?.data?.message
            || error.response?.data?.errors?.new_password?.[0]
            || "Failed to update password"
        toast.error(msg)
    } finally {
        isSavingPassword.value = false
    }
}
</script>

<style scoped src="../assets/users/MyAccount.css"></style>