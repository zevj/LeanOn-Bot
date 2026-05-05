<template>
    <div class="layout">
        <SidebarAdmin
            :open="sidebarOpen"
            @toggle="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)"
        />

        <main class="main-area">
            <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)" />

            <div class="main-container">
                <div class="header-title">
                    <h1 class="title">Crisis Alerts</h1>
                    <p class="subtext">Flagged conversations requiring attention</p>
                </div>

                <!-- STATS -->
                <div class="whole-stat-card">
                    <div class="stat-card-wrap s-high">
                        <div class="stat-left">
                            <span class="stat-label">High</span>
                            <span class="stat-number">{{ statsData.high_count ?? 0 }}</span>
                        </div>
                        <div class="stat-icon icon-high"><i class="bx bx-error"></i></div>
                    </div>

                    <div class="stat-card-wrap s-severe">
                        <div class="stat-left">
                            <span class="stat-label">Severe</span>
                            <span class="stat-number">{{ statsData.severe_count ?? 0 }}</span>
                        </div>
                        <div class="stat-icon icon-severe"><i class="bx bxs-bell-ring"></i></div>
                    </div>

                    <div class="stat-card-wrap s-moderate">
                        <div class="stat-left">
                            <span class="stat-label">Moderate</span>
                            <span class="stat-number">{{ statsData.moderate_count ?? 0 }}</span>
                        </div>
                        <div class="stat-icon icon-moderate"><i class="bx bx-info-circle"></i></div>
                    </div>

                    <div class="stat-card-wrap s-low">
                        <div class="stat-left">
                            <span class="stat-label">Low</span>
                            <span class="stat-number">{{ statsData.low_count ?? 0 }}</span>
                        </div>
                        <div class="stat-icon icon-low"><i class="bx bx-check-shield"></i></div>
                    </div>
                </div>

                <!-- KEYWORD REFERENCE -->
                <div class="section-card">
                    <p class="section-label">Keyword reference</p>
                    <div class="keyword-severity-tabs">
                        <button
                            v-for="level in severityLevels"
                            :key="level.label"
                            class="severity-tab"
                            :class="[`severity-tab--${level.key}`, { active: activeSeverity === level.key }]"
                            @click="activeSeverity = level.key"
                        >
                            {{ level.label }}
                        </button>
                    </div>
                    <div class="keyword-tags" :class="activeSeverity">
                        <span
                            v-for="kw in currentKeywords"
                            :key="kw"
                            class="keyword-tag"
                        >
                            {{ kw }}
                        </span>
                    </div>
                </div>

                <!-- FILTERS -->
                <div class="alert-filters">
                    <select v-model="filterPriority" class="filter-select" @change="fetchAlerts">
                        <option value="">All priorities</option>
                        <option value="high">High</option>
                        <option value="severe">Severe</option>
                        <option value="moderate">Moderate</option>
                        <option value="low">Low</option>
                    </select>
                    <select v-model="filterStatus" class="filter-select" @change="fetchAlerts">
                        <option value="">All statuses</option>
                        <option value="new">New</option>
                        <option value="reviewed">Under review</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>

                <!-- ALERT LIST -->
                <div class="alert-list">
                    <div
                        v-for="alert in alerts"
                        :key="alert.id"
                        class="alert-card"
                        :class="`p-${alert.severity}`"
                    >
                        <div class="alert-card-left">
                            <div class="alert-meta">
                                <span class="badge" :class="`b-${alert.severity}`">
                                    {{ capitalize(alert.severity) }}
                                </span>
                                <span class="badge" :class="`b-${alert.status}`">
                                    <i v-if="alert.status === 'new'" class="bx bx-error-circle"></i>
                                    {{ alert.status === 'reviewed' ? 'Under review' : capitalize(alert.status) }}
                                </span>
                                <span class="alert-time">
                                    <i class="bx bx-time-five"></i> {{ formatTime(alert.created_at) }}
                                </span>
                            </div>
                            <p class="alert-message">"{{ alert.message }}"</p>
                            <div class="alert-keywords-row">
                                <span class="alert-keywords-label">Keywords:</span>
                                <span
                                    v-for="kw in (alert.detected_keywords || [])"
                                    :key="kw"
                                    class="alert-keyword-tag"
                                    :class="`keyword--${alert.severity}`"
                                >
                                    {{ kw }}
                                </span>
                            </div>
                            <p class="alert-user">{{ alert.user_display }} · {{ alert.masked_email }}</p>
                        </div>
                        <div class="alert-card-actions">
                            <button class="action-btn action-btn--email" @click="openEmailModal(alert)">
                                <i class="bx bx-send"></i> Email
                            </button>
                            <button
                                class="action-btn action-btn--review"
                                @click="updateStatus(alert, 'reviewed')"
                                :disabled="alert.status === 'reviewed'"
                            >
                                <i class="bx bx-search-alt"></i> Review
                            </button>
                            <button
                                class="action-btn action-btn--resolve"
                                @click="updateStatus(alert, 'resolved')"
                                :disabled="alert.status === 'resolved'"
                            >
                                <i class="bx bx-check"></i> Resolve
                            </button>
                        </div>
                    </div>

                    <p v-if="alerts.length === 0 && !loading" class="no-alerts">
                        No alerts match the current filters.
                    </p>
                    <p v-if="loading" class="no-alerts">Loading alerts...</p>
                </div>
            </div>
        </main>


        <Teleport to="body">
    <Transition name="modal-fade">
        <div v-if="emailModal.visible" class="email-modal-overlay" @click.self="closeEmailModal">
            <div class="email-modal">

                <!-- Header -->
                <div class="email-modal-header">
                    <div class="email-modal-header-left">
                        <div class="email-modal-icon"><i class="bx bx-send"></i></div>
                        <div>
                            <p class="email-modal-title">Send Crisis Alert Email</p>
                            <p class="email-modal-subtitle">Review and edit before sending</p>
                        </div>
                    </div>
                    <button class="email-modal-close" @click="closeEmailModal">
                        <i class="bx bx-x"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="email-modal-body">
                    <div class="email-field-group">
                        <span class="email-field-label">To</span>
                        <div class="email-field-value">{{ emailModal.maskedEmail }}</div>
                    </div>

                    <div class="email-field-group">
                        <span class="email-field-label">Subject</span>
                        <div class="email-field-value">{{ emailModal.subject }}</div>
                    </div>

                    <div class="email-field-group">
                        <span class="email-field-label">Severity</span>
                        <div style="padding: 6px 0;">
                            <span class="badge" :class="`b-${emailModal.severity}`">
                                {{ capitalize(emailModal.severity) }}
                            </span>
                        </div>
                    </div>

                    <div class="email-field-group">
                        <span class="email-field-label">Message body</span>
                        <textarea
                            class="email-field-value email-field-textarea"
                            v-model="emailModal.body"
                        ></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="email-modal-footer">
                    <button class="action-btn" @click="closeEmailModal">Cancel</button>
                    <button class="action-btn action-btn--email" @click="sendEmail">
                        <i class="bx bx-send"></i> Send Email
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import SidebarAdmin from '@/components/sidebarAdmin.vue';
import HeaderAdmin from '@/components/headerAdmin.vue';

