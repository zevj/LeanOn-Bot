import { createApp } from 'vue'
import { createPinia } from 'pinia'

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

axios.defaults.baseURL = 'http://127.0.0.1:8000'

// 🔐 attach token automatically to every request
axios.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})