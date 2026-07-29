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

                <!-- STATS — order: Severe → Moderate → Low → Awaiting -->
<div class="whole-stat-card">
    <div
        class="stat-card-wrap s-severe stagger-1"
        :class="{ 'stat-active': filterPriority === 'severe' }"
        style="cursor: pointer;"
        @click="toggleStatFilter('severe')"
    >
        <div class="stat-left">
            <span class="stat-label">Severe</span>
            <span class="stat-number">{{ statsData.severe_count ?? 0 }}</span>
        </div>
        <div class="stat-icon icon-severe"><i class="bx bxs-bell-ring"></i></div>
    </div>
    <div
        class="stat-card-wrap s-moderate stagger-2"
        :class="{ 'stat-active': filterPriority === 'moderate' }"
        style="cursor: pointer;"
        @click="toggleStatFilter('moderate')"
    >
        <div class="stat-left">
            <span class="stat-label">Moderate</span>
            <span class="stat-number">{{ statsData.moderate_count ?? 0 }}</span>
        </div>
        <div class="stat-icon icon-moderate"><i class="bx bx-info-circle"></i></div>
    </div>
    <div
        class="stat-card-wrap s-low stagger-3"
        :class="{ 'stat-active': filterPriority === 'low' }"
        style="cursor: pointer;"
        @click="toggleStatFilter('low')"
    >
        <div class="stat-left">
            <span class="stat-label">Low</span>
            <span class="stat-number">{{ statsData.low_count ?? 0 }}</span>
        </div>
        <div class="stat-icon icon-low"><i class="bx bx-check-shield"></i></div>
    </div>
    <div
        class="stat-card-wrap s-pending stagger-4"
        :class="{ 'stat-active': showOnlyUnclassified }"
        style="cursor: pointer;"
        @click="toggleUnclassifiedFilter"
    >
        <div class="stat-left">
            <span class="stat-label">Awaiting Review</span>
            <span class="stat-number">{{ statsData.unclassified_count ?? unclassifiedAlerts.length }}</span>
        </div>
        <div class="stat-icon icon-pending"><i class="bx bx-time-five"></i></div>
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
                <div class="alert-section fade-in" ref="unclassifiedSectionRef" v-if="unclassifiedAlerts.length > 0 || showOnlyUnclassified">
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

                                <div v-if="alert.severe_alerts_count >= 2" class="alert-urgent-warning">
                                    <i class="bx bxs-error-circle"></i>
                                    <span>Urgent Support Needed: This student has accumulated {{ alert.severe_alerts_count }} severe alerts!</span>
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

                                <!-- Flag reason badge -->
                                <div v-if="alert.flag_reason" class="alert-flag-reason">
                                    <i class="bx bx-flag"></i>
                                    <span>{{ alert.flag_reason }}</span>
                                </div>

                                <p class="alert-user">
                                    {{ alert.user_display }}
                                    <span v-if="alert.total_alerts_count !== undefined" class="student-alert-badge" :class="studentAlertBadgeClass(alert)">
                                        {{ alert.total_alerts_count }} alert{{ alert.total_alerts_count !== 1 ? 's' : '' }}
                                        <template v-if="alert.severe_alerts_count > 0">
                                            · {{ alert.severe_alerts_count }} severe
                                        </template>
                                    </span>
                                    ·
                                    <span class="alert-email-text">
                                        {{ revealedEmails.has(alert.id) ? alert.real_email : alert.masked_email }}
                                    </span>
                                    <button
                                        v-if="alert.real_email"
                                        class="reveal-email-btn"
                                        :title="revealedEmails.has(alert.id) ? 'Hide email' : 'Show full email'"
                                        @click="toggleEmail(alert.id)"
                                    >
                                        <i :class="revealedEmails.has(alert.id) ? 'bx bx-hide' : 'bx bx-show'"></i>
                                    </button>
                                </p>

                                <!-- SEVERITY ASSIGNMENT -->
                                <div class="severity-assign-row">
                                    <div class="severity-assign-header">
                                        <span class="severity-assign-label">Assign severity:</span>
                                        <!-- High-risk lock notice -->
                                        <span v-if="isHighRisk(alert)" class="severity-lock-notice">
                                            <i class="bx bx-lock-alt"></i>
                                            High-risk — only <strong>Severe</strong> can be assigned
                                        </span>
                                    </div>
                                    <div class="severity-assign-controls">
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
                                                :disabled="isHighRisk(alert)"
                                                :title="isHighRisk(alert) ? 'Not available — message is flagged as high-risk' : ''"
                                                @click="setPendingSeverity(alert.id, 'moderate')"
                                            >
                                                <i class="bx bx-info-circle"></i> Moderate
                                            </button>
                                            <button
                                                class="severity-assign-btn severity-assign-btn--low"
                                                :class="{ selected: pendingSeverity[alert.id] === 'low' }"
                                                :disabled="isHighRisk(alert)"
                                                :title="isHighRisk(alert) ? 'Not available — message is flagged as high-risk' : ''"
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
                            </div>

                            <div class="alert-card-actions">
                                <!-- No actions on unclassified cards — admin must assign severity first -->
                            </div>
                        </div>

                        <p v-if="unclassifiedAlerts.length === 0" class="no-alerts">
                            No alerts awaiting classification.
                        </p>
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

                <!-- ── CLASSIFIED ALERTS — table layout ── -->
                <div class="alert-section fade-in stagger-4">
                    <div class="alert-section-header">
                        <div class="alert-section-label-group">
                            <span class="alert-section-dot dot-classified"></span>
                            <span class="alert-section-label">Classified Alerts</span>
                            <span class="alert-section-count">{{ sortedClassifiedAlerts.length }}</span>
                        </div>
                        <div class="alert-filters">
                            <div class="filter-search-wrap">
                                <i class="bx bx-search filter-search-icon"></i>
                                <input
                                    v-model="searchQuery"
                                    class="filter-search-input"
                                    placeholder="Search student or message…"
                                    @input="classifiedPage = 1"
                                />
                            </div>
                            <select v-model="filterPriority" class="filter-select" @change="onFilterChange">
                                <option value="">All severities</option>
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

                    <!-- Table wrapper -->
                    <div class="alert-table-wrap">
                        <table class="alert-table" v-if="pagedClassified.length > 0">
                            <thead>
                                <tr>
                                    <th class="col-severity">Severity</th>
                                    <th class="col-student">Flagged ID</th>
                                    <th class="col-message">Message</th>
                                    <th class="col-keywords">Keywords</th>
                                    <th class="col-status">Status</th>
                                    <th class="col-time">Time</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="alert in pagedClassified"
                                    :key="alert.id"
                                    class="alert-row"
                                    :class="[`row-${alert.severity}`, { 'is-assigning': assigningId === alert.id }]"
                                    @click="openDetailModal(alert)"
                                >
                                    <td class="col-severity">
                                        <span class="badge" :class="`b-${alert.severity}`">
                                            <i v-if="alert.severity === 'severe'" class="bx bxs-bell-ring"></i>
                                            <i v-else-if="alert.severity === 'moderate'" class="bx bx-info-circle"></i>
                                            <i v-else class="bx bx-check-shield"></i>
                                            {{ capitalize(alert.severity) }}
                                        </span>
                                    </td>
                                    <td class="col-student">
                                        <div class="student-cell">
                                            <span class="student-name">{{ alert.user_display }}</span>
                                        </div>
                                    </td>
                                    <td class="col-message">
                                        <span class="message-preview">"{{ alert.message }}"</span>
                                    </td>
                                    <td class="col-keywords">
                                        <div class="kw-cell">
                                            <span
                                                v-for="kw in (alert.detected_keywords || []).slice(0, 2)"
                                                :key="kw"
                                                class="alert-keyword-tag"
                                                :class="`keyword--${alert.severity}`"
                                            >{{ kw }}</span>
                                            <span v-if="(alert.detected_keywords || []).length > 2" class="kw-more">+{{ alert.detected_keywords.length - 2 }}</span>
                                        </div>
                                    </td>
                                    <td class="col-status">
                                        <div class="status-cell">
                                            <span class="badge" :class="`b-${alert.status}`">
                                                <i v-if="alert.status === 'new'" class="bx bx-error-circle"></i>
                                                <i v-else-if="alert.status === 'reviewed'" class="bx bx-search-alt"></i>
                                                <i v-else-if="alert.status === 'resolved'" class="bx bx-check-circle"></i>
                                                {{ alert.status === 'reviewed' ? 'Under review' : capitalize(alert.status) }}
                                            </span>
                                            <span
                                                v-if="hasAppointment(alert)"
                                                class="appt-linked-badge"
                                                :class="{ 'appt-linked-badge--done': isAppointmentDone(alert), 'appt-linked-badge--noshow': alert.appointment_status === 'did_not_attend' }"
                                                :title="isAppointmentDone(alert) ? 'Appointment done — can resolve' : (alert.appointment_status === 'did_not_attend' ? 'Did not attend' : 'Has scheduled appointment')"
                                            >
                                                <i :class="isAppointmentDone(alert) ? 'bx bx-check-circle' : (alert.appointment_status === 'did_not_attend' ? 'bx bx-user-x' : 'bx bx-calendar')"></i>
                                                {{ isAppointmentDone(alert) ? 'Done' : (alert.appointment_status === 'did_not_attend' ? 'No show' : 'Appt') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="col-time">
                                        <span class="alert-time">
                                            <i class="bx bx-time-five"></i> {{ formatTime(alert.created_at) }}
                                        </span>
                                    </td>
                                    <td class="col-actions" @click.stop>
                                        <div class="row-actions">
                                            <button class="action-btn action-btn--email" title="Send email" @click="openEmailModal(alert)">
                                                <i class="bx bx-send"></i>
                                            </button>
                                            <button
                                                class="action-btn action-btn--review"
                                                title="Mark under review"
                                                @click="updateStatus(alert, 'reviewed')"
                                                :disabled="alert.status === 'reviewed' || alert.status === 'resolved'"
                                            >
                                                <i class="bx bx-search-alt"></i>
                                            </button>
                                            <button
                                                class="action-btn action-btn--resolve"
                                                :title="resolveButtonTitle(alert)"
                                                @click="openResolveModal(alert)"
                                                :disabled="!canResolveOnCrisisPage(alert)"
                                            >
                                                <i class="bx bx-check"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="sortedClassifiedAlerts.length === 0 && !loading" class="empty-state-filtered">
                            <div class="empty-icon-wrap-filtered"><i class='bx bx-filter-alt'></i></div>
                            <p class="empty-title-filtered">No matching alerts</p>
                            <p class="empty-subtitle-filtered">Try adjusting your filters to see more results.</p>
                        </div>
                        <p v-if="loading" class="no-alerts">Loading alerts…</p>
                    </div>

                    <!-- Pagination — Classified Alerts -->
                    <div class="pagination-row">
                        <button class="page-btn" :disabled="classifiedPage === 1" @click="classifiedPage--"><i class="bx bx-chevron-left"></i></button>
                        <button
                            v-for="p in pageRange(totalClassifiedPages)"
                            :key="p"
                            class="page-btn"
                            :class="{ 'page-btn--active': p === classifiedPage }"
                            @click="classifiedPage = p"
                        >{{ p }}</button>
                        <button class="page-btn" :disabled="classifiedPage === totalClassifiedPages" @click="classifiedPage++"><i class="bx bx-chevron-right"></i></button>
                        <span class="page-info">
                            {{ (classifiedPage - 1) * CLASSIFIED_PAGE_SIZE + 1 }}–{{ Math.min(classifiedPage * CLASSIFIED_PAGE_SIZE, sortedClassifiedAlerts.length) }}
                            of {{ sortedClassifiedAlerts.length }}
                        </span>
                    </div>
                </div>
            </div>
        </main>

        <!-- Modals -->
        <Teleport to="body">
            <!-- ALERT DETAIL MODAL -->
            <Transition name="modal-fade">
                <div v-if="detailModal.visible" class="email-modal-overlay" @click.self="closeDetailModal">
                    <div class="email-modal detail-modal" :class="detailModal.alert ? `detail-modal--${detailModal.alert.severity}` : ''">
                        <div class="email-modal-header">
                            <div class="email-modal-header-left">
                                <div class="email-modal-icon" :class="detailModal.alert ? `icon-${detailModal.alert.severity}` : ''">
                                    <i v-if="detailModal.alert?.severity === 'severe'" class="bx bxs-bell-ring"></i>
                                    <i v-else-if="detailModal.alert?.severity === 'moderate'" class="bx bx-info-circle"></i>
                                    <i v-else class="bx bx-check-shield"></i>
                                </div>
                                <div>
                                    <p class="email-modal-title">Alert Details</p>
                                    <p class="email-modal-subtitle">
                                        {{ detailModal.alert?.user_display }}
                                        <template v-if="detailModal.alert">
                                            · {{ capitalize(detailModal.alert.severity) }}
                                            · {{ detailModal.alert.status === 'reviewed' ? 'Under review' : capitalize(detailModal.alert.status) }}
                                        </template>
                                    </p>
                                </div>
                            </div>
                            <button class="email-modal-close" @click="closeDetailModal">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                        <div class="email-modal-body" v-if="detailModal.alert">
                            <div v-if="detailModal.alert.severe_alerts_count >= 2" class="alert-urgent-warning">
                                <i class="bx bxs-error-circle"></i>
                                <span>Urgent Support Needed: This student has accumulated {{ detailModal.alert.severe_alerts_count }} severe alerts!</span>
                            </div>

                            <div class="email-field-group">
                                <span class="email-field-label">Flagged message</span>
                                <p class="alert-message detail-modal-message">"{{ detailModal.alert.message }}"</p>
                            </div>

                            <div class="email-field-group" v-if="(detailModal.alert.detected_keywords || []).length">
                                <span class="email-field-label">Keywords</span>
                                <div class="alert-keywords-row">
                                    <span
                                        v-for="kw in (detailModal.alert.detected_keywords || [])"
                                        :key="kw"
                                        class="alert-keyword-tag"
                                        :class="`keyword--${detailModal.alert.severity}`"
                                    >{{ kw }}</span>
                                </div>
                            </div>

                            <div class="email-field-group" v-if="detailModal.alert.flag_reason">
                                <span class="email-field-label">Flag reason</span>
                                <div class="alert-flag-reason">
                                    <i class="bx bx-flag"></i>
                                    <span>{{ detailModal.alert.flag_reason }}</span>
                                </div>
                            </div>

                            <div class="email-field-group">
                                <span class="email-field-label">Student alert history</span>
                                <div class="student-history-cell student-history-cell--detail">
                                    <span class="history-count history-count--total">
                                        <i class="bx bx-flag"></i>
                                        {{ detailModal.alert.total_alerts_count ?? 0 }} total crisis alert{{ (detailModal.alert.total_alerts_count ?? 0) !== 1 ? 's' : '' }}
                                    </span>
                                    <span
                                        class="history-count history-count--severe"
                                        :class="{ 'history-count--urgent': needsUrgentHelp(detailModal.alert) }"
                                    >
                                        <i class="bx bxs-bell-ring"></i>
                                        {{ detailModal.alert.severe_alerts_count ?? 0 }} classified severe
                                    </span>
                                </div>
                                <p v-if="needsUrgentHelp(detailModal.alert)" class="schedule-hint schedule-hint--urgent">
                                    <i class="bx bxs-error-circle"></i>
                                    This student has multiple severe alerts and needs urgent wellness support.
                                </p>
                            </div>

                            <div class="email-field-group">
                                <span class="email-field-label">Student</span>
                                <p class="alert-user">
                                    {{ detailModal.alert.user_display }}
                                    <span v-if="detailModal.alert.total_alerts_count !== undefined" class="student-alert-badge" :class="studentAlertBadgeClass(detailModal.alert)">
                                        {{ detailModal.alert.total_alerts_count }} alert{{ detailModal.alert.total_alerts_count !== 1 ? 's' : '' }}
                                        <template v-if="detailModal.alert.severe_alerts_count > 0">
                                            · {{ detailModal.alert.severe_alerts_count }} severe
                                        </template>
                                    </span>
                                    ·
                                    <span class="alert-email-text">
                                        {{ revealedEmails.has(detailModal.alert.id) ? detailModal.alert.real_email : detailModal.alert.masked_email }}
                                    </span>
                                    <button
                                        v-if="detailModal.alert.real_email"
                                        class="reveal-email-btn"
                                        :title="revealedEmails.has(detailModal.alert.id) ? 'Hide email' : 'Show email'"
                                        @click="toggleEmail(detailModal.alert.id)"
                                    >
                                        <i :class="revealedEmails.has(detailModal.alert.id) ? 'bx bx-hide' : 'bx bx-show'"></i>
                                    </button>
                                </p>
                            </div>

                            <div class="email-field-group" v-if="hasAppointment(detailModal.alert)">
                                <span class="email-field-label">Scheduled appointment</span>
                                <div class="detail-appt-info" :class="{ 'detail-appt-info--done': isAppointmentDone(detailModal.alert) }">
                                    <i :class="isAppointmentDone(detailModal.alert) ? 'bx bx-check-circle' : (detailModal.alert.appointment_status === 'did_not_attend' ? 'bx bx-user-x' : 'bx bx-calendar')"></i>
                                    <span>
                                        {{ formatAppointmentDate(detailModal.alert.appointment_date) }}
                                        at {{ formatAppointmentTime(detailModal.alert.appointment_time) }}
                                        <template v-if="detailModal.alert.appointment_status">
                                            · {{ detailModal.alert.appointment_status === 'done' ? 'Done' : (detailModal.alert.appointment_status === 'did_not_attend' ? 'Did not attend' : capitalize(detailModal.alert.appointment_status)) }}
                                        </template>
                                    </span>
                                </div>
                                <p class="schedule-hint" v-if="!isAppointmentDone(detailModal.alert)">
                                    <i class="bx bx-info-circle"></i>
                                    Mark this appointment as Done on the Appointments page before resolving.
                                </p>
                                <p class="schedule-hint schedule-hint--ok" v-else>
                                    <i class="bx bx-check-circle"></i>
                                    Appointment completed — this alert can now be resolved.
                                </p>
                            </div>

                            <div class="email-field-group">
                                <span class="email-field-label">Time flagged</span>
                                <span class="alert-time">
                                    <i class="bx bx-time-five"></i> {{ formatTime(detailModal.alert.created_at) }}
                                </span>
                            </div>

                            <div class="severity-assign-row">
                                <div class="severity-assign-header">
                                    <span class="severity-assign-label">Change severity:</span>
                                    <span v-if="isHighRisk(detailModal.alert)" class="severity-lock-notice">
                                        <i class="bx bx-lock-alt"></i>
                                        High-risk — severity locked to <strong>Severe</strong>
                                    </span>
                                </div>
                                <div class="severity-assign-controls">
                                    <div class="severity-assign-buttons">
                                        <button
                                            class="severity-assign-btn severity-assign-btn--severe"
                                            :disabled="isHighRisk(detailModal.alert)"
                                            :class="{ selected: pendingSeverity[detailModal.alert.id] === 'severe' || (!pendingSeverity[detailModal.alert.id] && detailModal.alert.severity === 'severe') }"
                                            @click="setPendingSeverity(detailModal.alert.id, 'severe')"
                                        >
                                            <i class="bx bxs-bell-ring"></i> Severe
                                        </button>
                                        <button
                                            class="severity-assign-btn severity-assign-btn--moderate"
                                            :disabled="isHighRisk(detailModal.alert)"
                                            :class="{ selected: pendingSeverity[detailModal.alert.id] === 'moderate' || (!pendingSeverity[detailModal.alert.id] && detailModal.alert.severity === 'moderate') }"
                                            @click="setPendingSeverity(detailModal.alert.id, 'moderate')"
                                        >
                                            <i class="bx bx-info-circle"></i> Moderate
                                        </button>
                                        <button
                                            class="severity-assign-btn severity-assign-btn--low"
                                            :disabled="isHighRisk(detailModal.alert)"
                                            :class="{ selected: pendingSeverity[detailModal.alert.id] === 'low' || (!pendingSeverity[detailModal.alert.id] && detailModal.alert.severity === 'low') }"
                                            @click="setPendingSeverity(detailModal.alert.id, 'low')"
                                        >
                                            <i class="bx bx-check-shield"></i> Low
                                        </button>
                                    </div>
                                    <button
                                        v-if="pendingSeverity[detailModal.alert.id] && pendingSeverity[detailModal.alert.id] !== detailModal.alert.severity"
                                        class="severity-confirm-btn"
                                        :disabled="assigningId === detailModal.alert.id"
                                        @click="confirmSeverity(detailModal.alert)"
                                    >
                                        <i class="bx bx-check"></i>
                                        {{ assigningId === detailModal.alert.id ? 'Saving…' : 'Confirm' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="email-modal-footer" v-if="detailModal.alert">
                            <button class="action-btn action-btn--email" @click="openEmailModalFromDetail">
                                <i class="bx bx-send"></i> Send Email
                            </button>
                            <button
                                class="action-btn action-btn--resolve"
                                :title="resolveButtonTitle(detailModal.alert)"
                                @click="openResolveModal(detailModal.alert)"
                                :disabled="!canResolveOnCrisisPage(detailModal.alert)"
                            >
                                <i class="bx bx-check"></i> Resolve
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

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
                            <!-- SCHEDULE APPOINTMENT — only for severe / moderate -->
<div
    v-if="emailModal.severity === 'severe' || emailModal.severity === 'moderate'"
    class="email-field-group"
>
    <span class="email-field-label">Schedule Appointment</span>

    <label class="schedule-checkbox-label">
        <input
            type="checkbox"
            class="schedule-checkbox"
            v-model="emailModal.withAppointment"
        />
        <span class="schedule-checkbox-text">Include appointment with this email</span>
    </label>

    <!-- Date & time — only when checked -->
    <div v-if="emailModal.withAppointment" class="schedule-fields">
        <div class="schedule-row">
            <div class="schedule-input-wrap">
                <i class="bx bx-calendar schedule-icon"></i>
                <input
                    type="date"
                    class="schedule-input"
                    v-model="emailModal.appointmentDate"
                    :min="todayDate"
                />
            </div>
            <div class="schedule-input-wrap">
                <i class="bx bx-time schedule-icon"></i>
                <input
                    type="time"
                    class="schedule-input"
                    v-model="emailModal.appointmentTime"
                />
            </div>
        </div>
        <p class="schedule-hint">
            <i class="bx bx-info-circle"></i>
            An appointment request will be included in the email sent to the student.
        </p>
    </div>

    <!-- When unchecked -->
    <p v-else class="schedule-none-text">No appointment — email only.</p>
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
                            <button class="action-btn action-btn--email" @click="openEmailConfirm">
                                <i class="bx bx-send"></i> Send Email
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- EMAIL SEND CONFIRMATION MODAL -->
            <Transition name="modal-fade">
                <div v-if="emailConfirmModal.visible" class="email-modal-overlay" @click.self="closeEmailConfirm">
                    <div class="email-modal resolve-modal">
                        <div class="email-modal-header">
                            <div class="email-modal-header-left">
                                <div class="email-modal-icon"><i class="bx bx-envelope"></i></div>
                                <div>
                                    <p class="email-modal-title">Confirm Send Email</p>
                                    <p class="email-modal-subtitle">Review before sending to student</p>
                                </div>
                            </div>
                            <button class="email-modal-close" @click="closeEmailConfirm">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                        <div class="email-modal-body resolve-body">
                            <div class="resolve-icon-large"><i class="bx bx-send"></i></div>
                            <p class="resolve-text">
                                Are you sure you want to send this crisis alert email to
                                <strong class="resolve-user">{{ emailConfirmModal.maskedEmail }}</strong>?
                            </p>
                            <p class="resolve-subtext" v-if="emailConfirmModal.withAppointment">
                                An appointment on {{ formatAppointmentDate(emailConfirmModal.appointmentDate) }} at {{ formatAppointmentTime(emailConfirmModal.appointmentTime) }} will be included.
                            </p>
                            <p class="resolve-subtext" v-else>
                                This email will be delivered to the student immediately.
                            </p>
                        </div>
                        <div class="email-modal-footer">
                            <button class="action-btn action-btn--email" @click="confirmSendEmail" :disabled="sendingEmail">
                                <i class="bx bx-send"></i> {{ sendingEmail ? 'Sending…' : 'Confirm & Send' }}
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
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import SidebarAdmin from '@/components/sidebarAdmin.vue';
import HeaderAdmin from '@/components/headerAdmin.vue';

const toast = useToast();
const sidebarOpen = ref(localStorage.getItem('adminSidebarOpen') !== 'false');
const loading = ref(false);

// ── Email reveal toggle ────────────────────────────────────────
const revealedEmails = ref(new Set());
const toggleEmail = (id) => {
    const updated = new Set(revealedEmails.value);
    updated.has(id) ? updated.delete(id) : updated.add(id);
    revealedEmails.value = updated;
};

// ── Alert Data (declared first — used by pagination computeds below) ──
const alerts = ref([]);
const unclassifiedAlerts = ref([]);
const statsData = ref({ severe_count: 0, moderate_count: 0, low_count: 0, unclassified_count: 0 });

// ── Awaiting Review filter / scroll ───────────────────────────
const showOnlyUnclassified = ref(false);
const unclassifiedSectionRef = ref(null);

const toggleUnclassifiedFilter = async () => {
    showOnlyUnclassified.value = !showOnlyUnclassified.value;
    if (showOnlyUnclassified.value) {
        await nextTick();
        unclassifiedSectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// ── Search ───────────────────────────────────────────────────
const searchQuery = ref('');

// ── Detail modal (classified alerts) ──────────────────────────
const detailModal = ref({ visible: false, alert: null });

const openDetailModal = (alert) => {
    detailModal.value = { visible: true, alert };
};

const closeDetailModal = () => {
    detailModal.value.visible = false;
    setTimeout(() => {
        if (!detailModal.value.visible) detailModal.value.alert = null;
    }, 200);
};

const openEmailModalFromDetail = () => {
    if (!detailModal.value.alert) return;
    openEmailModal(detailModal.value.alert);
};

const hasAppointment = (alert) => !!(alert?.appointment_date);

const isAppointmentDone = (alert) => alert?.appointment_status === 'done';

const needsUrgentHelp = (alert) => (alert?.severe_alerts_count ?? 0) >= 2;

const studentAlertBadgeClass = (alert) => ({
    'has-severe': (alert?.severe_alerts_count ?? 0) > 0,
    'has-urgent': needsUrgentHelp(alert),
});

const canResolveOnCrisisPage = (alert) => {
    if (!alert) return false;
    if (alert.status !== 'reviewed') return false;
    // No appointment → resolve here. With appointment → only after marked done.
    if (hasAppointment(alert) && !isAppointmentDone(alert)) return false;
    return true;
};

const resolveButtonTitle = (alert) => {
    if (!alert) return 'Resolve';
    if (alert.status !== 'reviewed') return 'Mark under review first';
    if (hasAppointment(alert) && !isAppointmentDone(alert)) {
        if (alert.appointment_status === 'did_not_attend') {
            return 'Appointment was a no-show — mark as done after a completed session';
        }
        return 'Mark the appointment as Done on the Appointments page first';
    }
    return 'Resolve';
};

// ── Pagination ─────────────────────────────────────────────────
const PAGE_SIZE = 5;            // unclassified cards
const CLASSIFIED_PAGE_SIZE = 8; // classified table rows
const pageRange = (n) => Array.from({ length: n }, (_, i) => i + 1);

const unclassifiedPage = ref(1);
const totalUnclassifiedPages = computed(() =>
    Math.max(1, Math.ceil(unclassifiedAlerts.value.length / PAGE_SIZE))
);
const pagedUnclassified = computed(() => {
    const start = (unclassifiedPage.value - 1) * PAGE_SIZE;
    return unclassifiedAlerts.value.slice(start, start + PAGE_SIZE);
});

const classifiedPage = ref(1);
const totalClassifiedPages = computed(() =>
    Math.max(1, Math.ceil(sortedClassifiedAlerts.value.length / CLASSIFIED_PAGE_SIZE))
);
const pagedClassified = computed(() => {
    const start = (classifiedPage.value - 1) * CLASSIFIED_PAGE_SIZE;
    return sortedClassifiedAlerts.value.slice(start, start + CLASSIFIED_PAGE_SIZE);
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

// Severity sort order: severe first, moderate second, low third
const SEVERITY_ORDER = { severe: 0, moderate: 1, low: 2 };

// Classified alerts: filtered by search + sorted by severity
const sortedClassifiedAlerts = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    return [...alerts.value]
        .filter(a => !q ||
            (a.user_display ?? '').toLowerCase().includes(q) ||
            (a.message ?? '').toLowerCase().includes(q)
        )
        .sort((a, b) => (SEVERITY_ORDER[a.severity] ?? 99) - (SEVERITY_ORDER[b.severity] ?? 99));
});

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

        // New API returns both unclassified and classified separately
        unclassifiedAlerts.value = res.data.unclassified?.data ?? [];
        alerts.value             = res.data.alerts?.data       ?? [];
        statsData.value          = res.data.stats              ?? { severe_count: 0, moderate_count: 0, low_count: 0, unclassified_count: 0 };
    } catch (err) {
        console.error('Failed to fetch crisis alerts:', err);
        toast.error('Failed to load crisis alerts.');
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (alert, newStatus) => {
    // Optimistic update — badge changes immediately before API call
    const previousStatus = alert.status;
    const targetInArray = alerts.value.find(a => a.id === alert.id);
    if (targetInArray) targetInArray.status = newStatus;
    else alert.status = newStatus; // fallback for static/local refs

    if (detailModal.value.alert?.id === alert.id) {
        detailModal.value.alert = { ...detailModal.value.alert, status: newStatus };
    }

    const label = newStatus === 'reviewed' ? 'under review' : 'resolved';

    try {
        const token = localStorage.getItem('token');
        await axios.patch(`/api/admin/crisis-alerts/${alert.id}`, { status: newStatus }, {
            headers: { Authorization: `Bearer ${token}` },
        });
        toast.success(`Alert from ${alert.user_display} is now ${label}.`, { timeout: 3000 });
        fetchAlerts();
    } catch (err) {
        console.error('Failed to update alert:', err);
        toast.error(err.response?.data?.message || 'Failed to update alert status.');
        // Revert optimistic update on failure
        if (targetInArray) targetInArray.status = previousStatus;
        if (detailModal.value.alert?.id === alert.id) {
            detailModal.value.alert = { ...detailModal.value.alert, status: previousStatus };
        }
    }
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('en-PH', {
        month: 'numeric', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit', hour12: true,
    });
};

const formatAppointmentDate = (dateStr) => {
    if (!dateStr) return '';
    const normalized = String(dateStr).includes('T') ? dateStr : `${dateStr}T00:00:00`;
    return new Date(normalized).toLocaleDateString('en-PH', {
        weekday: 'long', month: 'short', day: 'numeric', year: 'numeric',
    });
};

const formatAppointmentTime = (timeStr) => {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    const h = parseInt(hours, 10);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${minutes} ${ampm}`;
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

    try {
        const token = localStorage.getItem('token');
        await axios.patch(`/api/admin/crisis-alerts/${alert.id}`, { severity: chosen }, {
            headers: { Authorization: `Bearer ${token}` },
        });
        const copy = { ...pendingSeverity.value };
        delete copy[alert.id];
        pendingSeverity.value = copy;
        toast.success(`Alert classified as ${capitalize(chosen)}.`, { timeout: 3000 });
        await fetchAlerts();
        // Keep detail modal in sync with refreshed data
        if (detailModal.value.visible && detailModal.value.alert?.id === alert.id) {
            const refreshed = alerts.value.find(a => a.id === alert.id);
            if (refreshed) detailModal.value.alert = refreshed;
        }
    } catch (err) {
        console.error('Failed to assign severity:', err);
        toast.error('Failed to assign severity. Please try again.');
    } finally {
        assigningId.value = null;
    }
};

// ── Resolve Modal ──────────────────────────────────────────────
const resolveModal = ref({ visible: false, alert: null });

const openResolveModal  = (alert) => {
    if (!canResolveOnCrisisPage(alert)) {
        if (hasAppointment(alert) && !isAppointmentDone(alert)) {
            toast.info('Mark the appointment as Done on the Appointments page before resolving.');
        }
        return;
    }
    resolveModal.value = { visible: true, alert };
};
const closeResolveModal = () => {
    resolveModal.value.visible = false;
    setTimeout(() => { if (!resolveModal.value.visible) resolveModal.value.alert = null; }, 200);
};
const confirmResolve = async () => {
    if (resolveModal.value.alert) {
        if (!canResolveOnCrisisPage(resolveModal.value.alert)) {
            toast.info('Mark the appointment as Done on the Appointments page before resolving.');
            closeResolveModal();
            return;
        }
        await updateStatus(resolveModal.value.alert, 'resolved');
        closeResolveModal();
        closeDetailModal();
    }
};

// ── Email Modal ────────────────────────────────────────────────
const emailModal = ref({ visible: false, maskedEmail: '', subject: '', severity: '', body: '', alertId: null });

const openEmailModal = (alert) => {
    const defaultBody = `Dear Student,\n\nOur system has detected that you may be going through a difficult time. We want you to know that support is available and you are not alone.\n\nPlease don't hesitate to reach out to our guidance counselors or visit the wellness center at your earliest convenience.\n\nTake care of yourself.\n\nLeanOn Bot Support Team`;

    emailModal.value = {
        visible:         true,
        maskedEmail:     alert.masked_email,
        subject:         `Wellness Support — LeanOn Bot`,
        severity:        alert.severity || '',
        alertId:         alert.id,
        body:            defaultBody,
        appointmentDate: '',
        appointmentTime: '',
        withAppointment: false,
    };
};
const closeEmailModal = () => { emailModal.value.visible = false; };

const emailConfirmModal = ref({
    visible: false,
    maskedEmail: '',
    withAppointment: false,
    appointmentDate: '',
    appointmentTime: '',
});
const sendingEmail = ref(false);

const parseModalAppointmentDateTime = () => {
    if (!emailModal.value.appointmentDate || !emailModal.value.appointmentTime) return null;
    // `appointmentTime` is from <input type="time"> and comes as "HH:MM" (24h).
    // Combine into a local datetime for accurate "already passed" validation.
    const dt = new Date(`${emailModal.value.appointmentDate}T${emailModal.value.appointmentTime}:00`);
    return Number.isNaN(dt.getTime()) ? null : dt;
};

const validateAppointmentNotInPast = () => {
    if (!emailModal.value.withAppointment) return true;

    if (!emailModal.value.appointmentDate || !emailModal.value.appointmentTime) {
        toast.warning('Please select both appointment date and time.');
        return false;
    }

    const dt = parseModalAppointmentDateTime();
    if (!dt) {
        toast.warning('Invalid appointment date/time selected.');
        return false;
    }

    if (dt.getTime() < Date.now()) {
        toast.warning('Appointment time has already passed. Please choose a future time.');
        return false;
    }

    return true;
};

const openEmailConfirm = () => {
    if (!validateAppointmentNotInPast()) return;
    emailConfirmModal.value = {
        visible: true,
        maskedEmail: emailModal.value.maskedEmail,
        withAppointment: emailModal.value.withAppointment,
        appointmentDate: emailModal.value.appointmentDate,
        appointmentTime: emailModal.value.appointmentTime,
    };
};

const closeEmailConfirm = () => {
    if (sendingEmail.value) return;
    emailConfirmModal.value.visible = false;
};

const confirmSendEmail = async () => {
    if (sendingEmail.value) return;

    // Re-check on confirm click to prevent stale UI inputs.
    if (!validateAppointmentNotInPast()) return;

    sendingEmail.value = true;
    try {
        const token = localStorage.getItem('token');
        await axios.post(`/api/admin/crisis-alerts/${emailModal.value.alertId}/send-email`, {
            subject:          emailModal.value.subject,
            body:             emailModal.value.body,
            appointment_date: emailModal.value.withAppointment ? emailModal.value.appointmentDate : null,
            appointment_time: emailModal.value.withAppointment ? emailModal.value.appointmentTime : null,
        }, {
            headers: { Authorization: `Bearer ${token}` },
        });
        toast.success('Email sent successfully.', { timeout: 3000 });
        emailConfirmModal.value.visible = false;
        closeEmailModal();
        await fetchAlerts();
        // Refresh detail modal if open (e.g. appointment just scheduled)
        if (detailModal.value.visible && detailModal.value.alert?.id === emailModal.value.alertId) {
            const refreshed = alerts.value.find(a => a.id === emailModal.value.alertId);
            if (refreshed) detailModal.value.alert = refreshed;
        }
    } catch (err) {
        console.error('Failed to send email:', err);
        toast.error('Failed to send email.');
    } finally {
        sendingEmail.value = false;
    }
};

/* ADD STAT CARD CLICK FILTER*/
const toggleStatFilter = (level) => {
    filterPriority.value = filterPriority.value === level ? '' : level;
    classifiedPage.value = 1;
    fetchAlerts();
};

// ── High-risk lock: flag reasons that must ONLY be classified as Severe ──
const HIGH_RISK_FLAG_REASONS = new Set([
    'Self-harm or suicidal mention',
    'Hopelessness or worthlessness',
    'Emotional crisis expression',
    'Severe burnout or exhaustion',
]);

const isHighRisk = (alert) => HIGH_RISK_FLAG_REASONS.has(alert.flag_reason);

/*ADD APPOINTMENT ON MODAL */
// Add this computed near the top of your script (alongside other refs)
const todayDate = computed(() => new Date().toISOString().split('T')[0]);
</script>

<style scoped src="@/assets/admin/AdminCrisisAlert.css"></style>
<style src="@/assets/admin/admin-layout.css"></style>

<style scoped>
.alert-user {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.alert-email-text {
    font-family: monospace;
    font-size: 12.5px;
}

.reveal-email-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    padding: 2px 4px;
    cursor: pointer;
    color: #6b7280;
    border-radius: 4px;
    transition: color 0.15s, background 0.15s;
    line-height: 1;
}

.reveal-email-btn:hover {
    color: #0e6008;
    background: #f0fdf4;
}

.reveal-email-btn i {
    font-size: 14px;
}

/* ── Appointment linked badge (classified table) ── */
.appt-linked-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    margin-top: 5px;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}

.appt-linked-badge i {
    font-size: 12px;
}

.appt-linked-badge--done {
    background: #f0fdf4;
    color: #15803d;
    border-color: #86efac;
}

.appt-linked-badge--noshow {
    background: #fff7ed;
    color: #c2410c;
    border-color: #fed7aa;
}

.status-cell {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
}

/* ── Detail modal ── */
.detail-modal {
    width: 640px;
    max-width: 94vw;
}

.detail-modal-message {
    margin: 0;
    padding: 12px 14px;
    background: #f9fafb;
    border-radius: 10px;
    border-left: 3px solid #0e6008;
}

.detail-modal--severe .detail-modal-message { border-left-color: #ef4444; }
.detail-modal--moderate .detail-modal-message { border-left-color: #9F7A00; }
.detail-modal--low .detail-modal-message { border-left-color: #0A9569; }

.detail-appt-info {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    color: #1d4ed8;
}

.detail-appt-info i {
    font-size: 18px;
}

.detail-appt-info--done {
    background: #f0fdf4;
    border-color: #86efac;
    color: #15803d;
}

.schedule-hint--ok {
    color: #15803d;
}

.detail-modal .severity-assign-row {
    margin-top: 4px;
    padding-top: 16px;
}

.email-modal-icon.icon-severe {
    background: #fef2f2;
    border-color: #fca5a5;
    color: #b91c1c;
}
.email-modal-icon.icon-moderate {
    background: #fffbeb;
    border-color: #fde68a;
    color: #9f7a00;
}
.email-modal-icon.icon-low {
    background: #f0fdf4;
    border-color: #86efac;
    color: #15803d;
}

[data-theme="dark"] .appt-linked-badge {
    background: #1e2d4d;
    color: #93c5fd;
    border-color: #1e3a5f;
}

[data-theme="dark"] .appt-linked-badge--done {
    background: #14532d;
    color: #4ade80;
    border-color: #166534;
}

[data-theme="dark"] .appt-linked-badge--noshow {
    background: #431407;
    color: #fdba74;
    border-color: #9a3412;
}

[data-theme="dark"] .detail-modal-message {
    background: #161b27;
}

[data-theme="dark"] .detail-appt-info {
    background: #1e2d4d;
    border-color: #1e3a5f;
    color: #93c5fd;
}

[data-theme="dark"] .detail-appt-info--done {
    background: #14532d;
    border-color: #166534;
    color: #4ade80;
}

[data-theme="dark"] .schedule-hint--ok {
    color: #4ade80;
}

[data-theme="dark"] .email-modal-icon.icon-severe {
    background: #3b1010;
    border-color: #7f1d1d;
    color: #fca5a5;
}
[data-theme="dark"] .email-modal-icon.icon-moderate {
    background: #2d2410;
    border-color: #78500a;
    color: #fde68a;
}
[data-theme="dark"] .email-modal-icon.icon-low {
    background: #0d2818;
    border-color: #14532d;
    color: #86efac;
}

/* ── Student Alert Count Badge ── */
.student-alert-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 9px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 600;
    background: #f3f4f6;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}

.student-alert-badge.has-severe {
    background: #fff1f2;
    color: #b91c1c;
    border-color: #fca5a5;
    animation: pulse-badge 2.4s ease-in-out infinite;
}

.student-alert-badge.has-urgent {
    background: #fef2f2;
    color: #991b1b;
    border-color: #ef4444;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.15);
}

/* ── Student alert history (classified table) ── */
.student-history-cell {
    display: flex;
    flex-direction: column;
    gap: 5px;
    align-items: flex-start;
}

.student-history-cell--detail {
    gap: 8px;
}

.history-count {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 600;
    border: 1px solid transparent;
}

.history-count i {
    font-size: 13px;
}

.history-count--total {
    background: #f3f4f6;
    color: #4b5563;
    border-color: #e5e7eb;
}

.history-count--severe {
    background: #fff1f2;
    color: #b91c1c;
    border-color: #fca5a5;
}

.history-count--urgent {
    background: #fef2f2;
    color: #991b1b;
    border-color: #ef4444;
    animation: pulse-badge 2.4s ease-in-out infinite;
}

.urgent-student-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
}

.urgent-student-tag i {
    font-size: 13px;
    color: #ef4444;
}

.schedule-hint--urgent {
    color: #b91c1c;
    font-weight: 600;
}

.schedule-hint--urgent i {
    color: #ef4444;
}

@keyframes pulse-badge {
    0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    50%       { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.18); }
}

/* ── Urgent Warning Banner ── */
.alert-urgent-warning {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    padding: 9px 14px;
    background: #fff1f2;
    border: 1.5px solid #fca5a5;
    border-left: 4px solid #ef4444;
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 600;
    color: #b91c1c;
    animation: flash-warning 3s ease-in-out infinite;
}

.alert-urgent-warning i {
    font-size: 17px;
    color: #ef4444;
    flex-shrink: 0;
}

@keyframes flash-warning {
    0%, 100% { background: #fff1f2; border-color: #fca5a5; }
    50%       { background: #fee2e2; border-color: #ef4444; }
}
</style>

<style>
/* Non-scoped: admin-dark.css cannot pierce Vue scoped selectors */
[data-theme="dark"] .main-container {
  background-color: #0f1117 !important;
  color: #cbd5e1 !important;
}

[data-theme="dark"] .title { color: #f3f4f6 !important; }
[data-theme="dark"] .subtext { color: #9ca3af !important; }

[data-theme="dark"] .stat-card-wrap {
  background: linear-gradient(145deg, #1e2533, #1a2030) !important;
  border-color: #2d3748 !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.28) !important;
}

[data-theme="dark"] .stat-label { color: #9ca3af !important; }
[data-theme="dark"] .stat-number { color: #f3f4f6 !important; }

[data-theme="dark"] .icon-severe {
  background: #3b1010 !important;
  border-color: #7f1d1d !important;
  color: #fca5a5 !important;
}
[data-theme="dark"] .icon-moderate {
  background: #2d2410 !important;
  border-color: #78500a !important;
  color: #fde68a !important;
}
[data-theme="dark"] .icon-low {
  background: #0d2818 !important;
  border-color: #14532d !important;
  color: #86efac !important;
}
[data-theme="dark"] .icon-pending {
  background: #2d3748 !important;
  border-color: #374151 !important;
  color: #9ca3af !important;
}

[data-theme="dark"] .section-card,
[data-theme="dark"] .alert-card {
  background: #1e2533 !important;
  border-color: #2d3748 !important;
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.25) !important;
}

[data-theme="dark"] .alert-card:hover {
  border-color: #4b5563 !important;
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.35) !important;
}

[data-theme="dark"] .section-label,
[data-theme="dark"] .alert-section-label { color: #e2e8f0 !important; }
[data-theme="dark"] .alert-section-count {
  background: #2d3748 !important;
  color: #9ca3af !important;
  border-color: #374151 !important;
}
[data-theme="dark"] .alert-section-hint { color: #6b7280 !important; }

[data-theme="dark"] .severity-tab {
  background: #1a2030 !important;
  border-color: #374151 !important;
  color: #9ca3af !important;
}
[data-theme="dark"] .severity-tab:hover {
  background: #243044 !important;
  color: #e2e8f0 !important;
}

[data-theme="dark"] .keyword-tags.severe .keyword-tag {
  background: #3b1010 !important;
  border-color: #7f1d1d !important;
  color: #fca5a5 !important;
}
[data-theme="dark"] .keyword-tags.moderate .keyword-tag {
  background: #2d2410 !important;
  border-color: #78500a !important;
  color: #fde68a !important;
}
[data-theme="dark"] .keyword-tags.low .keyword-tag {
  background: #0d2818 !important;
  border-color: #14532d !important;
  color: #86efac !important;
}

/* Search & filters — remove light flash */
[data-theme="dark"] .filter-search-input,
[data-theme="dark"] .filter-select {
  background-color: #1a2030 !important;
  background-image: none !important;
  border-color: #374151 !important;
  color: #e2e8f0 !important;
}
[data-theme="dark"] .filter-select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%239ca3af' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E") !important;
  background-repeat: no-repeat !important;
  background-position: right 12px center !important;
}
[data-theme="dark"] .filter-search-input::placeholder { color: #6b7280 !important; }
[data-theme="dark"] .filter-search-icon { color: #6b7280 !important; }
[data-theme="dark"] .filter-search-input:focus,
[data-theme="dark"] .filter-select:focus,
[data-theme="dark"] .filter-select:hover {
  border-color: #4ade80 !important;
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.12) !important;
}

/* Table */
[data-theme="dark"] .alert-table-wrap {
  background: #1e2533 !important;
  border-color: #2d3748 !important;
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.3) !important;
}
[data-theme="dark"] .alert-table thead tr { background: #161b27 !important; }
[data-theme="dark"] .alert-table th {
  color: #9ca3af !important;
  border-bottom-color: #2d3748 !important;
}
[data-theme="dark"] .alert-table td,
[data-theme="dark"] .alert-row td {
  background: #1e2533 !important;
  color: #e2e8f0 !important;
  border-bottom-color: #2d3748 !important;
}
[data-theme="dark"] .alert-row { background: #1e2533 !important; }
[data-theme="dark"] .alert-row:hover,
[data-theme="dark"] .alert-row:hover td {
  background: #243044 !important;
}
[data-theme="dark"] .alert-row.row-expanded,
[data-theme="dark"] .alert-row.row-expanded:hover,
[data-theme="dark"] .alert-row.row-expanded td {
  background: #1a2435 !important;
}
[data-theme="dark"] .detail-panel {
  background: #161b27 !important;
  border-top-color: #2d3748 !important;
  border-bottom-color: #2d3748 !important;
}

/* Message contrast — primary readability fix */
[data-theme="dark"] .message-preview {
  color: #e2e8f0 !important;
}
[data-theme="dark"] .alert-message {
  color: #f1f5f9 !important;
}
[data-theme="dark"] .student-name { color: #f3f4f6 !important; }
[data-theme="dark"] .alert-user,
[data-theme="dark"] .alert-email-text,
[data-theme="dark"] .alert-time { color: #9ca3af !important; }
[data-theme="dark"] .alert-time i { color: #6b7280 !important; }
[data-theme="dark"] .kw-more {
  background: #2d3748 !important;
  border-color: #374151 !important;
  color: #9ca3af !important;
}

[data-theme="dark"] .b-severe   { background: #3b1010 !important; color: #fca5a5 !important; border-color: #7f1d1d !important; }
[data-theme="dark"] .b-moderate { background: #2d2410 !important; color: #fde68a !important; border-color: #78500a !important; }
[data-theme="dark"] .b-low      { background: #0d2818 !important; color: #86efac !important; border-color: #14532d !important; }
[data-theme="dark"] .b-unclassified { background: #2d3748 !important; color: #9ca3af !important; border-color: #374151 !important; }
[data-theme="dark"] .b-new      { background: #0d2818 !important; color: #86efac !important; border-color: #14532d !important; }
[data-theme="dark"] .b-reviewed { background: #2d2410 !important; color: #fcd34d !important; border-color: #78500a !important; }
[data-theme="dark"] .b-resolved { background: #1a1f2e !important; color: #9ca3af !important; border-color: #374151 !important; }

[data-theme="dark"] .keyword--plain,
[data-theme="dark"] .alert-keyword-tag {
  background: #2d3748 !important;
  color: #cbd5e1 !important;
  border-color: #4b5563 !important;
}
[data-theme="dark"] .keyword--severe {
  background: #3b1010 !important;
  color: #fca5a5 !important;
  border-color: #7f1d1d !important;
}
[data-theme="dark"] .keyword--moderate {
  background: #2d2410 !important;
  color: #fde68a !important;
  border-color: #78500a !important;
}
[data-theme="dark"] .keyword--low {
  background: #0d2818 !important;
  color: #86efac !important;
  border-color: #14532d !important;
}

[data-theme="dark"] .alert-flag-reason {
  background: #2d2410 !important;
  border-color: #78500a !important;
  color: #fcd34d !important;
}
[data-theme="dark"] .alert-flag-reason i { color: #fbbf24 !important; }

[data-theme="dark"] .alert-urgent-warning,
[data-theme="dark"] .severity-lock-notice,
[data-theme="dark"] .urgent-inline {
  background: #2d1010 !important;
  border-color: #7f1d1d !important;
  color: #fca5a5 !important;
}
[data-theme="dark"] .alert-urgent-warning i,
[data-theme="dark"] .severity-lock-notice i,
[data-theme="dark"] .urgent-inline i { color: #f87171 !important; }

[data-theme="dark"] .severity-assign-row { border-top-color: #2d3748 !important; }
[data-theme="dark"] .severity-assign-label { color: #9ca3af !important; }
[data-theme="dark"] .severity-assign-btn {
  background: #1a2030 !important;
  border-color: #374151 !important;
  color: #cbd5e1 !important;
}
[data-theme="dark"] .severity-assign-btn--severe:not(:disabled):hover {
  background: #3b1010 !important;
  border-color: #7f1d1d !important;
  color: #fca5a5 !important;
}
[data-theme="dark"] .severity-assign-btn--moderate:not(:disabled):hover {
  background: #2d2410 !important;
  border-color: #78500a !important;
  color: #fde68a !important;
}
[data-theme="dark"] .severity-assign-btn--low:not(:disabled):hover {
  background: #0d2818 !important;
  border-color: #14532d !important;
  color: #86efac !important;
}
[data-theme="dark"] .severity-assign-btn:disabled {
  background: #161b27 !important;
  border-color: #2d3748 !important;
  color: #4b5563 !important;
}
[data-theme="dark"] .severity-assign-btn--severe.selected {
  background: #b91c1c !important;
  border-color: #b91c1c !important;
  color: #fff !important;
}
[data-theme="dark"] .severity-assign-btn--moderate.selected {
  background: #a16207 !important;
  border-color: #a16207 !important;
  color: #fff !important;
}
[data-theme="dark"] .severity-assign-btn--low.selected {
  background: #15803d !important;
  border-color: #15803d !important;
  color: #fff !important;
}

[data-theme="dark"] .action-btn {
  background: #1a2030 !important;
  border-color: #374151 !important;
  color: #d1d5db !important;
}
[data-theme="dark"] .action-btn:hover:not(:disabled) {
  background: #243044 !important;
  color: #f3f4f6 !important;
}
[data-theme="dark"] .action-btn--email {
  background: #0e6008 !important;
  border-color: #0e6008 !important;
  color: #fff !important;
}
[data-theme="dark"] .action-btn--review {
  background: #1e2d4d !important;
  border-color: #1e3a5f !important;
  color: #93c5fd !important;
}

[data-theme="dark"] .student-alert-badge {
  background: #2d3748 !important;
  color: #9ca3af !important;
  border-color: #374151 !important;
}
[data-theme="dark"] .student-alert-badge.has-severe {
  background: #3b1010 !important;
  color: #fca5a5 !important;
  border-color: #7f1d1d !important;
}
[data-theme="dark"] .student-alert-badge.has-urgent {
  background: #450a0a !important;
  color: #fecaca !important;
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
}
[data-theme="dark"] .history-count--total {
  background: #2d3748 !important;
  color: #9ca3af !important;
  border-color: #374151 !important;
}
[data-theme="dark"] .history-count--severe {
  background: #3b1010 !important;
  color: #fca5a5 !important;
  border-color: #7f1d1d !important;
}
[data-theme="dark"] .history-count--urgent {
  background: #450a0a !important;
  color: #fecaca !important;
  border-color: #ef4444 !important;
}
[data-theme="dark"] .urgent-student-tag {
  background: #3b1010 !important;
  color: #fca5a5 !important;
  border-color: #7f1d1d !important;
}
[data-theme="dark"] .schedule-hint--urgent {
  color: #fca5a5 !important;
}
[data-theme="dark"] .schedule-hint--urgent i {
  color: #f87171 !important;
}
[data-theme="dark"] .alert-row.row-urgent,
[data-theme="dark"] .alert-row.row-urgent:hover {
  background: #2d1010 !important;
}

[data-theme="dark"] .page-btn {
  background: #1e2533 !important;
  border-color: #374151 !important;
  color: #cbd5e1 !important;
}
[data-theme="dark"] .page-btn:hover:not(:disabled) {
  background: #1e3a2e !important;
  border-color: #4ade80 !important;
  color: #4ade80 !important;
}
[data-theme="dark"] .page-btn--active {
  background: #0e6008 !important;
  border-color: #0e6008 !important;
  color: #fff !important;
}
[data-theme="dark"] .page-info { color: #6b7280 !important; }

[data-theme="dark"] .no-alerts,
[data-theme="dark"] .empty-state-filtered {
  background: #1e2533 !important;
  border-color: #374151 !important;
  color: #9ca3af !important;
}
[data-theme="dark"] .empty-icon-wrap-filtered {
  background: #2d3748 !important;
  border-color: #374151 !important;
  color: #6b7280 !important;
}
[data-theme="dark"] .empty-title-filtered { color: #e2e8f0 !important; }
[data-theme="dark"] .empty-subtitle-filtered { color: #6b7280 !important; }

[data-theme="dark"] .reveal-email-btn { color: #6b7280 !important; }
[data-theme="dark"] .reveal-email-btn:hover {
  color: #4ade80 !important;
  background: #0d2818 !important;
}

[data-theme="dark"] .alert-card-actions {
  border-top-color: #2d3748 !important;
}

@keyframes flash-warning-dark {
  0%, 100% { background: #2d1010; border-color: #7f1d1d; }
  50%       { background: #3b1515; border-color: #ef4444; }
}

[data-theme="dark"] .severity-lock-notice,
[data-theme="dark"] .alert-urgent-warning {
  animation-name: flash-warning-dark !important;
}
</style>