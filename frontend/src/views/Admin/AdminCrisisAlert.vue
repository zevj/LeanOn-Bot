<<template>
    <div class="layout">
        <SidebarAdmin />

        <main>
            <HeaderAdmin />

            <div class="main-container">
                <div class="header-title">
                    <h1 class="title">Crisis Alerts</h1>
                    <p class="subtext">Flagged conversations requiring attention</p>
                </div>

                <!-- STATS -->
                <div class="whole-stat-card">
                    <div class="high-stat-card">
                        <div class="stat-card-title-high">
                            <h4 class="title-stat">High</h4>
                            <p class="stat-number">12</p>
                        </div>
                        <div class="icon-bg-high"><i class="bx bx-error"></i></div>
                    </div>

                    <div class="severe-stat-card">
                        <div class="stat-card-title-severe">
                            <h4 class="title-stat">Severe</h4>
                            <p class="stat-number">12</p>
                        </div>
                        <div class="icon-bg-severe"><i class="bx bxs-bell-ring"></i></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-title-moderate">
                            <h4 class="title-stat">Moderate</h4>
                            <p class="stat-number">12</p>
                        </div>
                        <div class="icon-bg-moderate"><i class="bx bx-info-circle"></i></div>
                    </div>

                    <div class="low-stat-card">
                        <div class="stat-card-title-low">
                            <h4 class="title-stat">Low</h4>
                            <p class="stat-number">12</p>
                        </div>
                        <div class="icon-bg-low"><i class="bx bx-check-shield"></i></div>
                    </div>
                </div>

                <!-- KEYWORD REFERENCE -->
                <div class="stats-and-keywords">
                    <div class="keyword-reference-card">
                        <h3 class="keyword-reference-title">Keyword Reference</h3>
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
                </div>

                <!-- FILTERS -->
                <div class="alert-filters">
                    <select v-model="filterPriority" class="filter-select">
                        <option value="">All Priorities</option>
                        <option value="high">High</option>
                        <option value="severe">Severe</option>
                        <option value="moderate">Moderate</option>
                        <option value="low">Low</option>
                    </select>
                    <select v-model="filterStatus" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="new">New</option>
                        <option value="review">Under Review</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>

                <!-- ALERT LIST -->
                <div class="alert-list">
                    <div
                        v-for="alert in filteredAlerts"
                        :key="alert.id"
                        class="alert-card"
                    >
                        <div class="alert-card-left">
                            <div class="alert-meta">
                                <span class="alert-priority-badge" :class="`badge--${alert.priority}`">
                                    {{ capitalize(alert.priority) }}
                                </span>
                                <span 
                                    class="alert-status-badge" 
                                    :class="[`status--${alert.status}`, `badge--${alert.priority}`]"
                                    >
                                    <i v-if="alert.status === 'new'" class="bx bx-error-circle"></i>
                                    {{ capitalize(alert.status) }}
                                </span>
                                <span class="alert-time">
                                    <i class="bx bx-time-five"></i> {{ alert.time }}
                                </span>
                            </div>
                            <p class="alert-message">"{{ alert.message }}"</p>
                            <div class="alert-keywords-row">
                                <span class="alert-keywords-label">Keywords:</span>
                                <span
                                    v-for="kw in alert.keywords"
                                    :key="kw"
                                    class="alert-keyword-tag"
                                    :class="`keyword--${alert.priority}`"
                                >
                                    {{ kw }}
                                </span>
                            </div>
                            <p class="alert-user">{{ alert.user }} · {{ alert.email }}</p>
                        </div>
                        <div class="alert-card-actions">
                            <button class="action-btn action-btn--email">
                                <i class="bx bx-send"></i> Email
                            </button>
                            <button
                                class="action-btn action-btn--review"
                                @click="handleReview(alert)"
                            >
                                <i class="bx bx-search-alt"></i> Review
                            </button>
                            <button
                                class="action-btn action-btn--resolve"
                                @click="handleResolve(alert)"
                            >
                                <i class="bx bx-check"></i> Resolve
                            </button>
                        </div>
                    </div>

                    <p v-if="filteredAlerts.length === 0" class="no-alerts">
                        No alerts match the current filters.
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useToast } from 'vue-toastification';
import SidebarAdmin from '@/components/sidebarAdmin.vue';
import HeaderAdmin from '@/components/headerAdmin.vue';

const toast = useToast();

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
const alerts = ref([
    {
        id: 1,
        priority: 'high',
        status: 'new',
        time: '4/1/2026, 9:23:00 AM',
        message: "I just feel like I don't want to live anymore... everything feels pointless and I want to end it all.",
        keywords: ["don't want to live", 'end it all'],
        user: 'Anonymous #1041',
        email: 'student1@gordoncollege.edu.ph',
    },
    {
        id: 2,
        priority: 'severe',
        status: 'new',
        time: '4/1/2026, 8:10:00 AM',
        message: "I feel completely worthless. No one understands what I'm going through.",
        keywords: ['worthless', 'no one understands'],
        user: 'Anonymous #1038',
        email: 'student2@gordoncollege.edu.ph',
    },
    {
        id: 3,
        priority: 'moderate',
        status: 'review',
        time: '3/31/2026, 11:45:00 PM',
        message: "I'm breaking down and I can't cope with everything anymore.",
        keywords: ['breaking down', "can't cope"],
        user: 'Anonymous #1035',
        email: 'student3@gordoncollege.edu.ph',
    },
]);

const filteredAlerts = computed(() => {
    return alerts.value.filter(a => {
        const matchPriority = !filterPriority.value || a.priority === filterPriority.value;
        const matchStatus   = !filterStatus.value   || a.status   === filterStatus.value;
        return matchPriority && matchStatus;
    });
});

// ── Toast Actions ──────────────────────────────────────────────
const handleReview = (alert) => {
    alert.status = 'review';
    toast.info(`Alert from ${alert.user} is now under review.`, {
        timeout: 3000,
    });
};

const handleResolve = (alert) => {
    alert.status = 'resolved';
    toast.success(`Alert from ${alert.user} has been resolved.`, {
        timeout: 3000,
    });
};

// ── Helpers ────────────────────────────────────────────────────
const capitalize = (str) => str.charAt(0).toUpperCase() + str.slice(1);
</script>

<style scoped src="@/assets/admin/AdminCrisisAlert.css"></style>>