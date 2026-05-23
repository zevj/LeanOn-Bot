<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SessionLog;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = SessionLog::with(['user:id,first_name,last_name,email,department,program,role'])
            ->whereHas('user', function ($q) {
                $q->where('role', 'student');
            });

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%");
            })->orWhere('id', 'like', "%{$search}%");
        }

        // Department filter
        if ($request->filled('department')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        // Status filter (active = no session_end, closed = has session_end)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNull('session_end');
            } else if ($request->status === 'closed') {
                $query->whereNotNull('session_end');
            }
        }

        $logs = $query->orderByDesc('session_start')->paginate($request->input('per_page', 20));

        // Get all departments for filter dropdown (fixed list per requirements)
        $departments = collect(['CCS', 'CEAS', 'CHTM', 'CAHS', 'CBA']);

        // Summary stats
        $totalLogs      = SessionLog::whereHas('user', function ($q) { $q->where('role', 'student'); })->count();
        $activeSessions = SessionLog::whereHas('user', function ($q) { $q->where('role', 'student'); })->whereNull('session_end')->count();
        $closedSessions = SessionLog::whereHas('user', function ($q) { $q->where('role', 'student'); })->whereNotNull('session_end')->count();

        // Transform logs to include user info with masked email
        $logs->getCollection()->transform(function ($log) {
            $data = [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'session_start' => $log->session_start,
                'session_end' => $log->session_end,
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,
            ];
            if ($log->user) {
                $data['masked_email'] = \App\Helpers\DataFormatter::maskEmail($log->user->email);
                $data['real_email']   = $log->user->email; // Only revealed on admin toggle
                $data['user_name']    = 'Anonymous #' . ($log->user->id + 1000); // Anonymized name
                $data['department']   = $log->user->department ?? '—';
                $data['program']      = \App\Helpers\DataFormatter::abbreviateProgram($log->user->program);
            }
            return $data;
        });

        return response()->json([
            'logs'            => $logs,
            'departments'     => $departments,
            'total_logs'      => $totalLogs,
            'active_sessions' => $activeSessions,
            'closed_sessions' => $closedSessions,
        ]);
    }
}
