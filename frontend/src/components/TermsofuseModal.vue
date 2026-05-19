<template>
  <Teleport to="body">
    <Transition name="tou-fade">
      <div v-if="visible" class="tou-overlay" @click.self="$emit('close')">
        <div class="tou-modal" role="dialog" aria-modal="true" aria-labelledby="tou-title">

          <!-- Header -->
          <div class="tou-header">
            <div class="tou-logo">
              <img src="/leanOnBot.png" class="tou-logo-icon" alt="LeanOn Bot Logo" />
              <span class="tou-logo-text">LeanOn Bot</span>
            </div>
            <div class="tou-title-row">
              <div class="tou-badge">
                <i class="bx bx-file"></i>
                Terms of Use
              </div>
            </div>
            <p class="tou-subtitle">Governing rules for using LeanOn Bot as a Gordon College student.</p>
          </div>

          <!-- Scrollable Body -->
          <div class="tou-body">

            <section class="tou-section">
              <h3><span class="tou-num">01</span> Purpose of This Service</h3>
              <p>
                LeanOn Bot is an AI-assisted mental wellness support chatbot designed exclusively for <strong>Gordon College students</strong>. It provides a confidential, judgment-free digital space where students can express emotions, receive supportive responses, and access wellness resources at any time.
              </p>
            </section>

            <section class="tou-section">
              <h3><span class="tou-num">02</span> Not a Replacement for Professional Help</h3>
              <p>
                LeanOn Bot is a <strong>first-line support tool only</strong>. It does <em>not</em> provide medical diagnoses, psychological assessments, or professional counseling services. If you are experiencing a mental health crisis or emergency, please contact the <strong>Gordon College Guidance and Counseling Unit</strong> or a licensed mental health professional immediately.
              </p>
            </section>

            <section class="tou-section">
              <h3><span class="tou-num">03</span> Emergency Situations</h3>
              <p>
                LeanOn Bot does <strong>not</strong> provide real-time crisis intervention or emergency hotline integration in its current release. If you or someone you know is in immediate danger, please contact emergency services or a trusted adult right away.
              </p>
            </section>

            <section class="tou-section">
              <h3><span class="tou-num">04</span> AI Limitations</h3>
              <p>
                LeanOn Bot uses rule-based logic and predefined wellness content to generate responses. It may not fully understand complex or nuanced emotional situations. All responses are supportive in nature and should <strong>not</strong> be interpreted as definitive psychological advice or clinical guidance.
              </p>
            </section>

            <section class="tou-section">
              <h3><span class="tou-num">05</span> Appropriate Use</h3>
              <p>
                This platform is intended solely for Gordon College students seeking emotional support and mental wellness guidance. Users are expected to engage with the system honestly and responsibly to receive the most effective support.
              </p>
            </section>

            <section class="tou-section">
              <h3><span class="tou-num">06</span> Referral to the Guidance Office</h3>
              <p>
                If LeanOn Bot detects repeated or high-risk emotional patterns in your conversations, it may automatically recommend that you consult with the Gordon College Guidance Office. These referral suggestions are supportive and non-mandatory, and are made solely in the interest of your wellbeing.
              </p>
            </section>

            <div class="tou-spacer"></div>
          </div>

          <!-- Footer -->
          <div class="tou-footer">
            <button class="tou-close-footer-btn" @click="$emit('close')">Close</button>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  visible: {
    type: Boolean,
    default: false
  }
})

defineEmits(['close'])
</script>

<style scoped>
/* Overlay */
.tou-overlay {
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
.tou-modal {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 580px;
  max-height: 88vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: relative;
  box-shadow:
    0 0 0 1px rgba(14, 96, 8, 0.12),
    0 32px 72px rgba(8, 24, 8, 0.40);
}

/* ── Header ── */
.tou-header {
  padding: 24px 28px 18px;
  border-bottom: 1px solid #e4ede3;
  background: #f7fbf6;
  flex-shrink: 0;
}

.tou-logo {
  display: flex;
  align-items: center;
  gap: 9px;
  margin-bottom: 14px;
}

.tou-logo-icon {
  width: 30px;
  height: 30px;
  object-fit: contain;
  border-radius: 7px;
}

.tou-logo-text {
  font-size: 13.5px;
  font-weight: 700;
  color: #0E6008;
  letter-spacing: 0.2px;
}

.tou-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.tou-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: #0E6008;
  color: white;
  font-size: 13px;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: 20px;
  letter-spacing: 0.2px;
}

.tou-badge i {
  font-size: 14px;
}

.tou-close-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: white;
  border-radius: 50%;
  cursor: pointer;
  font-size: 15px;
  color: #0E6008;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: background 0.18s ease, transform 0.18s ease;
  flex-shrink: 0;
}

.tou-close-btn:hover {
  background: #f2f7f1;
  transform: scale(1.08);
}

.tou-subtitle {
  font-size: 12.5px;
  color: #547150;
  margin: 0;
  line-height: 1.55;
}

/* ── Body ── */
.tou-body {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}

.tou-body::-webkit-scrollbar { width: 5px; }
.tou-body::-webkit-scrollbar-track { background: #f0f5ef; }
.tou-body::-webkit-scrollbar-thumb { background: #b5ceb2; border-radius: 4px; }

.tou-section {
  padding: 18px 20px;
  border-bottom: 1px solid #edf3ec;
  margin: 0;
}

.tou-section:last-of-type {
  border-bottom: none;
}

.tou-section h3 {
  font-size: 10.5px;
  font-weight: 700;
  color: #0E6008;
  margin: 0 0 8px;
  text-transform: uppercase;
  letter-spacing: 0.9px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.tou-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  min-width: 20px;
  background: #0E6008;
  color: white;
  border-radius: 50%;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0;
}

.tou-section p {
  font-size: 13px;
  color: #374535;
  line-height: 1.75;
  margin: 0;
}

.tou-section strong { color: #111e10; font-weight: 600; }

.tou-spacer { height: 12px; }

/* ── Footer ── */
.tou-footer {
  padding: 14px 28px 20px;
  border-top: 1px solid #e4ede3;
  background: #f7fbf6;
  flex-shrink: 0;
  display: flex;
  justify-content: flex-end;
}

.tou-close-footer-btn {
  padding: 9px 24px;
  border-radius: 9px;
  border: 1.5px solid #d2e8cf;
  background: white;
  color: #0E6008;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.18s ease;
}

.tou-close-footer-btn:hover {
  background: #0E6008;
  border-color: #0E6008;
  color: white;
}

/* ── Transition ── */
.tou-fade-enter-active { transition: opacity 0.25s ease; }
.tou-fade-leave-active { transition: opacity 0.2s ease; }
.tou-fade-enter-active .tou-modal {
  transition: transform 0.28s cubic-bezier(0.34, 1.4, 0.64, 1), opacity 0.25s ease;
}
.tou-fade-leave-active .tou-modal {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.tou-fade-enter-from,
.tou-fade-leave-to { opacity: 0; }
.tou-fade-enter-from .tou-modal {
  transform: translateY(18px) scale(0.97);
  opacity: 0;
}
.tou-fade-leave-to .tou-modal {
  transform: translateY(10px) scale(0.98);
  opacity: 0;
}
</style>