<template>
    <div class="layout">
        <SidebarAdmin
            :open="sidebarOpen"
            @toggle="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)"
        />

        <main>
            <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)" />

            <div class="main-container">
                <div class="header-title">
                    <h1 class="title">Log Records</h1>
                    <p class="subtext">Track student login and logout activity across the system.</p>
                </div>

                <div class="audit-log">

                    <!-- ── STAT CARDS ── -->
                    <div class="audit-stats">

                        <div class="stat-card sc-total">
                            <div class="sc-left">
                                <div class="sc-label">Total logs</div>
                                <div class="sc-val">{{ totalLogs }}</div>
                            </div>
                            <div class="sc-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 12h6M9 16h6M7 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2"/>
                                    <rect x="7" y="2" width="10" height="4" rx="1"/>
                                </svg>
                            </div>
                        </div>

                        <div class="stat-card sc-active">
                            <div class="sc-left">
                                <div class="sc-label">Active sessions</div>
                                <div class="sc-val">{{ activeSessions }}</div>
                            </div>
                            <div class="sc-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                        </div>

                        <div class="stat-card sc-closed">
                            <div class="sc-left">
                                <div class="sc-label">Closed sessions</div>
                                <div class="sc-val">{{ closedSessions }}</div>
                            </div>
                            <div class="sc-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                            </div>
                        </div>

                        <div class="stat-card sc-depts">
                            <div class="sc-left">
                                <div class="sc-label">Departments</div>
                                <div class="sc-val">{{ departments.length }}</div>
                            </div>
                            <div class="sc-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                                    <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
                                    <line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/>
                                </svg>
                            </div>
                        </div>

                    </div>

                    <!-- ── CONTROLS ── -->
                    <div class="log-controls">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search name, email, or log ID…"
                            @input="debounceFetch"
                        />
                        <select v-model="deptFilter" @change="fetchLogs">
                            <option value="">All departments</option>
                            <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                        </select>
                        <select v-model="statusFilter" @change="fetchLogs">
                            <option value="">All sessions</option>
                            <option value="active">Active (in)</option>
                            <option value="closed">Closed (out)</option>
                        </select>
                    </div>

                    <!-- ── TABLE ── -->
                    <div class="log-table-wrap">
                        <table class="log-table">
                            <thead>
                                <tr>
                                    <th>Log ID</th>
                                    <th>Email</th>
                                    <th>Dept</th>
                                    <th>Program</th>
                                    <th>Session in</th>
                                    <th>Session out</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loading">
                                    <td colspan="7" class="log-empty">Loading...</td>
                                </tr>
                                <tr v-if="!loading && !logs.length">
                                    <td colspan="7" class="log-empty">No records found.</td>
                                </tr>
                                <tr v-for="r in logs" :key="r.id">
                                    <td><span class="log-id">LOG-{{ String(r.id).padStart(8, '0') }}</span></td>
                                    <td class="log-email">{{ r.masked_email }}</td>
                                    <td>
                                        <span class="dept-badge" :class="'dept-' + (r.department || '').toLowerCase()">
                                            {{ r.department }}
                                        </span>
                                    </td>
                                    <td>{{ r.program }}</td>
                                    <td>
                                        <span class="sess-pill sess-in">
                                            <span class="sess-dot dot-in"></span>In
                                        </span>
                                        <span class="sess-time">{{ fmt(r.session_start).time }}</span>
                                        <span class="sess-date">{{ fmt(r.session_start).date }}</span>
                                    </td>
                                    <td>
                                        <template v-if="r.session_end">
                                            <span class="sess-pill sess-out">
                                                <span class="sess-dot dot-out"></span>Out
                                            </span>
                                            <span class="sess-time">{{ fmt(r.session_end).time }}</span>
                                            <span class="sess-date">{{ fmt(r.session_end).date }}</span>
                                        </template>
                                        <span v-else class="sess-active-label">— Active</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ── PAGINATION ── -->
                    <div class="log-pag">
                        <span class="log-pag-info">
                            Showing {{ pagStart }}–{{ pagEnd }} of {{ pagTotal }} records
                        </span>
                        <div class="log-pag-btns">
                            <button :disabled="page <= 1" @click="page--; fetchLogs()">← Prev</button>
                            <button :disabled="page >= totalPages" @click="page++; fetchLogs()">Next →</button>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import SidebarAdmin from '@/components/sidebarAdmin.vue';
import HeaderAdmin from '@/components/headerAdmin.vue';

const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false');
const loading = ref(false);

const logs = ref([]);
const departments = ref([]);
const totalLogs = ref(0);
const activeSessions = ref(0);
const closedSessions = ref(0);

const search = ref('');
const deptFilter = ref('');
const statusFilter = ref('');
const page = ref(1);
const PER_PAGE = 20;
const pagTotal = ref(0);
const totalPages = computed(() => Math.max(1, Math.ceil(pagTotal.value / PER_PAGE)));
const pagStart = computed(() => pagTotal.value === 0 ? 0 : (page.value - 1) * PER_PAGE + 1);
const pagEnd = computed(() => Math.min(page.value * PER_PAGE, pagTotal.value));

let debounceTimer = null;
const debounceFetch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        page.value = 1;
        fetchLogs();
    }, 400);
};

const fetchLogs = async () => {
    loading.value = true;
    try {
        const token = localStorage.getItem('token');
        const params = { page: page.value, per_page: PER_PAGE };
        if (search.value) params.search = search.value;
        if (deptFilter.value) params.department = deptFilter.value;
        if (statusFilter.value) params.status = statusFilter.value;

        const res = await axios.get('/api/admin/logs', {
            headers: { Authorization: `Bearer ${token}` },
            params,
        });

        logs.value = res.data.logs.data;
        pagTotal.value = res.data.logs.total;
        departments.value = res.data.departments;
        totalLogs.value = res.data.total_logs;
        activeSessions.value = res.data.active_sessions;
        closedSessions.value = res.data.closed_sessions;
    } catch (err) {
        console.error('Failed to fetch logs:', err);
    } finally {
        loading.value = false;
    }
};

function fmt(dt) {
    if (!dt) return {};
    const d = new Date(dt);
    return {
        date: d.toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' }),
        time: d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true }),
    };
}

onMounted(() => {
    fetchLogs();
});
</script>

<style scoped src="@/assets/admin/AdminLogRecords.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>