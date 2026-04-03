<template>
    <div class="layout">
        <SidebarStudent></SidebarStudent>

        <main>
            <HeaderStudent></HeaderStudent>

            <div class="main-container">
                <div class="account-info-separation">
                    <div class="left-side">
                        <div class="info-container">
                            <div class="photo-titleName">
                                    <!-- Clickable Image -->
                                        <label for="upload-photo">
                                        <img 
                                            :src="preview || 'https://via.placeholder.com/100'" 
                                            class="photo-preview" 
                                            alt="Upload Photo"
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
                                <h3 class="full-name">{{ profile.firstName }} {{ profile.lastName }}</h3>
                                <p class="domain-mail">{{ profile.email }}</p>
                                <hr>
                            </div>

                            <div class="other-infos">
                                
                                <div class="info-item">
                                    <i class='bx bx-phone'></i>
                                    <span>Phone:</span> <p>{{ profile.phone }}</p>
                                </div>

                                <div class="info-item">
                                    <i class='bx bx-calendar'></i>
                                    <span>Joined:</span> <p>March 12, 2026</p>
                                </div>

                                <div class="info-item">
                                    <i class='bx bx-user'></i>
                                    <span>Department:</span> <p>College of Computer Studies</p> 
                                </div>

                                <div class="info-item">
                                    <i class='bx bx-book'></i>
                                    <span>Program:</span> <p>BS Information Technology</p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="right-side">
                        <div class="profile-input-container">
                            <div class="title-header">
                                <h3 class="profile-title">Profile Information</h3>
                                <p class="profile-desc">Update your personal details and credentials.</p> <!-- TO BE CHANGE  -->
                            </div>

                            <hr class="profile-hr">

                            <div class="input-info-container">

                                <div class="left-side-input">
                                    <!-- First Name (editable) -->
                                    <div class="name-input">
                                        <label class="input-title">First Name</label>
                                        <input type="text" class="input-info" v-model="form.firstName" @input="validateTextInput">
                                    </div>

                                    <!-- Email (read-only) -->
                                    <div class="name-input">
                                        <label class="input-title">Email</label>
                                        <input type="text" class="input-info" v-model="form.email" readonly>
                                    </div>

                                    <!-- Department (read-only) -->
                                    <div class="name-input">
                                        <label class="input-title">Department</label>
                                        <select class="input-info" v-model="form.department" disabled>
                                            <option value="CCS">College of Computer Studies (CCS)</option>
                                            <option value="CAHS">College of Allied Health and Studies (CAHS)</option>
                                            <option value="CEAS">College of Education, Arts and Sciences (CEAS)</option>
                                            <option value="CBA">College of Business and Accountancy (CBA)</option>
                                            <option value="CHTM">College of Hospitality and Tourism Management (CHTM)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="right-side-input">
                                    <!-- Last Name (editable) -->
                                    <div class="name-input">
                                        <label class="input-title">Last Name</label>
                                        <input type="text" class="input-info" v-model="form.lastName" @input="validateTextInput">
                                    </div>


                                    <!-- Phone (editable) -->
                                    <div class="name-input">
                                        <label class="input-title">Phone</label>
                                        <input 
                                            type="tel" 
                                            class="input-info" 
                                            v-model="form.phone"
                                            @input="validatePhone"
                                            placeholder="Enter your phone number..."
                                            maxlength="11"
                                        /> 
                                    </div>                                  
                                    <!-- Year Level (read-only) -->
                                    <div class="name-input">
                                        <label class="input-title">Year Level</label>
                                        <select class="input-info" v-model="form.yearLevel" disabled>
                                            <option value="1stYear">1st Year</option>
                                            <option value="2ndYear">2nd Year</option>
                                            <option value="3rdYear">3rd Year</option>
                                            <option value="4thYear">4th Year</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                                <div class="action-btn">
                                    <button class="cancel-btn" @click="cancelProfile">Cancel</button>
                                    <button class="save-btn" @click="submitProfile">Save Changes</button>
                                </div>
                        </div>

                        <div class="password-container">

                            <div class="password-header">
                                <h3 class="profile-title">Change Password</h3>
                                <p class="profile-desc">Update your password to secure your credentials.</p> <!-- TO BE CHANGE  -->
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
                                            <!-- Show icon only if input has value -->
                                            <i 
                                            v-if="passwords.current" 
                                            :class="showCurrent ? 'fa-solid fa-eye-slash eye-icon':'fa-solid fa-eye eye-icon'"
                                            @click="showCurrent = !showCurrent"
                                            ></i>
                                        </div>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div class="password-left-side password-wrapper">
                                        <label class="password-title">Confirm Password</label>
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
                                        <label class="password-title">Change Password</label>
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

                                  <div class="password-right-side">
                                        <label class="password-title">Email</label>
                                            <div class="email-input-wrapper">
                                                <!-- Fetch email from left side and make it read-only -->
                                                <input type="text" class="password-input" :value="form.email" readonly />
                                                <button class="send-btn" @click="sendOTP">
                                                    <i class='bx bx-paper-plane'></i>
                                                </button>
                                            </div>

                                        <!-- OTP Modal -->
                                        <transition name="fade-slide">
                                        <div v-if="showOTP" class="modal-overlay" @click.self="showOTP = false">
                                            <div class="modal-content">
                                            <!-- Close button -->
                                            <button class="close-btn" @click="showOTP = false">
                                                <i class="bx bx-x"></i>
                                            </button>

                                            <!-- Label with horizontal line -->
                                            <div class="otp-label-container">
                                                <label class="otp-label">Enter OTP</label>
                                            </div>

                                            <!-- OTP Inputs Row -->
                                            <div class="otp-inputs">
                                                <input 
                                                    v-for="(digit, index) in otp" 
                                                    :key="index" 
                                                    type="text" 
                                                    maxlength="1" 
                                                    class="otp-input" 
                                                    v-model="otp[index]"
                                                    @input="otp[index] = otp[index].replace(/\D/g, '')"
                                                />
                                            </div>

                                            <!-- Verify Button -->
                                                <button class="verify-btn" @click="verifyOTP">Verify</button>                                            </div>
                                        </div>
                                        </transition>
                                    </div>
                            </div>
                            </div>

                            <div class="action-btn">
                                <button class="cancel-btn" @click="cancelPassword">Cancel</button>
                                <button class="save-btn" @click="submitPassword">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import SidebarStudent from '@/components/sidebar.vue';
