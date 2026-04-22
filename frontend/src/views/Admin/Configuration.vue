<template>
  <div class="layout">
    <SidebarAdmin></SidebarAdmin>

    <main>
      <HeaderAdmin></HeaderAdmin>

      <div class="main-container">

        <!-- Page Header -->
        <div class="page-header">
          <div>
            <h2 class="page-title">Configuration</h2>
            <p class="page-subtitle">System and chatbot settings</p>
          </div>
          <button class="save-btn" @click="saveSettings" :disabled="isSaving">
            <i class='bx bx-save'></i>
            {{ isSaving ? 'Saving...' : 'Save' }}
          </button>
        </div>

        <!-- Settings Cards -->
        <div class="settings-list">

          <!-- Max Session Length -->
          <div class="setting-card">
            <div class="setting-info">
              <h4 class="setting-label">Max Session Length (minutes)</h4>
              <p class="setting-desc">Maximum duration for a single chat session</p>
            </div>
            <div class="setting-control">
              <input
                type="number"
                class="setting-input"
                v-model.number="settings.maxSessionLength"
                min="1"
                max="240"
              />
            </div>
          </div>

          <!-- Distress Detection Threshold -->
          <div class="setting-card">
            <div class="setting-info">
              <h4 class="setting-label">Distress Detection Threshold</h4>
              <p class="setting-desc">Number of distress signals before recommending professional help</p>
            </div>
            <div class="setting-control">
              <input
                type="number"
                class="setting-input"
                v-model.number="settings.distressThreshold"
                min="1"
                max="20"
              />
            </div>
          </div>

          <!-- Auto Referral -->
          <div class="setting-card">
            <div class="setting-info">
              <h4 class="setting-label">Auto Referral</h4>
              <p class="setting-desc">Automatically suggest Guidance Office when threshold is met</p>
            </div>
            <div class="setting-control">
              <label class="toggle-switch">
                <input type="checkbox" v-model="settings.autoReferral" />
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>

          <!-- Welcome Message -->
          <div class="setting-card">
            <div class="setting-info">
              <h4 class="setting-label">Welcome Message</h4>
              <p class="setting-desc">The first message shown to users</p>
            </div>
            <div class="setting-control">
              <input
                type="text"
                class="setting-input setting-input--text"
                v-model="settings.welcomeMessage"
                placeholder="Enter welcome message..."
              />
            </div>
          </div>

          <!-- Data Retention -->
          <div class="setting-card">
            <div class="setting-info">
              <h4 class="setting-label">Data Retention (days)</h4>
              <p class="setting-desc">Days to retain anonymized interaction data</p>
            </div>
            <div class="setting-control">
              <input
                type="number"
                class="setting-input"
                v-model.number="settings.dataRetention"
                min="1"
                max="365"
              />
            </div>
          </div>

          <!-- Max Messages Per Session -->
          <div class="setting-card">
            <div class="setting-info">
              <h4 class="setting-label">Max Messages Per Session</h4>
              <p class="setting-desc">Maximum number of messages allowed in a single session</p>
            </div>
            <div class="setting-control">
              <input
                type="number"
                class="setting-input"
                v-model.number="settings.maxMessages"
                min="1"
                max="500"
              />
            </div>
          </div>
        </div>
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
const isSaving = ref(false)

const settings = ref({
  maxSessionLength: 30,
  distressThreshold: 3,
  autoReferral: true,
  welcomeMessage: 'Hi there! 💚 Welcome',
  dataRetention: 90,
  maintenanceMode: false,
  maxMessages: 50,
  allowAnonymous: false,
  crisisEmail: '',
  responseDelay: 500,
})

onMounted(() => {
  fetchSettings()
})

async function fetchSettings() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('http://127.0.0.1:8000/api/admin/configuration', {
      headers: { Authorization: `Bearer ${token}` }
    })
    settings.value = { ...settings.value, ...res.data }
  } catch {
    toast.error('Failed to load settings')
  }
}

async function saveSettings() {
  isSaving.value = true
  try {
    const token = localStorage.getItem('token')
    await axios.put('http://127.0.0.1:8000/api/admin/configuration', settings.value, {
      headers: { Authorization: `Bearer ${token}` }
    })
    toast.success('Configuration saved successfully!')
  } catch {
    toast.error('Failed to save settings')
  } finally {
    isSaving.value = false
  }
}
</script>

<style scoped src="@/assets/admin/Configuration.css"></style>