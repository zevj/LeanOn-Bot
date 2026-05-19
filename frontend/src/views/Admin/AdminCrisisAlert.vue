<template>
    <div class="layout">
        <!-- Sidebar -->
        <SidebarAdmin
            :open="sidebarOpen"
            @toggle="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)"
        />

        <main class="main-area">
            <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)" />

            <div class="main-container">
                <div class="header-title fade-in">
                    <h1 class="title">Crisis Alerts</h1>
                    <p class="subtext">Flagged conversations requiring attention</p>
                </div>

                <!-- STATS — order: Severe → Moderate → Low -->
                <div class="whole-stat-card">
                    <div class="stat-card-wrap s-severe stagger-1">
                        <div class="stat-left">
                            <span class="stat-label">Severe</span>
                            <span class="stat-number">{{ statsData.severe_count ?? 0 }}</span>
                        </div>
                        <div class="stat-icon icon-severe"><i class="bx bxs-bell-ring"></i></div>
                    </div>
                    <div class="stat-card-wrap s-moderate stagger-2">
                        <div class="stat-left">
                            <span class="stat-label">Moderate</span>
                            <span class="stat-number">{{ statsData.moderate_count ?? 0 }}</span>
                        </div>
                        <div class="stat-icon icon-moderate"><i class="bx bx-info-circle"></i></div>
                    </div>
                    <div class="stat-card-wrap s-low stagger-3">
                        <div class="stat-left">
                            <span class="stat-label">Low</span>
                            <span class="stat-number">{{ statsData.low_count ?? 0 }}</span>
                        </div>
                        <div class="stat-icon icon-low"><i class="bx bx-check-shield"></i></div>
                    </div>
                </div>

                <!-- KEYWORD REFERENCE -->
                <div class="section-card fade-in">
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
                        <span v-for="kw in currentKeywords" :key="kw" class="keyword-tag">
                            {{ kw }}
                        </span>
                    </div>
                </div>

                <!-- ── AWAITING CLASSIFICATION ── -->
                <div class="alert-section fade-in" v-if="unclassifiedAlerts.length > 0">
                    <div class="alert-section-header">
                        <div class="alert-section-label-group">
                            <span class="alert-section-dot dot-pending"></span>
                            <span class="alert-section-label">Awaiting Classification</span>
                            <span class="alert-section-count">{{ unclassifiedAlerts.length }}</span>
                        </div>
                        <p class="alert-section-hint">Assign a severity level to each flagged message below</p>
                    </div>

                    <div class="alert-list">
                        <div
                            v-for="alert in pagedUnclassified"
                            :key="alert.id"
                            class="alert-card alert-card--plain"
                            :class="{ 'is-assigning': assigningId === alert.id }"
                        >
                            <div class="alert-card-left">
                                <!-- Timestamp only — no status badge -->
                                <div class="alert-meta">
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
                                        class="alert-keyword-tag keyword--plain"
                                    >{{ kw }}</span>
                                </div>

                                <p class="alert-user">{{ alert.user_display }} · {{ alert.masked_email }}</p>

                                <!-- SEVERITY ASSIGNMENT -->
                                <div class="severity-assign-row">
                                    <span class="severity-assign-label">Assign severity:</span>
                                    <div class="severity-assign-buttons">
                                        <button
                                            class="severity-assign-btn severity-assign-btn--severe"
                                            :class="{ selected: pendingSeverity[alert.id] === 'severe' }"
                                            @click="setPendingSeverity(alert.id, 'severe')"
                                        >
                                            <i class="bx bxs-bell-ring"></i> Severe
                                        </button>
                                        <button
                                            class="severity-assign-btn severity-assign-btn--moderate"
                                            :class="{ selected: pendingSeverity[alert.id] === 'moderate' }"
                                            @click="setPendingSeverity(alert.id, 'moderate')"
                                        >
                                            <i class="bx bx-info-circle"></i> Moderate
                                        </button>
                                        <button
                                            class="severity-assign-btn severity-assign-btn--low"
                                            :class="{ selected: pendingSeverity[alert.id] === 'low' }"
                                            @click="setPendingSeverity(alert.id, 'low')"
                                        >
                                            <i class="bx bx-check-shield"></i> Low
                                        </button>
                                    </div>
                                    <button
                                        v-if="pendingSeverity[alert.id]"
                                        class="severity-confirm-btn"
                                        :disabled="assigningId === alert.id"
                                        @click="confirmSeverity(alert)"
                                    >
                                        <i class="bx bx-check"></i>
                                        {{ assigningId === alert.id ? 'Saving…' : 'Confirm' }}
                                    </button>
                                </div>
                            </div>

                            <div class="alert-card-actions">
                                <button class="action-btn action-btn--email" @click="openEmailModal(alert)">
                                    <i class="bx bx-send"></i> Email
                                </button>
                                <button class="action-btn action-btn--review" @click="updateStatus(alert, 'reviewed')">
                                    <i class="bx bx-search-alt"></i> Review
                                </button>
                                <button class="action-btn action-btn--resolve" @click="openResolveModal(alert)">
                                    <i class="bx bx-check"></i> Resolve
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination — Awaiting Classification -->
                    <div class="pagination-row">
                        <button
                            class="page-btn"
                            :disabled="unclassifiedPage === 1"
                            @click="unclassifiedPage--"
                        ><i class="bx bx-chevron-left"></i></button>

                        <button
                            v-for="p in pageRange(totalUnclassifiedPages)"
                            :key="p"
                            class="page-btn"
                            :class="{ 'page-btn--active': p === unclassifiedPage }"
                            @click="unclassifiedPage = p"
                        >{{ p }}</button>

                        <button
                            class="page-btn"
                            :disabled="unclassifiedPage === totalUnclassifiedPages"
                            @click="unclassifiedPage++"
                        ><i class="bx bx-chevron-right"></i></button>

                        <span class="page-info">
                            {{ (unclassifiedPage - 1) * PAGE_SIZE + 1 }}–{{ Math.min(unclassifiedPage * PAGE_SIZE, unclassifiedAlerts.length) }}
                            of {{ unclassifiedAlerts.length }}
                        </span>
                    </div>
                </div>

                <!-- ── CLASSIFIED ALERTS — sorted: Severe → Moderate → Low ── -->
                <div class="alert-section fade-in stagger-4">
                    <div class="alert-section-header">
                        <div class="alert-section-label-group">
                            <span class="alert-section-dot dot-classified"></span>
                            <span class="alert-section-label">Classified Alerts</span>
                            <span class="alert-section-count">{{ sortedClassifiedAlerts.length }}</span>
                        </div>
                        <div class="alert-filters">
                            <select v-model="filterPriority" class="filter-select" @change="onFilterChange">
                                <option value="">All priorities</option>
                                <option value="severe">Severe</option>
                                <option value="moderate">Moderate</option>
                                <option value="low">Low</option>
                            </select>
                            <select v-model="filterStatus" class="filter-select" @change="onFilterChange">
                                <option value="">All statuses</option>
                                <option value="new">New</option>
                                <option value="reviewed">Under review</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                    </div>

                    <div class="alert-list">
                        <div
                            v-for="alert in pagedClassified"
                            :key="alert.id"
                            class="alert-card"
                            :class="[`p-${alert.severity}`, { 'is-assigning': assigningId === alert.id }]"
                        >
                            <div class="alert-card-left">
                                <!-- Severity badge + reactive status badge + timestamp -->
                                <div class="alert-meta">
                                    <span class="badge" :class="`b-${alert.severity}`">
                                        {{ capitalize(alert.severity) }}
                                    </span>
                                    <span class="badge" :class="`b-${alert.status}`">
                                        <i v-if="alert.status === 'new'" class="bx bx-error-circle"></i>
                                        <i v-else-if="alert.status === 'reviewed'" class="bx bx-search-alt"></i>
                                        <i v-else-if="alert.status === 'resolved'" class="bx bx-check-circle"></i>
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
                                    >{{ kw }}</span>
                                </div>

                                <p class="alert-user">{{ alert.user_display }} · {{ alert.masked_email }}</p>

                                <!-- SEVERITY RE-ASSIGNMENT -->
                                <div class="severity-assign-row">
                                    <span class="severity-assign-label">Change severity:</span>
                                    <div class="severity-assign-buttons">
                                        <button
                                            class="severity-assign-btn severity-assign-btn--severe"
                                            :class="{ selected: pendingSeverity[alert.id] === 'severe' || (!pendingSeverity[alert.id] && alert.severity === 'severe') }"
                                            @click="setPendingSeverity(alert.id, 'severe')"
                                        >
                                            <i class="bx bxs-bell-ring"></i> Severe
                                        </button>
                                        <button
                                            class="severity-assign-btn severity-assign-btn--moderate"
                                            :class="{ selected: pendingSeverity[alert.id] === 'moderate' || (!pendingSeverity[alert.id] && alert.severity === 'moderate') }"
                                            @click="setPendingSeverity(alert.id, 'moderate')"
                                        >
                                            <i class="bx bx-info-circle"></i> Moderate
                                        </button>
                                        <button
                                            class="severity-assign-btn severity-assign-btn--low"
                                            :class="{ selected: pendingSeverity[alert.id] === 'low' || (!pendingSeverity[alert.id] && alert.severity === 'low') }"
                                            @click="setPendingSeverity(alert.id, 'low')"
                                        >
                                            <i class="bx bx-check-shield"></i> Low
                                        </button>
                                    </div>
                                    <button
                                        v-if="pendingSeverity[alert.id] && pendingSeverity[alert.id] !== alert.severity"
                                        class="severity-confirm-btn"
                                        :disabled="assigningId === alert.id"
                                        @click="confirmSeverity(alert)"
                                    >
                                        <i class="bx bx-check"></i>
                                        {{ assigningId === alert.id ? 'Saving…' : 'Confirm' }}
                                    </button>
                                </div>
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
                                    @click="openResolveModal(alert)"
                                    :disabled="alert.status === 'resolved'"
                                >
                                    <i class="bx bx-check"></i> Resolve
                                </button>
                            </div>
                        </div>

                        <p v-if="sortedClassifiedAlerts.length === 0 && !loading" class="no-alerts">
                            No classified alerts match the current filters.
                        </p>
                        <p v-if="loading" class="no-alerts">Loading alerts...</p>
                    </div>

                    <!-- Pagination — Classified Alerts -->
                    <div class="pagination-row">
                        <button
                            class="page-btn"
                            :disabled="classifiedPage === 1"
                            @click="classifiedPage--"
                        ><i class="bx bx-chevron-left"></i></button>

                        <button
                            v-for="p in pageRange(totalClassifiedPages)"
                            :key="p"
                            class="page-btn"
                            :class="{ 'page-btn--active': p === classifiedPage }"
                            @click="classifiedPage = p"
                        >{{ p }}</button>

                        <button
                            class="page-btn"
                            :disabled="classifiedPage === totalClassifiedPages"
                            @click="classifiedPage++"
                        ><i class="bx bx-chevron-right"></i></button>

                        <span class="page-info">
                            {{ (classifiedPage - 1) * PAGE_SIZE + 1 }}–{{ Math.min(classifiedPage * PAGE_SIZE, sortedClassifiedAlerts.length) }}
                            of {{ sortedClassifiedAlerts.length }}
                        </span>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modals -->
        <Teleport to="body">
            <!-- EMAIL MODAL -->
            <Transition name="modal-fade">
                <div v-if="emailModal.visible" class="email-modal-overlay" @click.self="closeEmailModal">
                    <div class="email-modal">
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
                                    <span v-if="emailModal.severity" class="badge" :class="`b-${emailModal.severity}`">
                                        {{ capitalize(emailModal.severity) }}
                                    </span>
                                    <span v-else class="badge b-unclassified">Unclassified</span>
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
                        <div class="email-modal-footer">
                            <button class="action-btn" @click="closeEmailModal">Cancel</button>
                            <button class="action-btn action-btn--email" @click="sendEmail">
                                <i class="bx bx-send"></i> Send Email
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- RESOLVE CONFIRMATION MODAL -->
            <Transition name="modal-fade">
                <div v-if="resolveModal.visible" class="email-modal-overlay" @click.self="closeResolveModal">
                    <div class="email-modal resolve-modal">
                        <div class="email-modal-header">
                            <div class="email-modal-header-left">
                                <div class="email-modal-icon icon-resolve"><i class="bx bx-check-shield"></i></div>
                                <div>
                                    <p class="email-modal-title">Resolve Alert</p>
                                    <p class="email-modal-subtitle">Confirm resolution status</p>
                                </div>
                            </div>
                            <button class="email-modal-close" @click="closeResolveModal">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                        <div class="email-modal-body resolve-body">
                            <div class="resolve-icon-large"><i class="bx bx-check-circle"></i></div>
                            <p class="resolve-text">
                                Are you sure you want to mark the alert for
                                <strong class="resolve-user">{{ resolveModal.alert?.user_display }}</strong> as resolved?
                            </p>
                            <p class="resolve-subtext">This action indicates that the crisis has been properly addressed and handled.</p>
                        </div>
                        <div class="email-modal-footer">
                            <button class="action-btn" @click="closeResolveModal">Cancel</button>
                            <button class="action-btn action-btn--confirm-resolve" @click="confirmResolve">
                                <i class="bx bx-check"></i> Confirm Resolution
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

