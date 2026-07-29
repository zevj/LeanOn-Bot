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

                    <!-- Export Buttons -->
                    <div class="export-btn-group" data-html2canvas-ignore="true">
                        <button class="download-btn hover-glow" @click="downloadPDF">
                            <i class='bx bxs-file-pdf'></i>
                            <span>Export PDF</span>
                        </button>
                        <button class="download-btn download-btn-csv hover-glow" @click="downloadCSV">
                            <i class='bx bx-spreadsheet'></i>
                            <span>Export CSV</span>
                        </button>
                    </div>
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
                                placeholder="Search by ID or email..."
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
                                <i class='bx bx-chevron-left'></i>
                            </button>

                            <template v-for="p in paginationRange" :key="p">
                                <span v-if="p === '...'" class="pag-ellipsis">···</span>
                                <button
                                    v-else
                                    class="pag-num"
                                    :class="{ active: p === page }"
                                    @click="page = p; fetchLogs()"
                                >{{ p }}</button>
                            </template>

                            <button :disabled="page >= totalPages" @click="page++; fetchLogs()" class="btn-next">
                                <i class='bx bx-chevron-right'></i>
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
const PER_PAGE = 6;
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

// ── Shared helpers ──────────────────────────────────────────────────────────
const BRAND_GREEN      = [14, 96, 8]       // #0E6008
const BRAND_GREEN_MID  = [22, 163, 74]     // #16a34a
const ACCENT_DARK      = [10, 68, 6]       // deep header accent
const TEXT_DARK        = [17, 24, 39]      // #111827
const TEXT_MID         = [75, 85, 99]      // #4b5563
const TEXT_LIGHT       = [156, 163, 175]   // #9ca3af
const ROW_ALT          = [247, 250, 247]   // subtle green-tinted alt row
const BORDER_LIGHT     = [229, 231, 235]   // #e5e7eb
const HEADER_BG        = [248, 250, 248]   // near-white section bg

// Generate a short export reference ID
const makeRefId = (prefix) => {
    const ts = Date.now().toString(36).toUpperCase()
    const rand = Math.random().toString(36).substring(2, 6).toUpperCase()
    return `${prefix}-${ts}-${rand}`
}

// Load an image from /public as a base64 data URL for jsPDF
const loadImgDataUrl = (path) => new Promise((resolve) => {
    const img = new Image()
    img.crossOrigin = 'anonymous'
    img.onload = () => {
        const canvas = document.createElement('canvas')
        canvas.width  = img.naturalWidth
        canvas.height = img.naturalHeight
        canvas.getContext('2d').drawImage(img, 0, 0)
        resolve(canvas.toDataURL('image/png'))
    }
    img.onerror = () => resolve(null)
    img.src = path
})

// Draw the professional two-tone header banner on a page
const drawPdfHeader = async (doc, title, subtitle, meta) => {
    const W = doc.internal.pageSize.getWidth()
    const BANNER_H = 38

    // Dark top stripe
    doc.setFillColor(...ACCENT_DARK)
    doc.rect(0, 0, W, 7, 'F')

    // Main green banner
    doc.setFillColor(...BRAND_GREEN)
    doc.rect(0, 7, W, BANNER_H - 7, 'F')

    // Right accent block
    doc.setFillColor(...BRAND_GREEN_MID)
    doc.rect(W - 38, 7, 38, BANNER_H - 7, 'F')

    // ── Logos — vertically centered in the banner content area (y7 → y38 = 31mm tall) ──
    const LOGO_SIZE = 20          // both logos same square size
    const LOGO_Y    = 7 + (31 - LOGO_SIZE) / 2   // = 12.5 — perfectly centered
    const GC_X      = 10          // Gordon College seal — left edge
    const LB_X      = GC_X + LOGO_SIZE + 3        // LeanOn Bot — 3mm gap after GC

    const gcLogo = await loadImgDataUrl('/gc-logo.png')
    if (gcLogo) doc.addImage(gcLogo, 'PNG', GC_X, LOGO_Y, LOGO_SIZE, LOGO_SIZE)

    // Thin white vertical divider between logos
    doc.setDrawColor(255, 255, 255)
    doc.setLineWidth(0.4)
    doc.line(LB_X - 1.5, LOGO_Y + 2, LB_X - 1.5, LOGO_Y + LOGO_SIZE - 2)

    const lbLogo = await loadImgDataUrl('/leanOnBot.png')
    if (lbLogo) doc.addImage(lbLogo, 'PNG', LB_X, LOGO_Y, LOGO_SIZE, LOGO_SIZE)

    // Text starts after both logos + gap
    const textX = LB_X + LOGO_SIZE + 5

    // System name — sits above the title, vertically near top of banner content
    doc.setFontSize(6.5)
    doc.setFont('helvetica', 'normal')
    doc.setTextColor(187, 247, 208)
    doc.text('GORDON COLLEGE  ·  LEANON BOT SYSTEM', textX, 14)

    // Report title — vertically centered in banner
    doc.setFontSize(14)
    doc.setFont('helvetica', 'bold')
    doc.setTextColor(255, 255, 255)
    doc.text(title, textX, 23)

    // Subtitle
    if (subtitle) {
        doc.setFontSize(8)
        doc.setFont('helvetica', 'normal')
        doc.setTextColor(187, 247, 208)
        doc.text(subtitle, textX, 31)
    }

    // Right badge — vertically centered in banner content (7→38)
    doc.setFontSize(7)
    doc.setFont('helvetica', 'bold')
    doc.setTextColor(255, 255, 255)
    doc.text('CONFIDENTIAL', W - 19, 18, { align: 'center' })
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(6)
    doc.text('Admin Use Only', W - 19, 24, { align: 'center' })

    // Separator line
    doc.setDrawColor(...BORDER_LIGHT)
    doc.setLineWidth(0.3)
    doc.line(0, BANNER_H + 1, W, BANNER_H + 1)

    // Meta info row
    doc.setFontSize(7.5)
    doc.setTextColor(...TEXT_MID)
    doc.setFont('helvetica', 'normal')
    if (meta) {
        doc.text(meta, 14, BANNER_H + 7, { maxWidth: W - 28 })
    }

    return BANNER_H + 13
}

