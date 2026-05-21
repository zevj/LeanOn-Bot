import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { encryptPayload, decryptPayload, generateNonce, generateTimestamp, signRequest } from '@/utils/crypto.js'

// import vue3GoogleLogin from 'vue3-google-login'
import App from './App.vue'
import router from './router'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import axios from 'axios'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(Toast)

// app.use(vue3GoogleLogin, {
//   clientId: '443922309929-1hqv1q5u435lrkoaeqileflt610ui7m6.apps.googleusercontent.com'
// })

app.mount('#app')

axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'

// 🔐 Axios Interceptors for E2E Encryption + HMAC Signing
//
// Request flow:
// 1. Attach auth token
// 2. AES-encrypt the payload (existing)
// 3. Generate timestamp + nonce
// 4. HMAC-sign the encrypted body + timestamp + nonce
// 5. Attach X-Timestamp, X-Nonce, X-Signature headers
//
// This ensures:
// - Confidentiality (AES encryption)
// - Integrity (HMAC signature)
// - Authenticity (only our frontend has the HMAC key)
// - Replay protection (unique nonce + timestamp window)

axios.interceptors.request.use(async config => {
  // Attach token automatically to every request
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  // Check if we should encrypt the request body
  // We do not encrypt multipart/form-data (FormData) or requests without body
  const isFormdata = config.data instanceof FormData
  const hasBody = config.data !== undefined && config.data !== null

  if (hasBody && !isFormdata) {
    config.headers['X-Encrypted'] = 'true'
    try {
      // Step 1: AES-encrypt the payload
      const encrypted = await encryptPayload(config.data)
      config.data = { payload: encrypted }

      // Step 2: Generate HMAC signing components
      const timestamp = generateTimestamp()
      const nonce = generateNonce()
      const bodyString = JSON.stringify(config.data)

      // Step 3: Sign the request body + timestamp + nonce
      const signature = await signRequest(bodyString, timestamp, nonce)

      // Step 4: Attach signing headers for backend verification
      config.headers['X-Timestamp'] = timestamp
      config.headers['X-Nonce'] = nonce
      config.headers['X-Signature'] = signature
    } catch (err) {
      console.error('Request encryption/signing failed:', err)
      return Promise.reject(err)
    }
  }

  return config
}, error => {
  return Promise.reject(error)
})

axios.interceptors.response.use(async response => {
  const isEncryptedHeader = response.headers['x-encrypted'] === 'true'
  const isEncryptedPayload = response.data && typeof response.data === 'object' && response.data.payload

  if (isEncryptedHeader || isEncryptedPayload) {
    try {
      const decrypted = await decryptPayload(response.data.payload)
      response.data = decrypted
    } catch (err) {
      console.error('Response decryption failed:', err)
      return Promise.reject({
        message: 'Failed to decrypt secure response payload',
        response
      })
    }
  }
  return response
}, async error => {
  // Handle decryption for error responses (e.g. 422 validation, 401 unauthenticated)
  if (error.response && error.response.data && error.response.data.payload) {
    try {
      const decrypted = await decryptPayload(error.response.data.payload)
      error.response.data = decrypted
    } catch (err) {
      console.error('Failed to decrypt error response payload:', err)
    }
  }
  return Promise.reject(error)
})