import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/login.vue'
import Signup from '../views/Signup.vue'
import forgotPass from '../views/forgotpass.vue'
import OTPVerification from '../views/OTP.vue'
import NewPass from '../views/NewPass.vue'
import OTPFPass from '../views/OTPFPass.vue'
import ChatConvo from '../views/ChatConvo.vue'
import MyAccount from '../views/MyAccount.vue'
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
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router