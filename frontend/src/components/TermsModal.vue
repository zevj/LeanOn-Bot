<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="visible" class="terms-overlay">
        <div class="terms-modal" role="dialog" aria-modal="true" aria-labelledby="terms-title">

          <!-- Header -->
          <div class="terms-header">
            <div class="terms-logo">
              <img src="/leanOnBot.png" class="logo-icon" alt="LeanOn Bot Logo" />
              <span class="logo-text">LeanOn Bot</span>
            </div>

            <!-- Tab switcher (only in view mode) -->
            <div v-if="mode === 'view'" class="terms-tabs">
              <button
                class="terms-tab"
                :class="{ active: activeSection === 'terms' }"
                @click="activeSection = 'terms'"
              >Terms of Use</button>
              <button
                class="terms-tab"
                :class="{ active: activeSection === 'privacy' }"
                @click="activeSection = 'privacy'"
              >Privacy Policy</button>
            </div>

            <!-- Accept mode: fixed title -->
            <h2 v-else id="terms-title" class="terms-title">
              {{ activeSection === 'terms' ? 'Terms of Use' : 'Privacy Policy' }}
            </h2>

            <p class="terms-subtitle">
              <template v-if="mode !== 'view'">
                Please read the following carefully before using LeanOn Bot. By accepting, you acknowledge and agree to these terms.
              </template>
              <template v-else>
                {{ activeSection === 'terms'
                  ? 'Governing rules for using LeanOn Bot.'
                  : 'How we handle and protect your data.' }}
              </template>
            </p>

            <!-- Close button: only shown in view mode -->
            <button v-if="mode === 'view'" class="modal-close-btn" @click="$emit('close')">✕</button>
          </div>

          <!-- Scrollable Content -->
          <div class="terms-body" ref="termsBody" @scroll="handleScroll">

            <!-- ── TERMS OF USE ───────────────────────────── -->
            <template v-if="activeSection === 'terms'">
              <section class="terms-section">
                <h3><span class="section-num">01</span> Purpose of This Service</h3>
                <p>
                  LeanOn Bot is an AI-assisted mental wellness support chatbot designed exclusively for <strong>Gordon College students</strong>. It provides a confidential, judgment-free digital space where students can express emotions, receive supportive responses, and access wellness resources at any time.
                </p>
              </section>

              <section class="terms-section">
                <h3><span class="section-num">02</span> Not a Replacement for Professional Help</h3>
                <p>
                  LeanOn Bot is a <strong>first-line support tool only</strong>. It does <em>not</em> provide medical diagnoses, psychological assessments, or professional counseling services. If you are experiencing a mental health crisis or emergency, please contact the <strong>Gordon College Guidance and Counseling Unit</strong> or a licensed mental health professional immediately.
                </p>
              </section>

              <section class="terms-section">
                <h3><span class="section-num">03</span> Emergency Situations</h3>
                <p>
                  LeanOn Bot does <strong>not</strong> provide real-time crisis intervention or emergency hotline integration in its current release. If you or someone you know is in immediate danger, please contact emergency services or a trusted adult right away.
                </p>
              </section>

              <section class="terms-section">
                <h3><span class="section-num">04</span> AI Limitations</h3>
                <p>
                  LeanOn Bot uses rule-based logic and predefined wellness content to generate responses. It may not fully understand complex or nuanced emotional situations. All responses are supportive in nature and should <strong>not</strong> be interpreted as definitive psychological advice or clinical guidance.
                </p>
              </section>

              <section class="terms-section">
                <h3><span class="section-num">05</span> Appropriate Use</h3>
                <p>
                  This platform is intended solely for Gordon College students seeking emotional support and mental wellness guidance. Users are expected to engage with the system honestly and responsibly to receive the most effective support.
                </p>
              </section>

              <section class="terms-section">
                <h3><span class="section-num">06</span> Referral to the Guidance Office</h3>
                <p>
                  If LeanOn Bot detects repeated or high-risk emotional patterns in your conversations, it may automatically recommend that you consult with the Gordon College Guidance Office. These referral suggestions are supportive and non-mandatory, and are made solely in the interest of your wellbeing.
                </p>
              </section>
            </template>

            <!-- ── PRIVACY POLICY ─────────────────────────── -->
            <template v-else>
              <section class="terms-section">
                <h3><span class="section-num">01</span> Data We Collect</h3>
                <p>
                  We collect only the information necessary to provide and improve LeanOn Bot: your student account details (name, email, student ID) and anonymized records of your chat interactions. No sensitive personal data beyond what you voluntarily share in conversation is collected.
                </p>
              </section>

              <section class="terms-section">
                <h3><span class="section-num">02</span> How We Use Your Data</h3>
                <p>
                  Your information is used solely to deliver personalized wellness support, maintain service quality, and generate anonymized trend reports for the Gordon College Guidance Office. We do <strong>not</strong> use your data for advertising, profiling, or any commercial purpose.
                </p>
              </section>

              <section class="terms-section">
                <h3><span class="section-num">03</span> Confidentiality &amp; Disclosure</h3>
                <p>
                  Your personal identity will <strong>never</strong> be shared with or disclosed to third parties. Anonymized emotional trend data may be reviewed by the Guidance Office solely for program improvement purposes. Individually identifiable information is only disclosed if required by Philippine law or to prevent imminent harm.
                </p>
              </section>

              <section class="terms-section">
                <h3><span class="section-num">04</span> Data Storage &amp; Security</h3>
                <p>
                  All data is stored on secured servers with industry-standard encryption in transit and at rest. Access is strictly limited to authorized system administrators and Guidance Office personnel under confidentiality agreements.
                </p>
              </section>

              <section class="terms-section">
                <h3><span class="section-num">05</span> Data Retention</h3>
                <p>
                  Conversation logs are retained for a maximum of one academic year after your last active session, after which they are permanently deleted. You may request deletion of your data at any time by contacting the Guidance Office.
                </p>
              </section>

              <section class="terms-section">
                <h3><span class="section-num">06</span> Your Rights</h3>
                <p>
                  Under the <strong>Data Privacy Act of the Philippines (Republic Act No. 10173)</strong>, you have the right to access, correct, and request deletion of your personal data. To exercise these rights, contact the Gordon College Data Protection Officer.
                </p>
              </section>

              <section class="terms-section">
                <h3><span class="section-num">07</span> Cookies &amp; Local Storage</h3>
                <p>
                  LeanOn Bot uses browser local storage exclusively to maintain your authenticated session. No third-party tracking cookies are used.
                </p>
              </section>
            </template>

            <div class="body-spacer"></div>
          </div>

          <!-- Scroll hint: only shown in accept mode -->
          <div v-if="mode !== 'view'" class="scroll-hint-bar" :class="{ hidden: hasScrolled }">
            <span class="scroll-hint-text">↓ Scroll to read all terms</span>
          </div>

          <!-- Footer: only shown in accept mode -->
          <div v-if="mode !== 'view'" class="terms-footer">
            <div class="checkboxes">
              <label
                class="checkbox-label"
                :class="{ checked: check2 }"
                @click="toggleCheck"
              >
                <span class="custom-check">
                  <svg v-if="check2" viewBox="0 0 12 10" fill="none">
                    <path d="M1 5l3.5 3.5L11 1" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
                <span class="check-text">I have read and agree to the <strong>Terms of Use</strong> and <strong>Privacy Policy</strong> of LeanOn Bot.</span>
              </label>
            </div>

            <div class="terms-actions">
              <button class="btn-decline" @click="handleDecline">Decline</button>
              <button
                class="btn-accept"
                :disabled="!canAccept"
                :class="{ active: canAccept }"
                @click="handleAccept"
              >
                <span>Accept &amp; Continue</span>
                <span class="btn-icon">→</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const props = defineProps({
  visible: Boolean,
  mode: {
    type: String,
    default: 'accept', // 'accept' | 'view'
    validator: (v) => ['accept', 'view'].includes(v)
  },
  // 'terms' | 'privacy' — which tab opens first (view mode only)
  section: {
    type: String,
    default: 'terms',
    validator: (v) => ['terms', 'privacy'].includes(v)
  }
})

