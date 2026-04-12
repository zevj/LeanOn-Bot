import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/studentLogin.vue'
import Signup from '../views/studentSignup.vue'
import forgotPass from '../views/forgotPass.vue'
import OTPVerification from '../views/OTP.vue'
import NewPass from '../views/NewPass.vue'
import OTPFPass from '../views/OTPFPass.vue'
import AdminDashboardTest from '../views/adminDashboard.vue'
import StudentDashboard from '../views/studentDashboard.vue'
import ChatConvo from '../views/ChatConvo.vue'
import MyAccount from '../views/MyAccount.vue'
import LandingPage from '../views/LandingPage.vue'
import CrisisAlert from '../views/CrisisAlert.vue'
import AdminDashboard from '../views/Admin/Dashboard.vue'
import EmotionalTrends from '../views/Admin/EmotionalTrends.vue'
import AdminCrisisAlerts from '../views/Admin/AdminCrisisAlert.vue'


const routes = [
  {
    path: '/',
    redirect: '/LandingPage'
  },

  {
    path: '/LandingPage',
    name: 'LandingPage',
    component: LandingPage
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
    path: '/AdminDashboardTest',
    component: AdminDashboardTest,
    meta: { requiresAuth: true, role: 'guidance' }
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


  /* STUDENT */
  {
    path: '/ChatConvo',
    name: 'ChatConvo',
    component: ChatConvo
  },

  {
    path: '/MyAccount',
    name: 'MyAccount',
    component: MyAccount
  },

  {
    path: '/CrisisAlert',
    name: 'CrisisAlert',
    component: CrisisAlert
  },

  /* ADMIN */
  /* ADMIN */
  {
    path: '/AdminDashboard',
    name: 'AdminDashboard',
    component: AdminDashboard,
   /*  meta: { requiresAuth: true, role: 'guidance' } */
  },

  {
    path: '/EmotionalTrends',
    name: 'EmotionalTrends',
    component: EmotionalTrends
  },

   {
    path: '/AdminCrisisAlerts',
    name: 'AdminCrisisAlerts',
    component: AdminCrisisAlerts
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router