// Draw the footer on every page
const drawPdfFooter = (doc, refId) => {
    const pageCount = doc.internal.getNumberOfPages()
    const W = doc.internal.pageSize.getWidth()
    const H = doc.internal.pageSize.getHeight()
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i)
        // Footer separator line
        doc.setDrawColor(...BORDER_LIGHT)
        doc.setLineWidth(0.3)
        doc.line(14, H - 14, W - 14, H - 14)
        // Left line 1: system label
        doc.setFontSize(6.5)
        doc.setTextColor(...TEXT_LIGHT)
        doc.setFont('helvetica', 'normal')
        doc.text('LeanOn Bot  ·  Gordon College  ·  Confidential — For authorized personnel only', 14, H - 9)
        // Left line 2: ref ID
        doc.setFontSize(6)
        doc.text(`Export Ref: ${refId}`, 14, H - 5)
        // Right: page number
        doc.setFontSize(7)
        doc.setFont('helvetica', 'bold')
        doc.setTextColor(120, 130, 145)
        doc.text(`Page ${i} of ${pageCount}`, W - 14, H - 7, { align: 'right' })
    }
}

// Draw a section heading with a left accent bar
const drawSectionHeading = (doc, text, y, margin) => {
    const W = doc.internal.pageSize.getWidth()
    // Accent bar
    doc.setFillColor(...BRAND_GREEN)
    doc.rect(margin, y - 4, 3, 7, 'F')
    // Heading text
    doc.setFontSize(11)
    doc.setFont('helvetica', 'bold')
    doc.setTextColor(...TEXT_DARK)
    doc.text(text, margin + 6, y)
    // Thin underline
    doc.setDrawColor(...BORDER_LIGHT)
    doc.setLineWidth(0.25)
    doc.line(margin + 6, y + 2, W - margin, y + 2)
    return y + 8
}

// Shared autoTable style config
const tableStyles = () => ({
    headStyles: {
        fillColor: BRAND_GREEN,
        textColor: [255, 255, 255],
        fontStyle: 'bold',
        fontSize: 9,
        cellPadding: { top: 4, bottom: 4, left: 5, right: 5 },
        halign: 'center',
    },
    bodyStyles: {
        fontSize: 8.5,
        textColor: TEXT_DARK,
        cellPadding: { top: 3.5, bottom: 3.5, left: 5, right: 5 },
    },
    alternateRowStyles: { fillColor: ROW_ALT },
    styles: {
        lineColor: BORDER_LIGHT,
        lineWidth: 0.2,
        overflow: 'linebreak',
        font: 'helvetica',
    },
    theme: 'grid',
})