const emit = defineEmits(['accept', 'decline', 'close'])
const router = useRouter()

/* ─────────────────────────────
   STATES
───────────────────────────── */
const check2 = ref(false)
const hasScrolled = ref(false)
const activeSection = ref(props.section)

/* ─────────────────────────────
   RESET STATE WHEN MODAL OPENS
───────────────────────────── */
watch(() => props.visible, (val) => {
  if (val) {
    check2.value = false
    hasScrolled.value = false
    activeSection.value = props.section
  }
})

// Also react if the caller changes `section` while modal is open
watch(() => props.section, (val) => {
  activeSection.value = val
})

/* ─────────────────────────────
   COMPUTED LOGIC
───────────────────────────── */
const canAccept = computed(() => check2.value)

/* ─────────────────────────────
   CHECKBOX TOGGLE
───────────────────────────── */
const toggleCheck = () => {
  check2.value = !check2.value
}

/* ─────────────────────────────
   SCROLL
───────────────────────────── */
const handleScroll = (e) => {
  if (e.target.scrollTop > 80) {
    hasScrolled.value = true
  }
}

/* ─────────────────────────────
   ACCEPT TERMS
───────────────────────────── */
const handleAccept = async () => {
  if (!canAccept.value) return

  try {
    const token = localStorage.getItem('token')
    await axios.post('/api/terms/accept', {}, { headers: { Authorization: `Bearer ${token}` } })
    emit('accept')
  } catch (e) {
    console.error('Failed to accept terms:', e)
  }
}

