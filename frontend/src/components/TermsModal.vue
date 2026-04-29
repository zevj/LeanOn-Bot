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
            <h2 id="terms-title" class="terms-title">Terms &amp; Privacy Policy</h2>
            <p class="terms-subtitle">
              Please read the following carefully before using LeanOn Bot. By accepting, you acknowledge and agree to these terms.
            </p>
          </div>

          <!-- Scrollable Content -->
          <div class="terms-body" ref="termsBody" @scroll="handleScroll">

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
              <h3><span class="section-num">04</span> Data Privacy &amp; Confidentiality</h3>
              <p>
                Your conversations are handled with strict confidentiality. The system stores only the minimum necessary interaction logs to maintain service quality. Your personal identity will <strong>never</strong> be shared with or disclosed to third parties.
              </p>
              <p>
                Anonymized emotional trend data may be reviewed by the Guidance Office solely for program improvement purposes. All data handling is fully compliant with the <strong>Data Privacy Act of the Philippines (Republic Act No. 10173)</strong>.
              </p>
            </section>

            <section class="terms-section">
              <h3><span class="section-num">05</span> AI Limitations</h3>
              <p>
                LeanOn Bot uses rule-based logic and predefined wellness content to generate responses. It may not fully understand complex or nuanced emotional situations. All responses are supportive in nature and should <strong>not</strong> be interpreted as definitive psychological advice or clinical guidance.
              </p>
            </section>

            <section class="terms-section">
              <h3><span class="section-num">06</span> Appropriate Use</h3>
              <p>
                This platform is intended solely for Gordon College students seeking emotional support and mental wellness guidance. Users are expected to engage with the system honestly and responsibly to receive the most effective support.
              </p>
            </section>

            <section class="terms-section">
              <h3><span class="section-num">07</span> Referral to the Guidance Office</h3>
              <p>
                If LeanOn Bot detects repeated or high-risk emotional patterns in your conversations, it may automatically recommend that you consult with the Gordon College Guidance Office. These referral suggestions are supportive and non-mandatory, and are made solely in the interest of your wellbeing.
              </p>
            </section>

            <div class="body-spacer"></div>
          </div>

          <!-- Scroll hint -->
          <div class="scroll-hint-bar" :class="{ hidden: hasScrolled }">
            <span class="scroll-hint-text">↓ Scroll to read all terms</span>
          </div>

          <!-- Footer -->
          <div class="terms-footer">
            <div class="checkboxes">

              <label 
  class="checkbox-label"
  :class="{ 
    checked: check2
  }"
  @click="toggleCheck"
>
                <span class="custom-check">
                  <svg v-if="check2" viewBox="0 0 12 10" fill="none">
                    <path d="M1 5l3.5 3.5L11 1" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
                <span class="check-text">I have read and agree to the <strong>Terms of Service</strong> and <strong>Privacy Policy</strong> of LeanOn Bot.</span>
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
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

defineProps({
  visible: Boolean
})

const emit = defineEmits(['accept', 'decline', 'close'])
const router = useRouter()

/* ─────────────────────────────
   STORAGE KEYS (per user)
───────────────────────────── */
// Removed local storage keys

/* ─────────────────────────────
   STATES
───────────────────────────── */
const check2 = ref(false)
const hasScrolled = ref(false)

/* ─────────────────────────────
   INIT STATE
───────────────────────────── */
// Removed mounted local storage sync

/* ─────────────────────────────
   COMPUTED LOGIC
───────────────────────────── */
const canAccept = computed(() => {
  return check2.value
})

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
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 600px;
  max-height: 92vh;
  display: flex;
  position: relative;
  flex-direction: column;
  overflow: hidden;
  box-shadow:
    0 0 0 1px rgba(14, 96, 8, 0.12),
    0 32px 72px rgba(8, 24, 8, 0.40);
}

