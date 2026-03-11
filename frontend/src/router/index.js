import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/login.vue'
import Signup from '../views/Signup.vue'
import forgotPass from '../views/forgotpass.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: Login
  },

  {
    path: '/signup',
    name: 'signup',
    component: Signup
  },

   {
    path: '/forgotPass',
    name: 'forgotPass',
    component: forgotPass
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