// ── PDF Export ──────────────────────────────────────────────────────────────
const downloadPDF = async () => {
    try {
        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })
        const W   = doc.internal.pageSize.getWidth()   // 297mm
        const margin = 14
        const refId  = makeRefId('LOG')

        const currentDept   = deptFilter.value   || 'All Departments'
        const currentStatus = statusFilter.value === 'active' ? 'Active Sessions'
                            : statusFilter.value === 'closed' ? 'Closed Sessions'
                            : 'All Sessions'
        const generatedAt = new Date().toLocaleString('en-PH', {
            month: 'long', day: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: true,
        })

        const metaLine = `Generated: ${generatedAt}   ·   Filters: ${currentDept} / ${currentStatus}   ·   Showing ${logs.value.length} of ${pagTotal.value} records`

        let y = await drawPdfHeader(doc, 'System Log Records Report',
            'Student session activity — login and logout audit trail', metaLine)

        // ── Summary stats bar ──
        // Landscape usable width = 297 - 28 = 269mm, divide into 3 equal cards
        const statsBoxW = (W - margin * 2) / 3
        const statsData = [
            { label: 'Total Log Entries', value: String(totalLogs.value),     color: BRAND_GREEN },
            { label: 'Active Sessions',   value: String(activeSessions.value), color: [14, 116, 144] },
            { label: 'Closed Sessions',   value: String(closedSessions.value), color: [75, 85, 99] },
        ]
        statsData.forEach((s, i) => {
            const bx = margin + i * statsBoxW
            doc.setFillColor(248, 250, 248)
            doc.setDrawColor(...s.color)
            doc.setLineWidth(0.4)
            doc.roundedRect(bx, y, statsBoxW - 4, 16, 2, 2, 'FD')
            doc.setFillColor(...s.color)
            doc.roundedRect(bx, y, 3, 16, 1, 1, 'F')
            doc.setFontSize(14)
            doc.setFont('helvetica', 'bold')
            doc.setTextColor(...s.color)
            doc.text(s.value, bx + (statsBoxW - 4) / 2, y + 8, { align: 'center' })
            doc.setFontSize(7)
            doc.setFont('helvetica', 'normal')
            doc.setTextColor(...TEXT_MID)
            doc.text(s.label.toUpperCase(), bx + (statsBoxW - 4) / 2, y + 13, { align: 'center' })
        })
        y += 22

        // ── Log records table ──
        y = drawSectionHeading(doc, 'Session Activity Records', y, margin)

        const tableRows = logs.value.map(r => [
            `LOG-${String(r.id).padStart(8, '0')}`,
            r.real_email || r.masked_email || 'N/A',
            r.department || 'N/A',
            r.program    || 'N/A',
            fmt(r.session_start).date || 'N/A',
            fmt(r.session_start).time || 'N/A',
            r.session_end ? fmt(r.session_end).time : '● Active',
        ])

        // Landscape usable = 269mm. Column widths must sum to ≤ 269mm.
        // Log ID: 32 | Email: 68 | Dept: 26 | Program: 46 | Date: 30 | In: 30 | Out: 37 = 269
        autoTable(doc, {
            ...tableStyles(),
            startY: y,
            head: [['Log ID', 'User Email', 'Dept', 'Program', 'Date', 'Session In', 'Session Out']],
            body: tableRows,
            margin: { left: margin, right: margin },
            tableWidth: W - margin * 2,
            columnStyles: {
                0: { cellWidth: 32, halign: 'center', fontStyle: 'bold' },
                1: { cellWidth: 68, overflow: 'linebreak' },
                2: { cellWidth: 26, halign: 'center' },
                3: { cellWidth: 46, overflow: 'linebreak' },
                4: { cellWidth: 30, halign: 'center' },
                5: { cellWidth: 30, halign: 'center' },
                6: { cellWidth: 37, halign: 'center' },
            },
            didParseCell(data) {
                if (data.section === 'body' && data.column.index === 6) {
                    const val = String(data.cell.raw || '')
                    if (val.includes('Active')) {
                        data.cell.styles.textColor = BRAND_GREEN
                        data.cell.styles.fontStyle  = 'bold'
                    }
                }
            },
        })

        // ── Privacy notice ──
        const finalY = doc.lastAutoTable.finalY + 6
        doc.setFontSize(7.5)
        doc.setFont('helvetica', 'italic')
        doc.setTextColor(...TEXT_LIGHT)
        doc.text(
            'Privacy Notice: This report is for authorized administrative use only. Do not distribute.',
            margin, finalY
        )

        drawPdfFooter(doc, refId)

        const safeDate = new Date().toISOString().slice(0, 10)
        doc.save(`LeanOn-LogRecords-${safeDate}.pdf`)
    } catch (error) {
        console.error('PDF Generation failed:', error)
        alert('There was an issue generating the PDF. Please check the console.')
    }
}

