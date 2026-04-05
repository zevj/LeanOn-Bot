<template>
  <div class="app">
    <!-- NAV -->
    <nav :class="['navbar', { scrolled: isScrolled }]">
      <div class="nav-logo">
        <div class="logo-icon"><i class='bx bxs-leaf'></i></div>
        <span>LeanOn Bot</span>
      </div>
      <ul class="nav-links">
        <li><a href="#about" @click.prevent="scrollTo('about')">About</a></li>
        <li><a href="#features" @click.prevent="scrollTo('features')">Features</a></li>
        <li><a href="#how" @click.prevent="scrollTo('how')">How It Works</a></li>
        <li><a href="#contact" @click.prevent="scrollTo('contact')">Contact</a></li>
      </ul>
      <router-link to="/login" class="nav-cta"><i class='bx bx-log-in'></i> Login</router-link>
      <div class="hamburger" @click="mobileOpen = !mobileOpen">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </nav>

    <!-- MOBILE MENU -->
    <div :class="['mobile-menu', { open: mobileOpen }]">
      <a v-for="link in navLinks" :key="link.id"
        :href="`#${link.id}`"
        @click.prevent="scrollTo(link.id); mobileOpen = false">
        <i :class="`bx ${link.icon}`"></i> {{ link.label }}
      </a>
      <a href="#login" class="nav-cta mobile-cta" @click="mobileOpen = false">
        <i class='bx bx-log-in'></i> Login
      </a>
    </div>

    <!-- HERO -->
    <section class="hero" id="hero">
      <div class="hero-content">
        <div class="hero-badge">
          <span class="dot"></span>
          Available 24 / 7 for Gordon College Students
        </div>
        <h1>Your Safe Space to<br><em>Lean On</em> Someone</h1>
        <p>LeanOn Bot is an AI-assisted mental health wellness support system — a confidential, judgment-free chatbot that listens, responds, and guides you toward well-being.</p>
        <div class="hero-btns">
          <a href="#login" class="btn-primary"><i class='bx bx-chat'></i> Start Chatting</a>
          <a href="#how" class="btn-outline" @click.prevent="scrollTo('how')"><i class='bx bx-play-circle'></i> Learn How It Works</a>
        </div>
      </div>
      <div class="hero-visual">
        <div class="chat-card">
          <div class="chat-header">
            <div class="chat-avatar"><i class='bx bxs-leaf'></i></div>
            <div class="chat-header-text">
              <h4>LeanOn Bot</h4>
              <span><i class='bx bxs-circle online-dot'></i> Online – Always here</span>
            </div>
          </div>
          <div v-for="(msg, i) in chatMessages" :key="i"
            :class="['chat-bubble', msg.from === 'bot' ? 'bubble-bot' : 'bubble-user']">
            {{ msg.text }}
          </div>
          <div class="chat-input-mock">
            <span>Type something...</span>
            <button class="send-btn"><i class='bx bx-send'></i></button>
          </div>
        </div>
      </div>
    </section>

    <!-- ABOUT -->
    <section class="about" id="about">
      <div class="about-grid">
        <div class="about-text">
          <span class="section-label">About the System</span>
          <h2 class="section-title">Built for Gordon College Students, by Students</h2>
          <p>LeanOn Bot was designed in response to a real need: Gordon College students often hesitate to visit the Guidance Office in person. Our AI-powered platform provides a private, stigma-free space to express concerns, receive coping strategies, and get connected to professional help when needed.</p>
          <p style="margin-top:.8rem;">This system complements — not replaces — the expert counselors at the Guidance Office. Think of it as your first-line, always-available mental wellness companion.</p>
          <div class="about-stats">
            <div v-for="stat in stats" :key="stat.num" class="stat-card">
              <div class="num">{{ stat.num }}</div>
              <p>{{ stat.desc }}</p>
            </div>
          </div>
        </div>
        <div class="about-visual">
          <h3><i class='bx bxs-graduation'></i> Gordon College Guidance Office</h3>
          <p>LeanOn Bot was developed in close collaboration with the Gordon College Guidance and Counseling Unit, validated by Ms. Mechel A. Bautista, to ensure the system truly addresses student mental health needs.</p>
          <div class="about-tags">
            <span v-for="tag in aboutTags" :key="tag" class="tag">{{ tag }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- FEATURES -->
    <section class="features" id="features">
      <div class="section-header center">
        <span class="section-label">Core Features</span>
        <h2 class="section-title">Everything You Need to Feel Supported</h2>
        <p class="section-sub">From real-time emotional support to smart referrals, LeanOn Bot is equipped with tools designed around student well-being.</p>
      </div>
      <div class="features-grid">
        <div v-for="feat in features" :key="feat.title" class="feature-card">
          <div class="feature-icon"><i :class="`bx ${feat.icon}`"></i></div>
          <h3>{{ feat.title }}</h3>
          <p>{{ feat.desc }}</p>
        </div>
      </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="how" id="how">
      <div class="section-header-1 center">
        <span class="section-label-1">How It Works</span>
        <h2 class="section-title-1 how-title">Simple. Private. Always On.</h2>
        <p class="section-sub-1 how-sub">Getting support through LeanOn Bot takes just a few steps — no appointments, no waiting rooms.</p>
      </div>
      <div class="steps">
        <div v-for="step in steps" :key="step.num" class="step">
          <div class="step-num"><i :class="`bx ${step.icon}`"></i></div>
          <h4>{{ step.title }}</h4>
          <p>{{ step.desc }}</p>
        </div>
      </div>
    </section>

    <!-- CONTACT -->
    <section class="contact" id="contact">
      <div class="contact-grid">
        <div class="contact-info">
          <span class="section-label">Get in Touch</span>
          <h2 class="section-title">We're Here to Help</h2>
          <p class="section-sub">Have questions about LeanOn Bot, or need to reach the Guidance Office directly? Reach out anytime.</p>
          <div class="contact-items">
            <div v-for="item in contactItems" :key="item.title" class="contact-item">
              <div class="icon"><i :class="`bx ${item.icon}`"></i></div>
              <div>
                <h4>{{ item.title }}</h4>
                <p>{{ item.desc }}</p>
              </div>
            </div>
          </div>
        </div>
        <div class="contact-form-wrap">
          <h3><i class='bx bx-envelope'></i> Send a Message</h3>
          <form @submit.prevent="handleSubmit" novalidate>
            <div class="form-row">
              <div class="form-group">
                <label>First Name</label>
                <input v-model="form.firstName" type="text" placeholder="Juan" required />
              </div>
              <div class="form-group">
                <label>Last Name</label>
                <input v-model="form.lastName" type="text" placeholder="dela Cruz" required />
              </div>
            </div>
            <div class="form-group">
              <label>Email Address</label>
              <input v-model="form.email" type="email" placeholder="juan@gordoncollege.edu.ph" required />
            </div>
            <div class="form-group">
              <label>Subject</label>
              <select v-model="form.subject">
                <option>General Inquiry</option>
                <option>Technical Support</option>
                <option>Guidance Office Appointment</option>
                <option>Other</option>
              </select>
            </div>
            <div class="form-group">
              <label>Message</label>
              <textarea v-model="form.message" placeholder="Write your message here..." rows="4"></textarea>
            </div>
            <button type="submit" class="btn-primary form-submit">
              <i :class="`bx ${submitted ? 'bx-check-circle' : 'bx-send'}`"></i>
              {{ submitted ? 'Message Sent!' : 'Send Message' }}
            </button>
          </form>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer>
      <div class="footer-logo">
        <div class="logo-icon"><i class='bx bxs-leaf'></i></div>
        <span>LeanOn Bot</span>
      </div>
      <p>AI-Assisted Mental Health Wellness Support System for Students</p>
      <p class="footer-sub">Gordon College – Guidance and Counseling Unit</p>
      <div class="footer-links">
        <a v-for="link in navLinks" :key="link.id"
          :href="`#${link.id}`"
          @click.prevent="scrollTo(link.id)">{{ link.label }}</a>
        <a href="#login">Login</a>
      </div>
      <hr class="footer-divider" />
      <p class="footer-bottom">© 2026 DualDev – Allysa Lingad & Ira Jacob Javier · Gordon College Capstone Project · SDG 3 & SDG 4</p>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, reactive } from 'vue'

