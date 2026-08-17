import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/studentLogin.vue'
import forgotPass from '../views/forgotpass.vue'
import NewPass from '../views/NewPass.vue'
import OTPFPass from '../views/OTPFPass.vue'
import ChatConvo from '../views/ChatConvo.vue'
import MyAccount from '../views/MyAccount.vue'
import LandingPage from '../views/LandingPage.vue'
import AdminDashboard from '../views/Admin/adminDashboard.vue'
import EmotionalTrends from '../views/Admin/EmotionalTrends.vue'
import AdminCrisisAlerts from '../views/Admin/AdminCrisisAlert.vue'
import AdminAppointments from '../views/Admin/AdminAppointments.vue'
import AdminProfile from '../views/Admin/AdminProfile.vue'
import AdminLogRecords from '../views/Admin/AdminLogRecords.vue'
import AdminAnalytics from '../views/Admin/AdminAnalytics.vue'
import AdminStudentInsights from '../views/Admin/AdminStudentInsights.vue'
import AdminMessages from '../views/Admin/AdminMessages.vue'
import StudentMessages from '../views/StudentMessages.vue'
import GoogleCallback from '../views/GoogleCallback.vue'

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
    path: '/OTPFPass',
    name: 'OTPFPass',
    component: OTPFPass
  },


  /* STUDENT */
  {
    path: '/ChatConvo',
    name: 'ChatConvo',
    component: ChatConvo,
    meta: { requiresAuth: true, role: 'student' }
  },

  {
    path: '/StudentMessages',
    name: 'StudentMessages',
    component: StudentMessages,
    meta: { requiresAuth: true, role: 'student' }
  },

  {
    path: '/MyAccount',
    name: 'MyAccount',
    component: MyAccount
  },

  /* ADMIN */
  {
    path: '/adminDashboard',
    name: 'adminDashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, role: 'guidance', hideThemeToggle: true }
  },

  {
    path: '/EmotionalTrends',
    name: 'EmotionalTrends',
    component: EmotionalTrends,
    meta: { hideThemeToggle: true }
  },

  {
    path: '/AdminCrisisAlerts',
    name: 'AdminCrisisAlerts',
    component: AdminCrisisAlerts,
    meta: { hideThemeToggle: true }
  },
  {
    path: '/AdminAppointments',
    name: 'AdminAppointments',
    component: AdminAppointments,
    meta: { hideThemeToggle: true }
  },

  {
    path: '/AdminMessages',
    name: 'AdminMessages',
    component: AdminMessages,
    meta: { requiresAuth: true, role: 'guidance', hideThemeToggle: true }
  },

  {
    path: '/AdminProfile',
    name: 'AdminProfile',
    component: AdminProfile,
    meta: { hideThemeToggle: true }
  },

  {
    path: '/AdminLogRecords',
    name: 'AdminLogRecords',
    component: AdminLogRecords,
    meta: { hideThemeToggle: true }
  },

  {
    path: '/AdminAnalytics',
    name: 'AdminAnalytics',
    component: AdminAnalytics,
    meta: { requiresAuth: true, role: 'guidance', hideThemeToggle: true }
  },

  {
    path: '/AdminStudentInsights',
    name: 'AdminStudentInsights',
    component: AdminStudentInsights,
    meta: { requiresAuth: true, role: 'guidance', hideThemeToggle: true }
  },

  /* GOOGLE AUTH CALLBACK */
  {
    path: '/auth/google/callback',
    name: 'GoogleCallback',
    component: GoogleCallback
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router