<template>
    <div class="layout">
        <SidebarStudent />

        <main>
            <HeaderStudent />

            <div class="main-container">
                <!-- HEADER -->
                <div class="title-header">
                    <h1 class="title">Crisis Alert</h1>
                    <p class="subtext">
                        Report a crisis or emergency situation to the appropriate authorities.
                    </p>
                </div>

                <!-- STATS -->
                <div class="whole-stat-card">
                    <div class="high-stat-card">
                        <div class="stat-card-title-high">
                            <h4 class="title-stat">High</h4>
                            <p class="stat-number">{{ stats.high }}</p>
                        </div>
                        <div class="icon-bg-high"><i class="bx bx-error"></i></div>
                    </div>

                    <div class="severe-stat-card">
                        <div class="stat-card-title-severe">
                            <h4 class="title-stat">Severe</h4>
                            <p class="stat-number">{{ stats.severe }}</p>
                        </div>
                        <div class="icon-bg-severe"><i class="bx bxs-bell-ring"></i></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-title-moderate">
                            <h4 class="title-stat">Moderate</h4>
                            <p class="stat-number">{{ stats.moderate }}</p>
                        </div>
                        <div class="icon-bg-moderate"><i class="bx bx-info-circle"></i></div>
                    </div>

                    <div class="low-stat-card">
                        <div class="stat-card-title-low">
                            <h4 class="title-stat">Low</h4>
                            <p class="stat-number">{{ stats.low }}</p>
                        </div>
                        <div class="icon-bg-low"><i class="bx bx-check-shield"></i></div>
                    </div>
                </div>

                <!-- FILTER -->
                <div class="filter-tabs">
                    <button 
                        v-for="tab in ['All', 'High', 'Severe', 'Moderate', 'Low']" 
                        :key="tab"
                        class="tab"
                        :class="{ active: currentFilter === tab }"
                        @click="currentFilter = tab"
                    >
                        {{ tab }}
                    </button>
                </div>

                <!-- ALERT GRID -->
                <div class="alert-container">
                    <div 
                        class="alert-item" 
                        v-for="alert in filteredAlerts" 
                        :key="alert.id"
                        :style="{ borderTopColor: getStatusColor(alert.status) }"
                    >
                        <div class="alert-header" @click="openModal(alert)">
                            <div class="alert-header-left">
                                <span class="status-dot pulse"
                                    :style="{ backgroundColor: getStatusColor(alert.status) }"></span>
                                <span class="alert-text">{{ alert.message }}</span>
                            </div>
                        </div>

                        <div class="alert-preview-meta">
                            <span class="status-badge" :style="getBadgeStyle(alert.status)">
                                {{ alert.status.toUpperCase() }}
                            </span>
                            <span class="timestamp">
                                <i class='bx bx-time-five'></i> {{ alert.time }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- MODAL -->
                <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-content">

                    <div class="modal-top-bar">
                    <div class="modal-top-bar-left">
                        <div class="modal-status-row">
                        <div class="modal-dot" :style="{ backgroundColor: getStatusColor(selectedAlert.status) }"></div>
                        <span class="status-badge" :style="getBadgeStyle(selectedAlert.status)">
                            {{ selectedAlert.status.toUpperCase() }}
                        </span>
                        <span class="modal-time">
                            <i class='bx bx-time-five'></i> {{ selectedAlert.time }}
                        </span>
                        </div>
                        <h3>{{ selectedAlert.message }}</h3>
                    </div>
                    <button class="close-btn" @click="closeModal">&times;</button>
                    </div>

                    <div class="modal-body">
                    <div class="keyword-section">
                        <p class="section-label">Triggered Keywords</p>
                        <div class="keyword-tags">
                        <span
                            v-for="tag in selectedAlert.keywords"
                            :key="tag"
                            class="tag"
                            :style="getBadgeStyle(selectedAlert.status)"
                        >
                            {{ tag }}
                        </span>
                        </div>
                    </div>

                    <div class="recommendation-box">
                        <div class="rec-header">
                        <i class='bx bx-shield-quarter'></i>
                        Recommended: {{ selectedAlert.recommendation.title }}
                        </div>
                        <p>{{ selectedAlert.recommendation.desc }}</p>
                        <button class="btn-email-link" @click="openGmail(selectedAlert)">
                        <i class='bx bx-envelope'></i>
                        Request Consultation
                        </button>
                    </div>
                    </div>
                </div>
            </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import SidebarStudent from '@/components/sidebar.vue';
import HeaderStudent from '@/components/header.vue';

const alerts = ref([
    {
        id: 1,
        status: 'moderate',
        message: 'I feel hopeless and want to give up on everything',
        time: '1h ago',
        keywords: ['hopeless', 'give up'],
        recommendation: {
            title: 'In-Person Consultation',
            desc: 'Speaking with a counselor face-to-face can really help.'
        }
    },
    {
        id: 2,
        status: 'high',
        message: 'Extreme distress reported during peer session',
        time: '15m ago',
        keywords: ['distress', 'urgent'],
        recommendation: {
            title: 'Immediate Intervention',
            desc: 'A crisis responder has been notified.'
        }
    },
    {
        id: 3,
        status: 'low',
        message: 'User is experiencing mild academic anxiety',
        time: '3h ago',
        keywords: ['anxiety', 'exams'],
        recommendation: {
            title: 'Resource Materials',
            desc: 'Review stress management guides.'
        }
    },
    {
        id: 4,
        status: 'severe',
        message: 'User flagged with severe emotional distress',
        time: '2h ago',
        keywords: ['panic', 'fear'],
        recommendation: {
            title: 'Urgent Counseling',
            desc: 'Immediate counselor intervention required.'
        }
    }
]);

const currentFilter = ref('All');

const filteredAlerts = computed(() => {
    if (currentFilter.value === 'All') return alerts.value;
    return alerts.value.filter(a => a.status === currentFilter.value.toLowerCase());
});

const stats = computed(() => ({
    high: alerts.value.filter(a => a.status === 'high').length,
    severe: alerts.value.filter(a => a.status === 'severe').length,
    moderate: alerts.value.filter(a => a.status === 'moderate').length,
    low: alerts.value.filter(a => a.status === 'low').length,
}));

const statusColors = {
    low: '#0A9569',
    moderate: '#9F7A00',
    severe: '#FC9149',
    high: '#DC2625'
};

const getStatusColor = (status) => statusColors[status];

const getBadgeStyle = (status) => {
    const color = getStatusColor(status);
    return {
        color,
        backgroundColor: `${color}15`,
        border: `1px solid ${color}40`
    };
};

// MODAL
const showModal = ref(false);
const selectedAlert = ref(null);

const openModal = (alert) => {
    selectedAlert.value = alert;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedAlert.value = null;
};

const openGmail = (alert) => {
    const email = "guidance@gc.edu.ph";
    const subject = encodeURIComponent(`Crisis Alert Consultation: ${alert.status.toUpperCase()}`);
    const body = encodeURIComponent(`Message: ${alert.message}`);
    window.location.href = `mailto:${email}?subject=${subject}&body=${body}`;
};
</script>

<style scoped src="../assets/users/CrisisAlert.css"></style>