// ── Nav scroll state
const isScrolled = ref(false)
const mobileOpen = ref(false)

const handleScroll = () => { isScrolled.value = window.scrollY > 20 }
onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  // Load Boxicons if not already present
  if (!document.querySelector('link[href*="boxicons"]')) {
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = 'https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css'
    document.head.appendChild(link)
  }
})
onUnmounted(() => window.removeEventListener('scroll', handleScroll))

const scrollTo = (id) => {
  const el = document.getElementById(id)
  if (el) el.scrollIntoView({ behavior: 'smooth' })
}

// ── Data
const navLinks = [
  { id: 'about',    label: 'About',        icon: 'bx-info-circle' },
  { id: 'features', label: 'Features',     icon: 'bx-star' },
  { id: 'how',      label: 'How It Works', icon: 'bx-help-circle' },
  { id: 'contact',  label: 'Contact',      icon: 'bx-envelope' },
]

const chatMessages = [
  { from: 'bot',  text: "Hi! I'm LeanOn Bot. How are you feeling today? You can share anything with me. 💚" },
  { from: 'user', text: "I've been really stressed about my exams..." },
  { from: 'bot',  text: "I hear you. Exam stress is tough. Let's try a quick breathing exercise together first. 🌬️" },
]

const stats = [
  { num: '24/7',  desc: 'Available any time, any day — no appointments needed' },
  { num: '100%',  desc: 'Confidential interactions, protected by data privacy standards' },
  { num: '5+',    desc: 'Evidence-based coping strategies built into every session' },
  { num: 'SDG 3', desc: 'Aligned with UN Good Health & Well-Being goals' },
]

