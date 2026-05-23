<template>  
    <div class="layout">
        <!-- Sidebar -->
        <SidebarAdmin
            :open="sidebarOpen"
            @toggle="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)"
        />

        <main class="main-area">
            <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen; localStorage.setItem('adminSidebarOpen', sidebarOpen)" />

            <!-- Attached the ref here so we can target this specific container for the PDF -->
            <div class="main-container" ref="logsContainerRef">

                <!-- ── HEADER ── -->
                <div class="page-header-wrapper fade-in">
                    <div class="header-title">
                        <h1 class="title">Log Records</h1>
                        <p class="subtext">Track student login and logout activity across the system in real-time.</p>
                    </div>

                    <!-- PDF Download Button -->
                    <button class="download-btn hover-glow" @click="downloadPDF" data-html2canvas-ignore="true">
                        <i class='bx bx-cloud-download'></i>
                        <span>Export PDF</span>
                    </button>
                </div>

                <div class="audit-log">

                    <!-- ── STAT CARDS ── -->
                    <div class="audit-stats">
                        <div class="stat-card sc-total animate-card stagger-1">
                            <div class="sc-left">
                                <div class="sc-label">Total Logs</div>
                                <div class="sc-val">{{ totalLogs }}</div>
                            </div>
                            <div class="sc-icon">
                                <i class='bx bx-list-ul'></i>
                            </div>
                        </div>

                        <div class="stat-card sc-active animate-card stagger-2">
                            <div class="sc-left">
                                <div class="sc-label">Active Sessions</div>
                                <div class="sc-val">{{ activeSessions }}</div>
                            </div>
                            <div class="sc-icon">
                                <i class='bx bx-radar'></i>
                            </div>
                        </div>

                        <div class="stat-card sc-closed animate-card stagger-3">
                            <div class="sc-left">
                                <div class="sc-label">Closed Sessions</div>
                                <div class="sc-val">{{ closedSessions }}</div>
                            </div>
                            <div class="sc-icon">
                                <i class='bx bx-check-circle'></i>
                            </div>
                        </div>

                        <div class="stat-card sc-depts animate-card stagger-4">
                            <div class="sc-left">
                                <div class="sc-label">Departments</div>
                                <div class="sc-val">{{ departments.length }}</div>
                            </div>
                            <div class="sc-icon">
                                <i class='bx bx-buildings'></i>
                            </div>
                        </div>
                    </div>

                    <!-- ── CONTROLS ── -->
                    <div class="log-controls animate-card stagger-5" data-html2canvas-ignore="true">
                        <div class="search-wrapper">
                            <i class='bx bx-search search-icon'></i>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by ID, name, or email..."
                                @input="debounceFetch"
                            />
                        </div>
                        <div class="filters-wrapper">
                            <div class="select-box">
                                <select v-model="deptFilter" @change="fetchLogs">
                                    <option value="">All Departments</option>
                                    <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                                </select>
                            </div>
                            <div class="select-box">
                                <select v-model="statusFilter" @change="fetchLogs">
                                    <option value="">All Sessions</option>
                                    <option value="active">Active (In)</option>
                                    <option value="closed">Closed (Out)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ── TABLE ── -->
                    <div class="log-table-wrap animate-card stagger-5">
                        <table class="log-table">
                            <thead>
                                <tr>
                                    <th>Log ID</th>
                                    <th>User Details</th>
                                    <th>Department</th>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loading">
                                    <td colspan="6" class="log-empty">
                                        <div class="spinner"></div> <span>Syncing records...</span>
                                    </td>
                                </tr>
                                <tr v-if="!loading && !logs.length">
                                    <td colspan="6" class="log-empty">
                                        <div class="empty-state-content">
                                            <i class='bx bx-folder-open empty-icon'></i>
                                            <h3>No records found</h3>
                                            <p>Try adjusting your search or filters to find what you're looking for.</p>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Staggered row animation -->
                                <tr 
                                    v-for="(r, index) in logs" 
                                    :key="r.id" 
                                    class="fade-in-row"
                                    :style="{ animationDelay: `${0.1 + (index * 0.04)}s` }"
                                >
                                    <td data-label="Log ID">
                                        <span class="log-id">
                                            <i class='bx bx-hash'></i>{{ String(r.id).padStart(8, '0') }}
                                        </span>
                                    </td>
                                    <td data-label="User Details">
    <div class="user-cell">
        <span class="log-email">
            {{ revealedEmails.has(r.id) ? r.real_email : r.masked_email }}
            <button
                class="reveal-btn"
                @click="toggleEmail(r.id)"
                :title="revealedEmails.has(r.id) ? 'Hide email' : 'Show full email'"
            >
                <i :class="revealedEmails.has(r.id) ? 'bx bx-hide' : 'bx bx-show'"></i>
            </button>
        </span>
        <span class="program-text">{{ r.program }}</span>
    </div>