const toast = useToast();
const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false');
const loading = ref(false);

// ── Keyword Reference ──────────────────────────────────────────
const severityLevels = [
    { label: 'High',     key: 'high'     },
    { label: 'Severe',   key: 'severe'   },
    { label: 'Moderate', key: 'moderate' },
    { label: 'Low',      key: 'low'      },
];
const activeSeverity = ref('high');

const keywordMap = {
    high:     ['want to die', 'end it all', 'suicide', 'kill myself'],
    severe:   ['hopeless', 'worthless', 'no one understands', 'breaking down', "can't cope"],
    moderate: ['stressed', 'anxious', 'overwhelmed', 'struggling', 'alone'],
    low:      ['sad', 'tired', 'unmotivated', 'worried', 'frustrated'],
};
const currentKeywords = computed(() => keywordMap[activeSeverity.value] ?? []);

// ── Filters ────────────────────────────────────────────────────
const filterPriority = ref('');
const filterStatus   = ref('');

// ── Alert Data ─────────────────────────────────────────────────
const alerts = ref([]);
const statsData = ref({
    high_count: 0,
    severe_count: 0,
    moderate_count: 0,
    low_count: 0,
});

const fetchAlerts = async () => {
    loading.value = true;
    try {
        const token = localStorage.getItem('token');
        const params = {};
        if (filterPriority.value) params.severity = filterPriority.value;
        if (filterStatus.value)   params.status = filterStatus.value;

        const res = await axios.get('/api/admin/crisis-alerts', {
            headers: { Authorization: `Bearer ${token}` },
            params,
        });

        alerts.value = res.data.alerts.data;
        statsData.value = res.data.stats;
    } catch (err) {
        console.error('Failed to fetch crisis alerts:', err);
        toast.error('Failed to load crisis alerts.');
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (alert, newStatus) => {
    try {
        const token = localStorage.getItem('token');
        await axios.patch(`/api/admin/crisis-alerts/${alert.id}`, {
            status: newStatus,
        }, {
            headers: { Authorization: `Bearer ${token}` },
        });

        alert.status = newStatus;
        const label = newStatus === 'reviewed' ? 'under review' : 'resolved';
        toast.success(`Alert from ${alert.user_display} is now ${label}.`, { timeout: 3000 });

        // Refresh stats
        fetchAlerts();
    } catch (err) {
        console.error('Failed to update alert:', err);
        toast.error('Failed to update alert status.');
    }
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleString('en-PH', {
        month: 'numeric', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit', hour12: true,
    });
};

// ── Helpers ────────────────────────────────────────────────────
const capitalize = (str) => str.charAt(0).toUpperCase() + str.slice(1);

onMounted(() => {
    fetchAlerts();
});

/*OPEN EMAIL MODAL*/
// ── Email Modal ────────────────────────────────────────────────
const emailModal = ref({
    visible: false,
    maskedEmail: '',
    subject: '',
    severity: '',
    body: '',
    alertId: null,
});

const openEmailModal = (alert) => {
    const keywords = (alert.detected_keywords || []).join('", "');
    emailModal.value = {
        visible: true,
        maskedEmail: alert.masked_email,
        subject: `Urgent: Crisis Alert — Action Required`,
        severity: alert.severity,
        alertId: alert.id,
        body:
`Dear Student,`,
    };
};

const closeEmailModal = () => {
    emailModal.value.visible = false;
};

const sendEmail = async () => {
    try {
        const token = localStorage.getItem('token');
        await axios.post(`/api/admin/crisis-alerts/${emailModal.value.alertId}/send-email`, {}, {
            headers: { Authorization: `Bearer ${token}` },
        });
        toast.success('Email sent successfully.', { timeout: 3000 });
        closeEmailModal();
    } catch (err) {
        console.error('Failed to send email:', err);
        toast.error('Failed to send email.');
    }
};
</script>

<style scoped src="@/assets/admin/AdminCrisisAlert.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>