/* ─────────────────────────────
   DECLINE
───────────────────────────── */
const handleDecline = async () => {
  emit('decline')
  try {
    const token = localStorage.getItem('token')
    await axios.post('/api/logout', {}, { headers: { Authorization: `Bearer ${token}` } })
  } catch (e) {
    console.error('Logout error:', e)
  }
  localStorage.removeItem('token')
  router.push('/login')
}
</script>

<style scoped>
/* Overlay */
.terms-overlay {
  position: fixed;
  inset: 0;
  z-index: 999999;
  background: rgba(8, 24, 8, 0.72);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  font-family: 'Segoe UI', system-ui, sans-serif;
}

/* Modal box */
.terms-modal {
  background: var(--surface-color);
  border-radius: 16px;
  width: 100%;
  max-width: 600px;
  max-height: 92vh;
  display: flex;
  position: relative;
  flex-direction: column;
  overflow: hidden;
  box-shadow:
    0 0 0 1px var(--border-color),
    0 32px 72px rgba(0, 0, 0, 0.40);
  transition: background-color 0.2s, border-color 0.2s;
}

/* ── Header ─────────────────────────────────────── */
.terms-header {
  padding: 28px 32px 22px;
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-secondary);
  flex-shrink: 0;
  position: relative;
  transition: background-color 0.2s, border-color 0.2s;
}

.terms-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.logo-icon {
  width: 36px;
  height: 36px;
  object-fit: contain;
  border-radius: 8px;
}

.logo-text {
  font-size: 15px;
  font-weight: 700;
  color: var(--primary-color);
  letter-spacing: 0.2px;
}

/* ── Tab switcher (view mode) ────────────────────── */
.terms-tabs {
  display: flex;
  gap: 6px;
  margin-bottom: 12px;
}

.terms-tab {
  padding: 7px 16px;
  border-radius: 20px;
  border: 1.5px solid var(--border-color);
  background: var(--surface-color);
  color: var(--text-secondary);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.18s ease;
  font-family: inherit;
  line-height: 1;
}

.terms-tab:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
  background: var(--surface-hover);
}

.terms-tab.active {
  background: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--white-const);
  font-weight: 600;
}

.terms-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 8px;
  line-height: 1.25;
  letter-spacing: -0.3px;
}

.terms-subtitle {
  font-size: 13.5px;
  color: var(--text-secondary);
  margin: 0;
  line-height: 1.65;
}

/* ── Scrollable Body ─────────────────────────────── */
.terms-body {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
  background: var(--surface-color);
}

.terms-body::-webkit-scrollbar {
  width: 5px;
}

.terms-body::-webkit-scrollbar-track {
  background: var(--bg-color);
}

.terms-body::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 4px;
}

/* Sections */
.terms-section {
  padding: 20px 20px;
  border-bottom: 1px solid var(--border-color);
  margin: 0;
}

.terms-section:last-of-type {
  border-bottom: none;
}

.terms-section h3 {
  font-size: 11px;
  font-weight: 700;
  color: var(--primary-color);
  margin: 0 0 8px;
  text-transform: uppercase;
  letter-spacing: 0.9px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  min-width: 20px;
  background: var(--primary-color);
  color: var(--white-const);
  border-radius: 50%;
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: 0;
}

.terms-section p {
  font-size: 13.5px;
  color: var(--text-primary);
  line-height: 1.75;
  margin: 0 0 8px;
}

.terms-section p:last-child {
  margin-bottom: 0;
}

.terms-section strong {
  color: var(--primary-color);
  font-weight: 600;
}

.body-spacer {
  height: 16px;
}