// ── Pagination ─────────────────────────────────────────────────
const PAGE_SIZE = 3; // show 3 per page so pagination is always visible

// Helper: generates [1, 2, 3, ...n]
const pageRange = (n) => Array.from({ length: n }, (_, i) => i + 1);

// Awaiting classification pagination
const unclassifiedPage = ref(1);
const totalUnclassifiedPages = computed(() =>
    Math.max(1, Math.ceil(unclassifiedAlerts.value.length / PAGE_SIZE))
);
const pagedUnclassified = computed(() => {
    const start = (unclassifiedPage.value - 1) * PAGE_SIZE;
    return unclassifiedAlerts.value.slice(start, start + PAGE_SIZE);
});

// Classified pagination
const classifiedPage = ref(1);
const totalClassifiedPages = computed(() =>
    Math.max(1, Math.ceil(sortedClassifiedAlerts.value.length / PAGE_SIZE))
);
const pagedClassified = computed(() => {
    const start = (classifiedPage.value - 1) * PAGE_SIZE;
    return sortedClassifiedAlerts.value.slice(start, start + PAGE_SIZE);
});

// Reset to page 1 when filters change
const onFilterChange = () => {
    classifiedPage.value = 1;
    fetchAlerts();
};

// ── Keyword Reference ──────────────────────────────────────────
const severityLevels = [
    { label: 'Severe',   key: 'severe'   },
    { label: 'Moderate', key: 'moderate' },
    { label: 'Low',      key: 'low'      },
];
const activeSeverity = ref('severe');