</td>
                                    <td data-label="Department">
                                        <span class="dept-badge" :class="'dept-' + (r.department || '').toLowerCase()">
                                            {{ r.department }}
                                        </span>
                                    </td>
                                    <td data-label="Date">
                                        <span class="log-date"><i class='bx bx-calendar'></i> {{ fmt(r.session_start).date }}</span>
                                    </td>
                                    <td data-label="Time In">
                                        <div class="session-cell">
                                            <span class="sess-pill sess-in"><span class="sess-dot dot-in"></span>IN</span>
                                            <span class="sess-time">{{ fmt(r.session_start).time }}</span>
                                        </div>
                                    </td>
                                    <td data-label="Time Out">
                                        <div class="session-cell">
                                            <template v-if="r.session_end">
                                                <span class="sess-pill sess-out"><span class="sess-dot dot-out"></span>OUT</span>
                                                <span class="sess-time">{{ fmt(r.session_end).time }}</span>
                                            </template>
                                            <span v-else class="sess-active-label">
                                                <div class="pulse-ring"></div> Active
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ── PAGINATION ── -->
                    <div class="log-pag animate-card stagger-5" data-html2canvas-ignore="true">
                        <span class="log-pag-info">
                            Showing <strong>{{ pagStart }} – {{ pagEnd }}</strong> of <strong>{{ pagTotal }}</strong> entries
                        </span>
                        <div class="log-pag-btns">
                            <button :disabled="page <= 1" @click="page--; fetchLogs()" class="btn-prev">
                                <i class='bx bx-chevron-left'></i> Previous
                            </button>
                            <button :disabled="page >= totalPages" @click="page++; fetchLogs()" class="btn-next">
                                Next <i class='bx bx-chevron-right'></i>
                            </button>
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
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import SidebarAdmin from '@/components/sidebarAdmin.vue';
import HeaderAdmin from '@/components/headerAdmin.vue';

const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false');
const loading = ref(false);

const logsContainerRef = ref(null); 

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
        time: d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }),
    };
}

// PDF Generation Logic 
const downloadPDF = () => {
    try {
        const doc = new jsPDF({ orientation: 'landscape' });

        // 1. Draw a Header Background Rectangle
        doc.setFillColor(14, 96, 8); // #0E6008 
        doc.rect(0, 0, doc.internal.pageSize.width, 28, 'F'); 

        // 2. Add Custom Header Title
        doc.setFontSize(18);
        doc.setTextColor(255, 255, 255); 
        doc.setFont("helvetica", "bold");
        doc.text("System Log Records Report", 14, 19);

        // 3. Add Subtitle / Timestamp
        doc.setFontSize(10);
        doc.setTextColor(100, 100, 100);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString('en-PH', { 
            month: 'short', day: '2-digit', year: 'numeric', 
            hour: '2-digit', minute: '2-digit', hour12: true 
        });
        doc.text(`Generated on: ${currentDate}`, 14, 38);

        // 4. ADD FILTERS SECTION dynamically based on current vue ref states
        const currentDept = deptFilter.value ? deptFilter.value : 'All Departments';
        let currentStatus = 'All Sessions';
        if (statusFilter.value === 'active') currentStatus = 'Active (In)';
        if (statusFilter.value === 'closed') currentStatus = 'Closed (Out)';
        
        doc.text(`Filters Applied: ${currentDept} | ${currentStatus}`, 14, 46);
        
        // 5. Render Dashboard Stats
        doc.setTextColor(40, 40, 40);
        doc.setFont("helvetica", "bold");
        doc.text(`Total Logs: ${totalLogs.value}   |   Active Sessions: ${activeSessions.value}   |   Closed Sessions: ${closedSessions.value}`, 14, 56);

        // 6. Map the reactive logs data array into rows for the PDF table
        const tableColumn = ["Log ID", "Email", "Department", "Program", "Date", "Session In", "Session Out"];
        const tableRows = [];

        logs.value.forEach(r => {
            const logData = [
                `LOG-${String(r.id).padStart(8, '0')}`,
                r.masked_email || 'N/A',
                r.department || 'N/A',
                r.program || 'N/A',
                fmt(r.session_start).date || 'N/A',
                fmt(r.session_start).time || 'N/A',
                r.session_end ? fmt(r.session_end).time : 'Active'
            ];
            tableRows.push(logData);
        });

        // 7. Build the stylized table using autoTable
        autoTable(doc, {
            head: [tableColumn],
            body: tableRows,
            startY: 62, // Pushed down to accommodate the new Filters line
            theme: 'grid',
            headStyles: { 
                fillColor: [14, 96, 8], 
                textColor: [255, 255, 255],
                fontSize: 10,
                halign: 'center',
                fontStyle: 'bold'
            },
            bodyStyles: {
                fontSize: 9,
                halign: 'center',
                textColor: [50, 50, 50]
            },
            alternateRowStyles: { 
                fillColor: [248, 250, 248] 
            },
            styles: {
                cellPadding: 4
            }
        });

        // 8. Trigger Download
        const safeDate = new Date().toLocaleDateString('en-PH').replace(/\//g, '-');
        doc.save(`Log-Records-${safeDate}.pdf`);
    } catch (error) {
        console.error("PDF Generation failed:", error);
        alert("There was an issue generating the PDF. Please check the console.");
    }
};

onMounted(() => {
    fetchLogs();
});

/* ADD EYE ICON*/
const revealedEmails = ref(new Set());

const toggleEmail = (id) => {
    const updated = new Set(revealedEmails.value);
    updated.has(id) ? updated.delete(id) : updated.add(id);
    revealedEmails.value = updated;
};
</script>

<style scoped src="@/assets/admin/AdminLogRecords.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>