/* ── Header ─────────────────────────────────────── */
.terms-header {
  padding: 28px 32px 22px;
  border-bottom: 1px solid #e4ede3;
  background: #f7fbf6;
  flex-shrink: 0;
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
  color: #0E6008;
  letter-spacing: 0.2px;
}

.terms-title {
  font-size: 22px;
  font-weight: 700;
  color: #111e10;
  margin: 0 0 8px;
  line-height: 1.25;
  letter-spacing: -0.3px;
}

.terms-subtitle {
  font-size: 13.5px;
  color: #547150;
  margin: 0;
  line-height: 1.65;
}

/* ── Scrollable Body ─────────────────────────────── */
.terms-body {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}

.terms-body::-webkit-scrollbar {
  width: 5px;
}

.terms-body::-webkit-scrollbar-track {
  background: #f0f5ef;
}

.terms-body::-webkit-scrollbar-thumb {
  background: #b5ceb2;
  border-radius: 4px;
}

/* Sections */
.terms-section {
  padding: 20px 20px;
  border-bottom: 1px solid #edf3ec;
  margin: 0; /* remove uneven spacing */
}

.terms-section:last-of-type {
  border-bottom: none;
}

.terms-section h3 {
  font-size: 11px;
  font-weight: 700;
  color: #0E6008;
  margin: 0 0 8px; /* tighter + consistent */
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
  background: #0E6008;
  color: white;
  border-radius: 50%;
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: 0;
}

.terms-section p {
  font-size: 13.5px;
  color: #374535;
  line-height: 1.75;
  margin: 0 0 8px;
}

.terms-section p:last-child {
  margin-bottom: 0;
}

.terms-section strong {
  color: #111e10;
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
  color: #97b894;
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
  border-top: 1px solid #e4ede3;
  background: #f7fbf6;
  flex-shrink: 0;
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
  border: 1.5px solid #d2e8cf;
  background: #ffffff;
  transition: border-color 0.18s ease, background 0.18s ease;
}

.checkbox-label:hover {
  border-color: #0E6008;
  background: #f3faf2;
}

.checkbox-label.checked {
  border-color: #0E6008;
  background: #eef7ed;
}

.custom-check {
  width: 19px;
  height: 19px;
  min-width: 19px;
  border-radius: 5px;
  border: 2px solid #b5ceb2;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 1px;
  transition: background 0.18s ease, border-color 0.18s ease;
  flex-shrink: 0;
}

.checkbox-label.checked .custom-check {
  background: #0E6008;
  border-color: #0E6008;
}

.custom-check svg {
  width: 11px;
  height: 9px;
}

.check-text {
  font-size: 13px;
  color: #374535;
  line-height: 1.55;
}

.check-text strong {
  color: #111e10;
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
  border: 1.5px solid #d2e8cf;
  background: white;
  color: #6a8068;
  font-size: 13.5px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.18s ease;
  font-family: inherit;
}

.btn-decline:hover {
  border-color: #d9534f;
  color: #d9534f;
  background: #fff6f6;
}

.btn-accept {
  flex: 1;
  padding: 11px 20px;
  border-radius: 9px;
  border: none;
  background: #cce3ca;
  color: #8aad87;
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
  background: #0E6008;
  color: white;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(14, 96, 8, 0.28);
}

.btn-accept.active:hover {
  background: #0b5006;
  box-shadow: 0 6px 18px rgba(14, 96, 8, 0.36);
}

.btn-icon {
  font-size: 15px;
  transition: transform 0.2s ease;
}

.btn-accept.active:hover .btn-icon {
  transform: translateX(3px);
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

.modal-close-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 34px;
  height: 34px;

  display: flex;
  align-items: center;
  justify-content: center;

  border: none;
  background: white;
  border-radius: 50%;

  cursor: pointer;
  font-size: 18px;

  color: #0E6008;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);

  z-index: 9999;
}

.modal-close-btn:hover {
  background: #f2f7f1;
  transform: scale(1.05);
}
.checkbox-label.disabled {
  opacity: 0.6;
  pointer-events: none;
}

.btn-accept:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>