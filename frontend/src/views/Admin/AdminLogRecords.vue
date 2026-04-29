<template>
    <div class="layout">
        <SidebarAdmin
            :open="sidebarOpen"
            @toggle="sidebarOpen = !sidebarOpen"
        />

        <main>
            <HeaderAdmin @toggle-sidebar="sidebarOpen = !sidebarOpen" />

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
                                <div class="sc-val">{{ logs.length }}</div>
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
                            @input="page = 1"
                        />
                        <select v-model="deptFilter" @change="page = 1">
                            <option value="">All departments</option>
                            <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                        </select>
                        <select v-model="statusFilter" @change="page = 1">
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
                                    <th>Student name</th>
                                    <th>Dept</th>
                                    <th>Program</th>
                                    <th>Session in</th>
                                    <th>Session out</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!paginated.length">
                                    <td colspan="7" class="log-empty">No records found.</td>
                                </tr>
                                <tr v-for="r in paginated" :key="r.id">
                                    <td><span class="log-id">{{ r.id }}</span></td>
                                    <td class="log-email">{{ maskEmail(r.email) }}</td>
                                    <td>{{ r.name }}</td>
                                    <td>
                                        <span class="dept-badge" :class="'dept-' + r.dept.toLowerCase()">
                                            {{ r.dept }}
                                        </span>
                                    </td>
                                    <td>{{ r.prog }}</td>
                                    <td>
                                        <span class="sess-pill sess-in">
                                            <span class="sess-dot dot-in"></span>In
                                        </span>
                                        <span class="sess-time">{{ fmt(r.sessionIn).time }}</span>
                                        <span class="sess-date">{{ fmt(r.sessionIn).date }}</span>
                                    </td>
                                    <td>
                                        <template v-if="r.sessionOut">
                                            <span class="sess-pill sess-out">
                                                <span class="sess-dot dot-out"></span>Out
                                            </span>
                                            <span class="sess-time">{{ fmt(r.sessionOut).time }}</span>
                                            <span class="sess-date">{{ fmt(r.sessionOut).date }}</span>
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
                            Showing {{ pagStart }}–{{ pagEnd }} of {{ filtered.length }} records
                        </span>
                        <div class="log-pag-btns">
                            <button :disabled="page <= 1" @click="page--">← Prev</button>
                            <button :disabled="page >= totalPages" @click="page++">Next →</button>
                        </div>
                    </div>

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
const sidebarOpen = ref(false);

/* LOG RECORDS */
const activeSessions = computed(() => logs.value.filter(r => !r.sessionOut).length);
const closedSessions = computed(() => logs.value.filter(r => !!r.sessionOut).length);
const search = ref('');
const deptFilter = ref('');
const statusFilter = ref('');
const page = ref(1);
const PER_PAGE = 8;