const aboutTags = ['Mental Wellness', 'AI Chatbot', 'Student Support', 'Referral System', 'Emotion Detection', 'Gordon College']

const features = [
  { icon: 'bx-chat',          title: 'AI Chatbot Interaction',       desc: 'Text your thoughts freely. LeanOn Bot responds in real time with supportive, wellness-focused messages in a completely private, judgment-free conversation.' },
  { icon: 'bx-brain',         title: 'Emotion Recognition',          desc: 'Our rule-based engine analyzes keywords and emotional cues in your messages to detect stress, anxiety, and recurring distress signals accurately.' },
  { icon: 'bx-heart',         title: 'Wellness Recommendations',     desc: 'Receive personalized coping strategies — breathing exercises, grounding techniques, relaxation methods, and positive affirmations tailored to your emotional state.' },
  { icon: 'bx-bell',          title: 'Referral Recommendation',      desc: 'When recurring high-risk emotional patterns are detected, the system gently encourages you to consult the Guidance Office — non-alarming and supportive.' },
  { icon: 'bx-lock-alt',      title: 'Secure & Confidential Access', desc: 'Your data is protected. The system stores only what is necessary, ensuring your interactions remain private and handled ethically at all times.' },
  { icon: 'bx-bar-chart-alt-2', title: 'Admin Dashboard',            desc: 'Guidance Office staff can view anonymized emotional trend reports and usage statistics — enabling data-driven support without compromising student privacy.' },
]

const steps = [
  { num: '1', icon: 'bx-log-in-circle',  title: 'Log In',              desc: 'Access LeanOn Bot securely using your Gordon College student credentials.' },
  { num: '2', icon: 'bx-message-detail', title: 'Share Your Feelings',  desc: "Type what's on your mind. The chatbot listens without judgment, any time of day." },
  { num: '3', icon: 'bx-heart-circle',   title: 'Receive Support',      desc: 'Get personalized wellness responses, coping exercises, and affirmations instantly.' },
  { num: '4', icon: 'bx-link-external',  title: 'Get Connected',        desc: 'If patterns suggest you need more help, the bot guides you to the Guidance Office.' },
]

const contactItems = [
  { icon: 'bx-buildings',   title: 'Gordon College – Guidance Office', desc: 'Tapinac Oval Sports Complex, Donor St., East Tapinac, Olongapo City' },
  { icon: 'bx-envelope',    title: 'Email',        desc: 'guidance@gordoncollege.edu.ph' },
  { icon: 'bxl-messenger',  title: 'GC Connect',   desc: 'Message the Guidance Office via GC Connect platform or Facebook Messenger.' },
  { icon: 'bx-time-five',   title: 'Office Hours', desc: 'Monday – Friday, 8:00 AM – 5:00 PM (LeanOn Bot available 24/7)' },
]

// ── Contact form
const form = reactive({ firstName: '', lastName: '', email: '', subject: 'General Inquiry', message: '' })
const submitted = ref(false)
const handleSubmit = () => {
  submitted.value = true
  setTimeout(() => {
    submitted.value = false
    Object.assign(form, { firstName: '', lastName: '', email: '', subject: 'General Inquiry', message: '' })
  }, 3000)
}
</script>

<style src="../assets/Header & Sidebar/LandingPage.css"></style>