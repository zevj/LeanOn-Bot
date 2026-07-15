<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CrisisAlert;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrisisAlertController extends Controller
{
    /**
     * GET /api/admin/crisis-alerts
     *
     * Returns paginated crisis alerts split into:
     *   - unclassified: is_classified = false (awaiting admin review)
     *   - classified:   is_classified = true  (admin has assigned severity)
     *
     * Filters: severity, status
     * All user data is anonymized — no names or emails are exposed.
     */
    public function index(Request $request)
    {
        // ── Unclassified alerts (awaiting admin severity assignment) ──
        $unclassifiedQuery = CrisisAlert::with(['user:id,first_name,last_name,email'])
            ->where('is_classified', false);

        $unclassified = $unclassifiedQuery
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'unclassified_page');

        // ── Classified alerts (admin has assigned severity) ──
        $classifiedQuery = CrisisAlert::with(['user:id,first_name,last_name,email'])
            ->where('is_classified', true);

        if ($request->filled('severity')) {
            $classifiedQuery->where('severity', $request->severity);
        }
        if ($request->filled('status')) {
            $classifiedQuery->where('status', $request->status);
        }

        $classified = $classifiedQuery
            ->orderByRaw("CASE severity WHEN 'severe' THEN 1 WHEN 'moderate' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'classified_page');

        // ── Severity counts (classified only) ──
        $stats = CrisisAlert::where('is_classified', true)
            ->selectRaw("
                SUM(CASE WHEN severity = 'severe'   THEN 1 ELSE 0 END) as severe_count,
                SUM(CASE WHEN severity = 'moderate' THEN 1 ELSE 0 END) as moderate_count,
                SUM(CASE WHEN severity = 'low'      THEN 1 ELSE 0 END) as low_count,
                COUNT(*) as total_classified
            ")
            ->first();

        $unclassifiedCount = CrisisAlert::where('is_classified', false)->count();

        // ── Mask both collections ──
        $userIds = collect($unclassified->items())->pluck('user_id')
            ->merge(collect($classified->items())->pluck('user_id'))
            ->filter()
            ->unique();

        $userCounts = CrisisAlert::whereIn('user_id', $userIds)
            ->selectRaw('user_id, COUNT(*) as total_count, SUM(CASE WHEN severity = "severe" THEN 1 ELSE 0 END) as severe_count')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $anonymize = function ($alert) use ($userCounts) {
            $data = $alert->toArray();
            
            $userId = $alert->user_id;
            $countInfo = $userId ? $userCounts->get($userId) : null;
            
            $data['total_alerts_count'] = $countInfo ? (int) $countInfo->total_count : 0;
            $data['severe_alerts_count'] = $countInfo ? (int) $countInfo->severe_count : 0;

            if ($alert->user) {
                $data['masked_email']  = \App\Helpers\DataFormatter::maskEmail($alert->user->email);
                $data['real_email']    = $alert->user->email;
                $data['user_display']  = 'Flagged #' . ($alert->id + 1000);
            } else {
                $data['masked_email']  = 'Flagged';
                $data['real_email']    = null;
                $data['user_display']  = 'Flagged #' . ($alert->id + 1000);
            }
            unset($data['user']);
            return $data;
        };

        $unclassified->getCollection()->transform($anonymize);
        $classified->getCollection()->transform($anonymize);

        return response()->json([
            'stats' => array_merge($stats->toArray(), [
                'unclassified_count' => $unclassifiedCount,
            ]),
            'unclassified' => $unclassified,
            'alerts'       => $classified,   // keep key 'alerts' for backward compat
        ]);
    }

    /**
     * PATCH /api/admin/crisis-alerts/{id}
     *
     * Admin can update:
     *   - status:   new | reviewed | resolved
     *   - severity: severe | moderate | low  (manual classification)
     *
     * When severity is set, is_classified is automatically set to true.
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'status'   => 'sometimes|in:new,reviewed,resolved',
            'severity' => 'sometimes|nullable|in:severe,moderate,low',
            'appointment_date' => 'sometimes|nullable|date',
            'appointment_time' => 'sometimes|nullable|string',
        ]);

        $alert = CrisisAlert::findOrFail($id);

        $updates = [];

        if ($request->has('status')) {
            $updates['status'] = $request->status;
        }

        if ($request->has('severity')) {
            $updates['severity']      = $request->severity;
            $updates['is_classified'] = !is_null($request->severity);
        }

        if ($request->has('appointment_date') || $request->has('appointment_time')) {
            $newDate = $request->input('appointment_date');
            $newTime = $request->input('appointment_time');

            if (is_null($newDate) && is_null($newTime)) {
                $updates['appointment_date'] = null;
                $updates['appointment_time'] = null;
                $updates['appointment_status'] = null;
            } else {
                $wasScheduled = !is_null($alert->appointment_date);
                $statusType = $wasScheduled ? 'rescheduled' : 'scheduled';

                $updates['appointment_date'] = $newDate;
                $updates['appointment_time'] = $newTime;
                $updates['appointment_status'] = $statusType;
                $updates['admin_email_sent_at'] = now();
                $updates['admin_email_notified'] = false;

                if ($wasScheduled) {
                    $alert->load('user');
                    if ($alert->user && $alert->user->email) {
                        $studentEmail = $alert->user->email;
                        $subject = 'Updated: Your Wellness Appointment has been Rescheduled';
                        $fromEmail = config('mail.from.address', env('MAIL_FROM_ADDRESS'));
                        $fromName  = config('mail.from.name',    env('MAIL_FROM_NAME', 'LeanOn Bot Support'));
                        $apiKey = env('BREVO_API_KEY');

                        if ($apiKey) {
                            $appointmentFormatted = \Carbon\Carbon::parse("{$newDate} {$newTime}")
                                ->format('F j, Y \a\t g:i A');

                            $htmlBody = \Illuminate\Support\Facades\View::make('emails.appointment_rescheduled', [
                                'appointmentFormatted' => $appointmentFormatted,
                            ])->render();

                            try {
                                Http::withHeaders([
                                    'api-key'      => $apiKey,
                                    'Content-Type' => 'application/json',
                                    'Accept'       => 'application/json',
                                ])->post('https://api.brevo.com/v3/smtp/email', [
                                    'sender'      => ['name' => $fromName, 'email' => $fromEmail],
                                    'to'          => [['email' => $studentEmail]],
                                    'subject'     => $subject,
                                    'htmlContent' => $htmlBody,
                                ]);
                                Log::info("Crisis alert reschedule email sent via Brevo to: {$studentEmail}");
                            } catch (\Exception $e) {
                                Log::error('Crisis alert reschedule email failed: ' . $e->getMessage());
                            }
                        } else {
                            Log::error('Rescheduling email failed: BREVO_API_KEY is not configured.');
                        }
                    }
                }
            }
        }

        $alert->update($updates);

        if ($request->has('severity') && $request->severity === 'severe') {
            $userId = $alert->user_id;
            if ($userId) {
                $severeCount = CrisisAlert::where('user_id', $userId)
                    ->where('severity', 'severe')
                    ->count();

                if ($severeCount >= 2) {
                    $user = User::find($userId);
                    if ($user) {
                        $notification = \App\Models\AdminNotification::where('type', 'multiple_severe_alerts')
                            ->where('meta->user_id', $userId)
                            ->first();

                        if (!$notification) {
                            \App\Models\AdminNotification::urgentHelpNeeded($user, $severeCount);
                        } else {
                            $maskedEmail = \App\Helpers\DataFormatter::maskEmail($user->email);
                            $notification->update([
                                'message' => "Student ({$maskedEmail}) has accumulated {$severeCount} severe crisis alerts.",
                                'meta'    => [
                                    'user_id'      => $userId,
                                    'severe_count' => $severeCount,
                                ],
                                'is_read' => false,
                            ]);
                        }
                    }
                }
            }
        }

        // Bust analytics cache so dashboard reflects new classification
        Cache::forget('analytics:dashboard:1d');
        Cache::forget('analytics:dashboard:7d');
        Cache::forget('analytics:dashboard:14d');
        Cache::forget('analytics:dashboard:30d');
        Cache::forget('analytics:dashboard:90d');
        Cache::forget('analytics:crisis_dept_stats');

        return response()->json(['message' => 'Alert updated', 'alert' => $alert]);
    }

    /**
     * POST /api/admin/crisis-alerts/{id}/send-email
     *
     * Sends a crisis support email to the student's actual email address.
     * The email address is never exposed to the frontend — the backend
     * resolves it from the alert's user_id.
     */
    public function sendEmail(Request $request, int $id)
    {
        $request->validate([
            'subject'          => 'sometimes|string|max:200',
            'body'             => 'sometimes|string|max:5000',
            'appointment_date' => 'sometimes|nullable|date',
            'appointment_time' => 'sometimes|nullable|string',
        ]);

        $alert = CrisisAlert::with('user:id,email,first_name')->findOrFail($id);

        if (!$alert->user || !$alert->user->email) {
            return response()->json(['message' => 'No email address found for this student.'], 422);
        }

        $studentEmail = $alert->user->email;
        $subject = $request->input('subject', 'Important: Wellness Support from LeanOn Bot');
        $body    = $request->input('body', '');

        $appointmentDate = $request->input('appointment_date');
        $appointmentTime = $request->input('appointment_time');

        $apiKey = env('BREVO_API_KEY');

        if (!$apiKey) {
            Log::error('Crisis alert email failed: BREVO_API_KEY is not configured.');
            return response()->json(['message' => 'Email service is not configured. Please contact the administrator.'], 500);
        }

        $fromEmail = config('mail.from.address', env('MAIL_FROM_ADDRESS'));
        $fromName  = config('mail.from.name',    env('MAIL_FROM_NAME', 'LeanOn Bot Support'));

        // Format appointment string for the template
        $appointmentFormatted = null;
        if ($appointmentDate && $appointmentTime) {
            $appointmentFormatted = \Carbon\Carbon::parse("{$appointmentDate} {$appointmentTime}")
                ->format('F j, Y \a\t g:i A');
        }

        // Split body into paragraphs for the template
        $paragraphs = array_filter(
            explode("\n", $body),
            fn($line) => trim($line) !== ''
        );

        // Render the styled HTML email template
        $htmlBody = \Illuminate\Support\Facades\View::make('emails.crisis_alert', [
            'paragraphs'           => array_values($paragraphs),
            'severity'             => $alert->severity,
            'appointmentFormatted' => $appointmentFormatted,
        ])->render();

        try {
            $response = Http::withHeaders([
                'api-key'      => $apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender'      => ['name' => $fromName, 'email' => $fromEmail],
                'to'          => [['email' => $studentEmail]],
                'subject'     => $subject,
                'htmlContent' => $htmlBody,
            ]);

            if ($response->successful()) {
                Log::info("Crisis alert email sent via Brevo to: {$studentEmail}");

                // Stamp the alert so the student's chat can show a notification
                $alert->update([
                    'admin_email_sent_at' => now(),
                    'appointment_date' => $appointmentDate,
                    'appointment_time' => $appointmentTime,
                    'appointment_status' => ($appointmentDate && $appointmentTime) ? 'scheduled' : null,
                ]);

                return response()->json(['message' => 'Email sent successfully.']);
            }

            Log::error('Crisis alert email failed (Brevo): ' . $response->body());
            return response()->json(['message' => 'Failed to send email. Please try again.'], 500);

        } catch (\Exception $e) {
            Log::error('Crisis alert email exception: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send email. Please try again.'], 500);
        }
    }

    /**
     * GET /api/admin/crisis-alerts/department-stats
     *
     * Returns anonymized department-level crisis alert statistics.
     * Used by the analytics dashboard cards.
     */
    public function departmentStats()
    {
        $cacheKey = 'analytics:crisis_dept_stats';

        $data = Cache::remember($cacheKey, 3600, function () {
            // Department with most crisis alerts (classified only)
            $topDeptAlerts = CrisisAlert::where('is_classified', true)
                ->whereNotNull('department')
                ->selectRaw('department, COUNT(*) as count')
                ->groupBy('department')
                ->orderByDesc('count')
                ->first();

            // Department with most users
            $topDeptUsers = User::where('role', 'student')
                ->whereNotNull('department')
                ->selectRaw('department, COUNT(*) as count')
                ->groupBy('department')
                ->orderByDesc('count')
                ->first();

            // Gender that uses the system most (by conversation count via users)
            $topGender = User::where('role', 'student')
                ->whereNotNull('gender')
                ->selectRaw('gender, COUNT(*) as count')
                ->groupBy('gender')
                ->orderByDesc('count')
                ->first();

            // All departments with user counts (for breakdown)
            $deptBreakdown = User::where('role', 'student')
                ->whereNotNull('department')
                ->selectRaw('department, COUNT(*) as count')
                ->groupBy('department')
                ->orderByDesc('count')
                ->get()
                ->map(fn($r) => ['department' => $r->department, 'count' => (int) $r->count])
                ->toArray();

            // All departments with alert counts
            $deptAlertBreakdown = CrisisAlert::where('is_classified', true)
                ->whereNotNull('department')
                ->selectRaw('department, COUNT(*) as count')
                ->groupBy('department')
                ->orderByDesc('count')
                ->get()
                ->map(fn($r) => ['department' => $r->department, 'count' => (int) $r->count])
                ->toArray();

            // Gender breakdown
            $genderBreakdown = User::where('role', 'student')
                ->whereNotNull('gender')
                ->selectRaw('gender, COUNT(*) as count')
                ->groupBy('gender')
                ->orderByDesc('count')
                ->get()
                ->map(fn($r) => ['gender' => $r->gender, 'count' => (int) $r->count])
                ->toArray();

            return [
                'top_department_users'  => $topDeptUsers?->department  ?? 'N/A',
                'top_department_alerts' => $topDeptAlerts?->department ?? 'N/A',
                'top_gender'            => $topGender?->gender          ?? 'N/A',
                'dept_user_breakdown'   => $deptBreakdown,
                'dept_alert_breakdown'  => $deptAlertBreakdown,
                'gender_breakdown'      => $genderBreakdown,
            ];
        });

        return response()->json($data);
    }

    /**
     * GET /api/admin/appointments
     *
     * Returns all crisis alerts that have scheduled appointments (where appointment_date is not null).
     */
    public function appointments()
    {
        $appointments = CrisisAlert::with(['user:id,first_name,last_name,email'])
            ->whereNotNull('appointment_date')
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        $anonymize = function ($alert) {
            $data = $alert->toArray();
            if ($alert->user) {
                $data['masked_email']  = \App\Helpers\DataFormatter::maskEmail($alert->user->email);
                $data['real_email']    = $alert->user->email;
                $data['student_name']  = trim(($alert->user->first_name ?? '') . ' ' . ($alert->user->last_name ?? ''));
                $data['user_display']  = 'Flagged #' . ($alert->id + 1000);
            } else {
                $data['masked_email']  = 'Flagged';
                $data['real_email']    = null;
                $data['student_name']  = 'Flagged Student';
                $data['user_display']  = 'Flagged #' . ($alert->id + 1000);
            }
            unset($data['user']);
            return $data;
        };

        $appointments->transform($anonymize);

        return response()->json($appointments);
    }
}