const logs = ref([
  // CCS — College of Computer Studies
  { id: 'LOG-20240001', email: '2023-0112@gordoncollege.edu.ph', name: 'Juan dela Cruz',       dept: 'CCS',  prog: 'BS Computer Science',              sessionIn: '2024-04-28 07:32', sessionOut: '2024-04-28 09:15' },
  { id: 'LOG-20240002', email: '2022-0045@gordoncollege.edu.ph', name: 'Lea Gonzalez',          dept: 'CCS',  prog: 'BS Information Technology',         sessionIn: '2024-04-28 10:00', sessionOut: null },
  { id: 'LOG-20240003', email: '2021-0301@gordoncollege.edu.ph', name: 'Felix Castillo',        dept: 'CCS',  prog: 'BS Computer Science',              sessionIn: '2024-04-28 13:15', sessionOut: '2024-04-28 15:45' },
  { id: 'LOG-20240004', email: '2024-0198@gordoncollege.edu.ph', name: 'Rhea Manalo',           dept: 'CCS',  prog: 'BS Information Systems',            sessionIn: '2024-04-29 08:00', sessionOut: '2024-04-29 10:30' },
  { id: 'LOG-20240005', email: '2023-0774@gordoncollege.edu.ph', name: 'Paolo Reyes',           dept: 'CCS',  prog: 'BS Information Technology',         sessionIn: '2024-04-29 09:15', sessionOut: null },

  // CEAS — College of Engineering & Architecture
  { id: 'LOG-20240006', email: '2023-0387@gordoncollege.edu.ph', name: 'Carlos Reyes',          dept: 'CEAS', prog: 'BS Civil Engineering',              sessionIn: '2024-04-28 08:15', sessionOut: null },
  { id: 'LOG-20240007', email: '2020-0089@gordoncollege.edu.ph', name: 'Patricia Mendez',       dept: 'CEAS', prog: 'BS Electrical Engineering',          sessionIn: '2024-04-28 11:00', sessionOut: '2024-04-28 13:00' },
  { id: 'LOG-20240008', email: '2022-0412@gordoncollege.edu.ph', name: 'Marco Villanueva',      dept: 'CEAS', prog: 'BS Mechanical Engineering',          sessionIn: '2024-04-28 14:00', sessionOut: '2024-04-28 16:00' },
  { id: 'LOG-20240009', email: '2021-0553@gordoncollege.edu.ph', name: 'Diana Santos',          dept: 'CEAS', prog: 'BS Architecture',                   sessionIn: '2024-04-29 07:45', sessionOut: '2024-04-29 09:00' },
  { id: 'LOG-20240010', email: '2024-0631@gordoncollege.edu.ph', name: 'Kristine Bautista',     dept: 'CEAS', prog: 'BS Electronics Engineering',         sessionIn: '2024-04-29 10:00', sessionOut: null },

  // CAHS — College of Allied Health Sciences
  { id: 'LOG-20240011', email: '2024-0023@gordoncollege.edu.ph', name: 'Miguel Torres',         dept: 'CAHS', prog: 'BS Nursing',                         sessionIn: '2024-04-28 09:30', sessionOut: '2024-04-28 12:00' },
  { id: 'LOG-20240012', email: '2021-0376@gordoncollege.edu.ph', name: 'Rosa Villanueva',       dept: 'CAHS', prog: 'BS Midwifery',                        sessionIn: '2024-04-28 13:00', sessionOut: '2024-04-28 15:00' },
  { id: 'LOG-20240013', email: '2022-0289@gordoncollege.edu.ph', name: 'Angelica Cruz',         dept: 'CAHS', prog: 'BS Medical Technology',               sessionIn: '2024-04-28 15:30', sessionOut: null },
  { id: 'LOG-20240014', email: '2023-0345@gordoncollege.edu.ph', name: 'Jerome Aquino',         dept: 'CAHS', prog: 'BS Pharmacy',                         sessionIn: '2024-04-29 08:30', sessionOut: '2024-04-29 11:00' },
  { id: 'LOG-20240015', email: '2020-0471@gordoncollege.edu.ph', name: 'Maricel Flores',        dept: 'CAHS', prog: 'BS Nursing',                         sessionIn: '2024-04-29 11:30', sessionOut: null },

  // CHTM — College of Hospitality & Tourism Management
  { id: 'LOG-20240016', email: '2023-0154@gordoncollege.edu.ph', name: 'Ramon Flores',          dept: 'CHTM', prog: 'BS Hospitality Management',           sessionIn: '2024-04-28 10:15', sessionOut: '2024-04-28 12:30' },
  { id: 'LOG-20240017', email: '2022-0562@gordoncollege.edu.ph', name: 'Gloria Navarro',        dept: 'CHTM', prog: 'BS Tourism Management',              sessionIn: '2024-04-28 14:00', sessionOut: null },
  { id: 'LOG-20240018', email: '2021-0634@gordoncollege.edu.ph', name: 'Irene Pascual',         dept: 'CHTM', prog: 'BS Hospitality Management',           sessionIn: '2024-04-29 09:00', sessionOut: '2024-04-29 11:45' },
  { id: 'LOG-20240019', email: '2024-0102@gordoncollege.edu.ph', name: 'Kenneth Domingo',       dept: 'CHTM', prog: 'BS Tourism Management',              sessionIn: '2024-04-29 13:00', sessionOut: null },
  { id: 'LOG-20240020', email: '2023-0788@gordoncollege.edu.ph', name: 'Lovely Ramos',          dept: 'CHTM', prog: 'BS Hospitality Management',           sessionIn: '2024-04-29 14:30', sessionOut: '2024-04-29 16:00' },

  // CBA — College of Business Administration
  { id: 'LOG-20240021', email: '2022-0044@gordoncollege.edu.ph', name: 'Maria Santos',          dept: 'CBA',  prog: 'BS Accountancy',                     sessionIn: '2024-04-28 08:00', sessionOut: '2024-04-28 10:45' },
  { id: 'LOG-20240022', email: '2023-0210@gordoncollege.edu.ph', name: 'Ramon Torres',          dept: 'CBA',  prog: 'BS Business Administration',          sessionIn: '2024-04-28 10:15', sessionOut: '2024-04-28 12:30' },
  { id: 'LOG-20240023', email: '2021-0398@gordoncollege.edu.ph', name: 'Jasmine Dela Rosa',     dept: 'CBA',  prog: 'BS Marketing Management',             sessionIn: '2024-04-28 13:30', sessionOut: null },
  { id: 'LOG-20240024', email: '2024-0515@gordoncollege.edu.ph', name: 'Bernard Lim',           dept: 'CBA',  prog: 'BS Accountancy',                     sessionIn: '2024-04-29 08:45', sessionOut: '2024-04-29 10:00' },
  { id: 'LOG-20240025', email: '2020-0623@gordoncollege.edu.ph', name: 'Sheila Domingo',        dept: 'CBA',  prog: 'BS Financial Management',             sessionIn: '2024-04-29 12:00', sessionOut: '2024-04-29 14:15' },
]);

function maskEmail(email) {
  const [user, domain] = email.split('@');
  return user.slice(0, 4) + '*****' + '@' + domain;
}

function fmt(dt) {
  if (!dt) return {};
  const d = new Date(dt);
  return {
    date: d.toLocaleDateString('en-PH', { month: 'short', day: '2-digit', year: 'numeric' }),
    time: d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true }),
  };
}

const departments = computed(() => [...new Set(logs.value.map(r => r.dept))].sort());

const filtered = computed(() => logs.value.filter(r => {
  const q = search.value.toLowerCase();
  const matchQ = !q || r.name.toLowerCase().includes(q) || r.email.includes(q) || r.id.toLowerCase().includes(q);
  const matchD = !deptFilter.value || r.dept === deptFilter.value;
  const matchS = !statusFilter.value
    || (statusFilter.value === 'active' && !r.sessionOut)
    || (statusFilter.value === 'closed' && !!r.sessionOut);
  return matchQ && matchD && matchS;
}));

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PER_PAGE)));
const pagStart   = computed(() => (page.value - 1) * PER_PAGE + 1);
const pagEnd     = computed(() => Math.min(page.value * PER_PAGE, filtered.value.length));
const paginated  = computed(() => filtered.value.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE));

const stats = computed(() => {
  const active = logs.value.filter(r => !r.sessionOut).length;
  return [
    { label: 'Total logs',       value: logs.value.length },
    { label: 'Active sessions',  value: active, accent: true },
    { label: 'Closed sessions',  value: logs.value.length - active },
    { label: 'Departments',      value: departments.value.length },
  ];
});
</script>

<style scoped src="@/assets/admin/AdminLogRecords.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>