// ── CSV Export ──────────────────────────────────────────────────────────────
const downloadCSV = async () => {
    try {
        const token = localStorage.getItem('token')
        // Fetch ALL matching records (no pagination) for the CSV
        const params = { per_page: 9999, page: 1 }
        if (search.value)      params.search     = search.value
        if (deptFilter.value)  params.department = deptFilter.value
        if (statusFilter.value) params.status    = statusFilter.value

        const res = await axios.get('/api/admin/logs', {
            headers: { Authorization: `Bearer ${token}` },
            params,
        })

        const allLogs = res.data.logs.data

        const currentDept   = deptFilter.value   || 'All Departments'
        const currentStatus = statusFilter.value === 'active' ? 'Active Sessions'
                            : statusFilter.value === 'closed' ? 'Closed Sessions'
                            : 'All Sessions'
        const generatedAt = new Date().toLocaleString('en-PH', {
            month: 'long', day: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: true,
        })

        const esc = (v) => `"${String(v ?? '').replace(/"/g, '""').replace(/\r?\n/g, ' ')}"`

        const fmtCsv = (dt) => {
            if (!dt) return ''
            const d = new Date(dt)
            return d.toLocaleString('en-PH', {
                month: 'long', day: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true,
            })
        }

        const rows = []

        // Report metadata block
        rows.push([esc('LeanOn Bot — System Log Records Report')])
        rows.push([esc('Gordon College — Guidance & Counseling Office')])
        rows.push([esc(`Generated: ${generatedAt}`)])
        rows.push([esc(`Filters Applied: ${currentDept} / ${currentStatus}`)])
        rows.push([esc(`Total Records Exported: ${allLogs.length}`)])
        rows.push([])

        // Summary stats
        rows.push([esc('=== SESSION SUMMARY ===')])
        rows.push([esc('Metric'), esc('Count')])
        rows.push([esc('Total Log Entries'),  esc(totalLogs.value)])
        rows.push([esc('Active Sessions'),    esc(activeSessions.value)])
        rows.push([esc('Closed Sessions'),    esc(closedSessions.value)])
        rows.push([esc('Departments Tracked'), esc(departments.value.length)])
        rows.push([])

        // Data table
        rows.push([esc('=== SESSION ACTIVITY RECORDS ===')])
        rows.push([
            esc('Log ID'),
            esc('User Email'),
            esc('Department'),
            esc('Program'),
            esc('Session Start'),
            esc('Session End'),
            esc('Session Status'),
            esc('Duration (approx.)'),
        ])

        allLogs.forEach(r => {
            const start = r.session_start ? new Date(r.session_start) : null
            const end   = r.session_end   ? new Date(r.session_end)   : null
            let duration = 'Active'
            if (start && end) {
                const mins = Math.round((end - start) / 60000)
                duration = mins < 60
                    ? `${mins} min`
                    : `${Math.floor(mins / 60)}h ${mins % 60}m`
            }
            rows.push([
                esc(`LOG-${String(r.id).padStart(8, '0')}`),
                esc(r.real_email || r.masked_email || 'N/A'),
                esc(r.department || 'N/A'),
                esc(r.program    || 'N/A'),
                esc(fmtCsv(r.session_start)),
                esc(r.session_end ? fmtCsv(r.session_end) : 'Still Active'),
                esc(r.session_end ? 'Closed' : 'Active'),
                esc(duration),
            ])
        })

        rows.push([])
        rows.push([esc('Privacy Notice: This report contains anonymized session data. For authorized administrative use only.')])

        const csvContent = '\uFEFF' + rows.map(r => r.join(',')).join('\r\n')
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
        const url  = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href     = url
        link.download = `LeanOn-LogRecords-${new Date().toISOString().slice(0, 10)}.csv`
        link.click()
        URL.revokeObjectURL(url)

        // Notify admin panel
        try {
            await axios.post('/api/admin/notifications/log-csv-exported', {
                total_records: allLogs.length,
                filters: { department: currentDept, status: currentStatus },
            }, { headers: { Authorization: `Bearer ${token}` } })
        } catch (notifErr) {
            console.warn('Log CSV notification failed (non-fatal):', notifErr)
        }
    } catch (err) {
        console.error('CSV export failed:', err)
        alert('Failed to generate CSV. Please try again.')
    }
}

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

/* PAGINATION */
const paginationRange = computed(() => {
    const total = totalPages.value;
    const current = page.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

    const pages = [];
    pages.push(1);
    if (current > 3) pages.push('...');
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
        pages.push(i);
    }
    if (current < total - 2) pages.push('...');
    pages.push(total);
    return pages;
});
</script>

<style scoped src="@/assets/admin/AdminLogRecords.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>

<style scoped>
/* ── Export button group ── */
.export-btn-group {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.download-btn-csv {
  background: linear-gradient(135deg, #0e7490 0%, #0891b2 100%);
  box-shadow: 0 3px 10px rgba(14, 116, 144, 0.25);
}

.download-btn-csv:hover {
  background: linear-gradient(135deg, #0c6478 0%, #0e7490 100%);
  box-shadow: 0 6px 16px rgba(14, 116, 144, 0.35);
}

@media (max-width: 600px) {
  .export-btn-group { width: 100%; }
  .download-btn { flex: 1; justify-content: center; }
}
</style>