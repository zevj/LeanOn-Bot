import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/studentLogin.vue'
import Signup from '../views/studentSignup.vue'
import forgotPass from '../views/forgotPass.vue'
import OTPVerification from '../views/OTP.vue'
import NewPass from '../views/NewPass.vue'
import OTPFPass from '../views/OTPFPass.vue'
import AdminDashboard from '../views/adminDashboard.vue'
import StudentDashboard from '../views/studentDashboard.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },

  {
    path: '/login',
    name: 'login',
    component: Login
  },

  {
    path: '/studentDashboard',
    name: 'studentDashboard',
    component: StudentDashboard,
    meta: { requiresAuth: true, role: 'student' }
  },

  {
    path: '/adminDashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, role: 'guidance' }
  },

  {
    path: '/signup',
    name: 'signup',
    component: Signup
  },

  {
    path: '/forgotpass',
    name: 'forgotpass',
    component: forgotPass
  },

  {
    path: '/NewPass',
    name: 'NewPass',
    component: NewPass
  },

  {
    path: '/OTPVerification',
    name: 'OTPVerification',
    component: OTPVerification
  },

  {
    path: '/OTPFPass',
    name: 'OTPFPass',
    component: OTPFPass
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')

  if (to.meta.requiresAuth && !token) {
    return '/' // redirect to login
  }

  return true // allow navigation
})

export default router