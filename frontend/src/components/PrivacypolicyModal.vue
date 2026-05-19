<template>
  <Teleport to="body">
    <Transition name="pp-fade">
      <div v-if="visible" class="pp-overlay" @click.self="$emit('close')">
        <div class="pp-modal" role="dialog" aria-modal="true" aria-labelledby="pp-title">

          <!-- Header -->
          <div class="pp-header">
            <div class="pp-logo">
              <img src="/leanOnBot.png" class="pp-logo-icon" alt="LeanOn Bot Logo" />
              <span class="pp-logo-text">LeanOn Bot</span>
            </div>
            <div class="pp-title-row">
              <div class="pp-badge">
                <i class="bx bx-shield"></i>
                Privacy Policy
              </div>
            </div>
            <p class="pp-subtitle">How we collect, use, and protect your personal information.</p>
          </div>

          <!-- Scrollable Body -->
          <div class="pp-body">

            <section class="pp-section">
              <h3><span class="pp-num">01</span> Data We Collect</h3>
              <p>
                We collect only the information necessary to provide and improve LeanOn Bot: your student account details (name, email, student ID) and anonymized records of your chat interactions. No sensitive personal data beyond what you voluntarily share in conversation is collected.
              </p>
            </section>

            <section class="pp-section">
              <h3><span class="pp-num">02</span> How We Use Your Data</h3>
              <p>
                Your information is used solely to deliver personalized wellness support, maintain service quality, and generate anonymized trend reports for the Gordon College Guidance Office. We do <strong>not</strong> use your data for advertising, profiling, or any commercial purpose.
              </p>
            </section>

            <section class="pp-section">
              <h3><span class="pp-num">03</span> Confidentiality &amp; Disclosure</h3>
              <p>
                Your personal identity will <strong>never</strong> be shared with or disclosed to third parties. Anonymized emotional trend data may be reviewed by the Guidance Office solely for program improvement purposes. Individually identifiable information is only disclosed if required by Philippine law or to prevent imminent harm.
              </p>
            </section>

            <section class="pp-section">
              <h3><span class="pp-num">04</span> Data Storage &amp; Security</h3>
              <p>
                All data is stored on secured servers with industry-standard encryption in transit and at rest. Access is strictly limited to authorized system administrators and Guidance Office personnel under confidentiality agreements.
              </p>
            </section>

            <section class="pp-section">
              <h3><span class="pp-num">05</span> Data Retention</h3>
              <p>
                Conversation logs are retained for a maximum of one academic year after your last active session, after which they are permanently deleted. You may request deletion of your data at any time by contacting the Guidance Office.
              </p>
            </section>

            <section class="pp-section">
              <h3><span class="pp-num">06</span> Your Rights</h3>
              <p>
                Under the <strong>Data Privacy Act of the Philippines (Republic Act No. 10173)</strong>, you have the right to access, correct, and request deletion of your personal data. To exercise these rights, contact the Gordon College Data Protection Officer.
              </p>
            </section>

            <section class="pp-section">
              <h3><span class="pp-num">07</span> Cookies &amp; Local Storage</h3>
              <p>
                LeanOn Bot uses browser local storage exclusively to maintain your authenticated session. No third-party tracking cookies are used.
              </p>
            </section>

            <div class="pp-spacer"></div>
          </div>

          <!-- Footer -->
          <div class="pp-footer">
            <button class="pp-close-footer-btn" @click="$emit('close')">Close</button>
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
.pp-overlay {
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
.pp-modal {
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
.pp-header {
  padding: 24px 28px 18px;
  border-bottom: 1px solid #e4ede3;
  background: #f7fbf6;
  flex-shrink: 0;
}

.pp-logo {
  display: flex;
  align-items: center;
  gap: 9px;
  margin-bottom: 14px;
}

.pp-logo-icon {
  width: 30px;
  height: 30px;
  object-fit: contain;
  border-radius: 7px;
}

.pp-logo-text {
  font-size: 13.5px;
  font-weight: 700;
  color: #0E6008;
  letter-spacing: 0.2px;
}

.pp-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

/* Shield badge — slightly different teal accent to visually distinguish from Terms */
.pp-badge {
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

.pp-badge i {
  font-size: 14px;
}

.pp-close-btn {
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

.pp-close-btn:hover {
  background: #f3faf2;
  transform: scale(1.08);
}

.pp-subtitle {
  font-size: 12.5px;
  color: #547150;
  margin: 0;
  line-height: 1.55;
}

/* ── Body ── */
.pp-body {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}

.pp-body::-webkit-scrollbar { width: 5px; }
.pp-body::-webkit-scrollbar-track { background: #f0f5ef; }
.pp-body::-webkit-scrollbar-thumb { background: #b5ceb2; border-radius: 4px; }

.pp-section {
  padding: 18px 20px;
  border-bottom: 1px solid #edf3ec;
  margin: 0;
}

.pp-section:last-of-type {
  border-bottom: none;
}

.pp-section h3 {
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

.pp-num {
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

.pp-section p {
  font-size: 13px;
  color: #374535;
  line-height: 1.75;
  margin: 0;
}

.pp-section strong { color: #111e10; font-weight: 600; }

.pp-spacer { height: 12px; }

/* ── Footer ── */
.pp-footer {
  padding: 14px 28px 20px;
  border-top: 1px solid #e4ede3;
  background: #f7fbf6;
  flex-shrink: 0;
  display: flex;
  justify-content: flex-end;
}

.pp-close-footer-btn {
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

.pp-close-footer-btn:hover {
  background: #0E6008;
  border-color: #0E6008;
  color: white;
}

/* ── Transition ── */
.pp-fade-enter-active { transition: opacity 0.25s ease; }
.pp-fade-leave-active { transition: opacity 0.2s ease; }
.pp-fade-enter-active .pp-modal {
  transition: transform 0.28s cubic-bezier(0.34, 1.4, 0.64, 1), opacity 0.25s ease;
}
.pp-fade-leave-active .pp-modal {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.pp-fade-enter-from,
.pp-fade-leave-to { opacity: 0; }
.pp-fade-enter-from .pp-modal {
  transform: translateY(18px) scale(0.97);
  opacity: 0;
}
.pp-fade-leave-to .pp-modal {
  transform: translateY(10px) scale(0.98);
  opacity: 0;
}
</style>