const keywordMap = {
    severe:   ['hopeless', 'worthless', 'no one understands', 'breaking down', "can't cope"],
    moderate: ['stressed', 'anxious', 'overwhelmed', 'struggling', 'alone'],
    low:      ['sad', 'tired', 'unmotivated', 'worried', 'frustrated'],
};
const currentKeywords = computed(() => keywordMap[activeSeverity.value] ?? []);

// ── Filters (classified section only) ─────────────────────────
const filterPriority = ref('');
const filterStatus   = ref('');

// ── Alert Data ─────────────────────────────────────────────────
const alerts = ref([]);
const statsData = ref({ severe_count: 0, moderate_count: 0, low_count: 0 });

// ── Static Pending Alerts (for demo — no backend yet) ──────────
const staticPendingAlerts = ref([
    {
        id: 'static-1',
        message: "I just feel so hopeless lately, I don't think things will ever get better.",
        detected_keywords: ['hopeless'],
        user_display: 'Anonymous #1001',
        masked_email: '2023*****@gordoncollege.edu.ph',
        created_at: new Date(Date.now() - 1000 * 60 * 18).toISOString(),
        severity: null,
        status: 'new',
        _isStatic: true,
    },
    {
        id: 'static-2',
        message: "No one understands what I'm going through. I feel completely alone and I'm breaking down.",
        detected_keywords: ['no one understands', 'alone', 'breaking down'],
        user_display: 'Anonymous #1001',
        masked_email: '2023*****@gordoncollege.edu.ph',
        created_at: new Date(Date.now() - 1000 * 60 * 45).toISOString(),
        severity: null,
        status: 'new',
        _isStatic: true,
    },
    {
        id: 'static-3',
        message: "I've been so overwhelmed with everything, I can't sleep and I can't cope anymore.",
        detected_keywords: ['overwhelmed', "can't cope"],
        user_display: 'Anonymous #1001',
        masked_email: '2023*****@gordoncollege.edu.ph',
        created_at: new Date(Date.now() - 1000 * 60 * 72).toISOString(),
        severity: null,
        status: 'new',
        _isStatic: true,
    },
]);

