<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const user = ref(null)
const router = useRouter()

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/')
}

onMounted(async () => {
  try {
    const res = await axios.get('/api/user')
    user.value = res.data
    console.log(res.data)
  } catch (err) {
    logout() // auto logout if token invalid
  }
})
</script>

<template>
  <div>
    <h1>Welcome {{ user?.name }}</h1>
    <p>{{ user?.email }}</p>

    <button @click="logout">Logout</button>
  </div>
</template>