import HeaderStudent from '@/components/header.vue';
import { ref } from 'vue'
import { useToast } from 'vue-toastification'

const toast = useToast();
const isOTPVerified = ref(false)


// 👁️ Toggle states
const showCurrent = ref(false)
const showConfirm = ref(false)
const showNew = ref(false)

// UI states
const preview = ref(null)
const showOTP = ref(false)

// Profile display
const profile = ref({
  firstName: 'Allysa',
  lastName: 'Lingad',
  email: '202310636@gordoncollege.edu.ph',
  phone: '+63 912 345 6789',
  department: 'CCS',
  yearLevel: '1stYear'
})

// Editable form
const form = ref({ ...profile.value })

// OTP
const otp = ref(['', '', '', '', '', ''])

// Passwords
const passwords = ref({
  current: '',
  new: '',
  confirm: ''
})

// Upload
function handleUpload(event) {
  const file = event.target.files[0]
  if (file) preview.value = URL.createObjectURL(file)
}

// Validation
function validateTextInput(event) {
  event.target.value = event.target.value.replace(/[^a-zA-Z\s]/g, '')
}

function validatePhone() {
  form.value.phone = form.value.phone.replace(/\D/g, '').slice(0, 11)
}

// Profile actions
function submitProfile() {
  if (Object.values(form.value).some(v => !v)) {
    toast.error("Please complete all fields!");
    return;
  }
  profile.value = { ...form.value }
  toast.success("Profile updated successfully!");
}

function cancelProfile() {
  form.value = { ...profile.value }
  toast.info("Changes canceled!");
}

// OTP
function sendOTP() {
  toast.success("OTP sent to " + form.value.email);
  showOTP.value = true;
  isOTPVerified.value = false // reset OTP verification whenever new OTP is sent
}

function verifyOTP() {
  if (otp.value.some(d => d === '')) {
    toast.error("Complete OTP!");
    return;
  }
  toast.success("OTP verified!");
  isOTPVerified.value = true
  showOTP.value = false
}

// Password
function submitPassword() {
  if (!passwords.value.current || !passwords.value.new || !passwords.value.confirm) {
    toast.error("Complete all fields!");
    return;
  }

  if (passwords.value.new !== passwords.value.confirm) {
    toast.error("Passwords do not match!");
    return;
  }

  if (!isOTPVerified.value) {
    toast.error("Please verify OTP before saving password!");
    return;
  }

  toast.success("Password updated!");
  passwords.value = { current:'', new:'', confirm:'' }
  isOTPVerified.value = false // reset after successful change
  otp.value = ['', '', '', '', '', ''] // clear OTP fields
}
</script>

<style scoped src="../assets/users/MyAccount.css"></style>