/* ── Scroll hint ─────────────────────────────────── */
.scroll-hint-bar {
  text-align: center;
  padding: 8px 0 4px;
  flex-shrink: 0;
  transition: opacity 0.35s ease, max-height 0.35s ease, padding 0.35s ease;
  max-height: 40px;
  overflow: hidden;
}

.scroll-hint-bar.hidden {
  opacity: 0;
  max-height: 0;
  padding: 0;
  pointer-events: none;
}

.scroll-hint-text {
  font-size: 11.5px;
  color: var(--primary-color);
  opacity: 0.8;
  display: inline-block;
  animation: bob 1.6s ease-in-out infinite;
}

@keyframes bob {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(3px); }
}

/* ── Footer ──────────────────────────────────────── */
.terms-footer {
  padding: 16px 32px 24px;
  border-top: 1px solid var(--border-color);
  background: var(--bg-secondary);
  flex-shrink: 0;
  transition: background-color 0.2s, border-color 0.2s;
}

.checkboxes {
  display: flex;
  flex-direction: column;
  gap: 9px;
  margin-bottom: 16px;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 11px;
  cursor: pointer;
  user-select: none;
  padding: 11px 13px;
  border-radius: 9px;
  border: 1.5px solid var(--border-color);
  background: var(--surface-color);
  transition: border-color 0.18s ease, background 0.18s ease;
}

.checkbox-label:hover {
  border-color: var(--primary-color);
  background: var(--surface-hover);
}

.checkbox-label.checked {
  border-color: var(--primary-color);
  background: var(--surface-hover);
}

.custom-check {
  width: 19px;
  height: 19px;
  min-width: 19px;
  border-radius: 5px;
  border: 2px solid var(--border-color);
  background: var(--bg-color);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 1px;
  transition: background 0.18s ease, border-color 0.18s ease;
  flex-shrink: 0;
}

.checkbox-label.checked .custom-check {
  background: var(--primary-color);
  border-color: var(--primary-color);
}

.custom-check svg {
  width: 11px;
  height: 9px;
}

.check-text {
  font-size: 13px;
  color: var(--text-primary);
  line-height: 1.55;
}

.check-text strong {
  color: var(--primary-color);
}

/* Buttons */
.terms-actions {
  display: flex;
  gap: 10px;
}

.btn-decline {
  flex: 0 0 auto;
  padding: 11px 20px;
  border-radius: 9px;
  border: 1.5px solid var(--border-color);
  background: var(--surface-color);
  color: var(--text-secondary);
  font-size: 13.5px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.18s ease;
  font-family: inherit;
}

.btn-decline:hover {
  border-color: #d9534f;
  color: #d9534f;
  background: rgba(217, 83, 79, 0.1);
}

.btn-accept {
  flex: 1;
  padding: 11px 20px;
  border-radius: 9px;
  border: none;
  background: var(--border-color);
  color: var(--text-secondary);
  font-size: 13.5px;
  font-weight: 600;
  cursor: not-allowed;
  transition: all 0.22s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-family: inherit;
}

.btn-accept.active {
  background: var(--primary-color);
  color: var(--white-const);
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(22, 163, 74, 0.28);
}

.btn-accept.active:hover {
  background: var(--secondary-color);
  box-shadow: 0 6px 18px rgba(22, 163, 74, 0.36);
}

.btn-icon {
  font-size: 15px;
  transition: transform 0.2s ease;
}

.btn-accept.active:hover .btn-icon {
  transform: translateX(3px);
}

/* ── Close button (view mode) ────────────────────── */
.modal-close-btn {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: var(--surface-color);
  border-radius: 50%;
  cursor: pointer;
  font-size: 16px;
  color: var(--primary-color);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  z-index: 9999;
  transition: background 0.18s ease, transform 0.18s ease;
}

.modal-close-btn:hover {
  background: var(--surface-hover);
  transform: scale(1.05);
}

/* ── Transition ──────────────────────────────────── */
.modal-fade-enter-active {
  transition: opacity 0.25s ease;
}
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-active .terms-modal {
  transition: transform 0.28s cubic-bezier(0.34, 1.4, 0.64, 1), opacity 0.25s ease;
}
.modal-fade-leave-active .terms-modal {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-from .terms-modal {
  transform: translateY(18px) scale(0.97);
  opacity: 0;
}
.modal-fade-leave-to .terms-modal {
  transform: translateY(10px) scale(0.98);
  opacity: 0;
}
</style>