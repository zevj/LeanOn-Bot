<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatMessage;
use App\Models\SessionLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Total interactions (all chat messages)
        $totalInteractions = ChatMessage::count();

        // Active users (distinct users with session_logs in last 7 days)
        $activeUsers = SessionLog::where('session_start', '>=', Carbon::now()->subDays(7))
            ->distinct('user_id')
            ->count('user_id');

        // Average session duration in minutes (only closed sessions)
        $avgSession = SessionLog::whereNotNull('session_end')
            ->selectRaw('AVG(ABS(TIMESTAMPDIFF(MINUTE, session_start, session_end))) as avg_minutes')
            ->value('avg_minutes');
        $avgSessionMinutes = round($avgSession ?? 0, 1);

        // Daily interactions for current week (Mon-Sun)
        $startOfWeek = Carbon::now()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);

        $dailyRaw = ChatMessage::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw('DAYOFWEEK(created_at) as dow, COUNT(*) as total')
            ->groupByRaw('DAYOFWEEK(created_at)')
            ->pluck('total', 'dow')
            ->toArray();

        // DAYOFWEEK: 1=Sun,2=Mon,...,7=Sat → remap to Mon-Sun order
        $dailyInteractions = [];
        $dayMap = [2, 3, 4, 5, 6, 7, 1]; // Mon=2, Tue=3, ..., Sun=1
        foreach ($dayMap as $dow) {
            $dailyInteractions[] = $dailyRaw[$dow] ?? 0;
        }

        // Monthly interactions for current year
        $monthlyRaw = ChatMessage::whereYear('created_at', Carbon::now()->year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupByRaw('MONTH(created_at)')
            ->pluck('total', 'month')
            ->toArray();

        $monthlyInteractions = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyInteractions[] = $monthlyRaw[$m] ?? 0;
        }

        return response()->json([
            'total_interactions'    => $totalInteractions,
            'active_users'          => $activeUsers,
            'avg_session_minutes'   => $avgSessionMinutes,
            'daily_interactions'    => $dailyInteractions,
            'monthly_interactions'  => $monthlyInteractions,
        ]);
    }
}
