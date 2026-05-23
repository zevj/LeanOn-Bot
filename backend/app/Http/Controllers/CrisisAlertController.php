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

        // ── Anonymize both collections ──
        $anonymize = function ($alert) {
            $data = $alert->toArray();
            if ($alert->user) {
                $data['masked_email']  = \App\Helpers\DataFormatter::maskEmail($alert->user->email);
                $data['user_display']  = 'Anonymous #' . ($alert->id + 1000);
            } else {
                $data['masked_email']  = 'Anonymous';
                $data['user_display']  = 'Anonymous #' . ($alert->id + 1000);
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

        $alert->update($updates);

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

        // Append appointment info if provided
        $appointmentDate = $request->input('appointment_date');
        $appointmentTime = $request->input('appointment_time');
        if ($appointmentDate && $appointmentTime) {
            $formatted = \Carbon\Carbon::parse("{$appointmentDate} {$appointmentTime}")
                ->format('F j, Y \a\t g:i A');
            $body .= "\n\nAppointment scheduled: {$formatted}";
        }

        $apiKey = env('BREVO_API_KEY');

        if (!$apiKey) {
            Log::error('Crisis alert email failed: BREVO_API_KEY is not configured.');
            return response()->json(['message' => 'Email service is not configured. Please contact the administrator.'], 500);
        }

        $fromEmail = config('mail.from.address', env('MAIL_FROM_ADDRESS'));
        $fromName  = config('mail.from.name',    env('MAIL_FROM_NAME', 'LeanOn Bot Support'));

        // Convert plain-text body to simple HTML (preserve line breaks)
        $htmlBody = nl2br(e($body));

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
}