// Severity sort order: severe first, moderate second, low third
const SEVERITY_ORDER = { severe: 0, moderate: 1, low: 2 };

const unclassifiedAlerts = computed(() => [
    ...staticPendingAlerts.value,
    ...alerts.value.filter(a => !a.severity || a.severity === 'unclassified'),
]);

// Classified alerts sorted by severity hierarchy (severe → moderate → low)
const sortedClassifiedAlerts = computed(() =>
    [...alerts.value.filter(a => a.severity && a.severity !== 'unclassified')]
        .sort((a, b) => (SEVERITY_ORDER[a.severity] ?? 99) - (SEVERITY_ORDER[b.severity] ?? 99))
);

const fetchAlerts = async () => {
    loading.value = true;
    try {
        const token = localStorage.getItem('token');
        const params = {};
        if (filterPriority.value) params.severity = filterPriority.value;
        if (filterStatus.value)   params.status   = filterStatus.value;

        const res = await axios.get('/api/admin/crisis-alerts', {
            headers: { Authorization: `Bearer ${token}` },
            params,
        });

        alerts.value    = res.data.alerts.data;
        statsData.value = res.data.stats;
    } catch (err) {
        console.error('Failed to fetch crisis alerts:', err);
        toast.error('Failed to load crisis alerts.');
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (alert, newStatus) => {
    // Optimistic update — badge changes immediately before API call
    const targetInArray = alerts.value.find(a => a.id === alert.id);
    if (targetInArray) targetInArray.status = newStatus;
    else alert.status = newStatus; // fallback for static/local refs

    const label = newStatus === 'reviewed' ? 'under review' : 'resolved';
    toast.success(`Alert from ${alert.user_display} is now ${label}.`, { timeout: 3000 });

    try {
        const token = localStorage.getItem('token');
        await axios.patch(`/api/admin/crisis-alerts/${alert.id}`, { status: newStatus }, {
            headers: { Authorization: `Bearer ${token}` },
        });
        fetchAlerts();
    } catch (err) {
        console.error('Failed to update alert:', err);
        toast.error('Failed to update alert status.');
        // Revert optimistic update on failure
        if (targetInArray) targetInArray.status = alert.status;
    }
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('en-PH', {
        month: 'numeric', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit', hour12: true,
    });
};

const capitalize = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1) : '';

onMounted(() => { fetchAlerts(); });

// ── Severity Assignment ────────────────────────────────────────
const pendingSeverity = ref({});
const assigningId     = ref(null);

const setPendingSeverity = (alertId, level) => {
    if (pendingSeverity.value[alertId] === level) {
        const copy = { ...pendingSeverity.value };
        delete copy[alertId];
        pendingSeverity.value = copy;
    } else {
        pendingSeverity.value = { ...pendingSeverity.value, [alertId]: level };
    }
};

const confirmSeverity = async (alert) => {
    const chosen = pendingSeverity.value[alert.id];
    if (!chosen) return;
    assigningId.value = alert.id;

    // Static alert: handle locally
    if (alert._isStatic) {
        const idx = staticPendingAlerts.value.findIndex(a => a.id === alert.id);
        if (idx !== -1) {
            const moved = { ...staticPendingAlerts.value[idx], severity: chosen, status: 'new' };
            delete moved._isStatic;
            staticPendingAlerts.value.splice(idx, 1);
            alerts.value.unshift(moved);
            // Reset unclassified page if current page is now out of range
            if (unclassifiedPage.value > totalUnclassifiedPages.value) {
                unclassifiedPage.value = Math.max(1, totalUnclassifiedPages.value);
            }
        }
        const copy = { ...pendingSeverity.value };
        delete copy[alert.id];
        pendingSeverity.value = copy;
        assigningId.value = null;
        toast.success(`Alert classified as ${capitalize(chosen)}.`, { timeout: 3000 });
        return;
    }

    // Backend alert: PATCH as normal
    try {
        const token = localStorage.getItem('token');
        await axios.patch(`/api/admin/crisis-alerts/${alert.id}`, { severity: chosen }, {
            headers: { Authorization: `Bearer ${token}` },
        });
        alert.severity = chosen;
        const copy = { ...pendingSeverity.value };
        delete copy[alert.id];
        pendingSeverity.value = copy;
        toast.success(`Alert classified as ${capitalize(chosen)}.`, { timeout: 3000 });
        fetchAlerts();
    } catch (err) {
        console.error('Failed to assign severity:', err);
        toast.error('Failed to assign severity. Please try again.');
    } finally {
        assigningId.value = null;
    }
};

// ── Resolve Modal ──────────────────────────────────────────────
const resolveModal = ref({ visible: false, alert: null });

const openResolveModal  = (alert) => { resolveModal.value = { visible: true, alert }; };
const closeResolveModal = () => {
    resolveModal.value.visible = false;
    setTimeout(() => { if (!resolveModal.value.visible) resolveModal.value.alert = null; }, 200);
};
const confirmResolve = async () => {
    if (resolveModal.value.alert) {
        await updateStatus(resolveModal.value.alert, 'resolved');
        closeResolveModal();
    }
};

// ── Email Modal ────────────────────────────────────────────────
const emailModal = ref({ visible: false, maskedEmail: '', subject: '', severity: '', body: '', alertId: null });

const openEmailModal = (alert) => {
    emailModal.value = {
        visible:     true,
        maskedEmail: alert.masked_email,
        subject:     `Urgent: Crisis Alert — Action Required`,
        severity:    alert.severity || '',
        alertId:     alert.id,
        body:        `Dear Student,`,
    };
};
const closeEmailModal = () => { emailModal.value.visible = false; };

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