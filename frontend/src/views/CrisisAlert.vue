<template>
    <div class="layout">
        <SidebarStudent></SidebarStudent>

        <main>
            <HeaderStudent></HeaderStudent>

            <div class="main-container">
                <div class="title-header">
                    <h1 class="title">Crisis Alert</h1>
                    <p class="subtext">Report a crisis or emergency situation to the appropriate authorities.</p>
                </div>

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

                <div class="alert-container">
                    <div 
                        v-for="alert in filteredAlerts" 
                        :key="alert.id" 
                        class="alert-item" 
                        :class="{ 'expanded': expandedId === alert.id }"
                        :style="{ borderTopColor: getStatusColor(alert.status) }"
                    >
                        <div class="alert-header" @click="toggleExpand(alert.id)">
                            <div class="alert-header-left">
                                <span class="status-dot pulse" :style="{ backgroundColor: getStatusColor(alert.status), '--pulse-color': getStatusColor(alert.status) }"></span>
                                <span class="alert-text">{{ alert.message }}</span>
                            </div>
                            <i class='bx bx-chevron-down toggle-icon'></i>
                        </div>

                        <div class="alert-preview-meta" v-if="expandedId !== alert.id">
                            <span class="status-badge" :style="getBadgeStyle(alert.status)">
                                {{ alert.status.toUpperCase() }}
                            </span>
                            <span class="timestamp"><i class='bx bx-time-five'></i> {{ alert.time }}</span>
                        </div>

                        <Transition name="expand">
                            <div class="alert-details" v-if="expandedId === alert.id">
                                <div class="keyword-section">
                                    <p class="section-label">TRIGGERED KEYWORDS</p>
                                    <div class="keyword-tags">
                                        <span v-for="tag in alert.keywords" :key="tag" class="tag" :style="getBadgeStyle(alert.status)">
                                            {{ tag }}
                                        </span>
                                    </div>
                                </div>

                                <div class="recommendation-box">
                                    <div class="rec-header">
                                        <i class='bx bx-shield-quarter'></i>
                                        <strong>Recommended: {{ alert.recommendation.title }}</strong>
                                    </div>
                                    <p>{{ alert.recommendation.desc }}</p>
                    
                                    <div class="action-buttons">
                                        <button class="btn-email-link" @click="openGmail(alert)">
                                            <i class='bx bx-envelope'></i> Request Consultation
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </Transition>
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

// 1. Mockup Data aligned with your specific design requirements
const alerts = ref([
    {
        id: 1,
        status: 'moderate',
        message: 'I feel hopeless and want to give up on everything',
        time: '1h ago',
        keywords: ['hopeless', 'give up'],
        recommendation: {
            title: 'In-Person Consultation',
            desc: 'Speaking with a counselor face-to-face can really help. Everything shared is confidential.'
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
            desc: 'A crisis responder has been notified. Please stay on the line.'
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
            desc: 'We recommend reviewing our stress management guides.'
        }
    },
    {
        id: 3,
        status: 'severe',
        message: 'User is experiencing mild academic anxiety',
        time: '3h ago',
        keywords: ['anxiety', 'exams'],
        recommendation: {
            title: 'Resource Materials',
            desc: 'We recommend reviewing our stress management guides.'
        }
    }
]);

// Add this inside <script setup>
const openGmail = (alert) => {
    const email = "guidance@gc.edu.ph"; // Your GC domain email
    const subject = encodeURIComponent(`Crisis Alert Consultation: ${alert.status.toUpperCase()}`);
    const body = encodeURIComponent(`Hello Guidance Office,\n\nI am requesting a consultation regarding the following alert:\n\nMessage: ${alert.message}\nStatus: ${alert.status}\nTime: ${alert.time}\n\nPlease let me know when we can meet.`);
    
    window.location.href = `mailto:${email}?subject=${subject}&body=${body}`;
};

const currentFilter = ref('All');
const expandedId = ref(null);

// 2. Logic for Filtering
const filteredAlerts = computed(() => {
    if (currentFilter.value === 'All') return alerts.value;
    return alerts.value.filter(a => a.status === currentFilter.value.toLowerCase());
});

// 3. Logic for Stats Summary
const stats = computed(() => ({
    high: alerts.value.filter(a => a.status === 'high').length,
    severe: alerts.value.filter(a => a.status === 'severe').length,
    moderate: alerts.value.filter(a => a.status === 'moderate').length,
    low: alerts.value.filter(a => a.status === 'low').length,
}));

const toggleExpand = (id) => {
    expandedId.value = expandedId.value === id ? null : id;
};

// 4. Color Management using your specific Hex Codes
const statusColors = {
    low: '#0A9569',
    moderate: '#9F7A00',
    severe: '#FC9149',
    high: '#DC2625'
};

const getStatusColor = (status) => statusColors[status] || '#ff4d6d';

const getBadgeStyle = (status) => {
    const color = getStatusColor(status);
    return {
        color: color,
        backgroundColor: `${color}15`, // 15 is ~8% opacity
        border: `1px solid ${color}40` // 40 is ~25% opacity
    };
};
</script>

<style scoped src="../assets/users/CrisisAlert.css"></style>