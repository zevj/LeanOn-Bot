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
        $driver = DB::connection()->getDriverName();

        // Total interactions (all chat messages)
        $totalInteractions = ChatMessage::count();

        // Active users (distinct users with session_logs in last 7 days)
        $activeUsers = SessionLog::where('session_start', '>=', Carbon::now()->subDays(7))
            ->distinct('user_id')
            ->count('user_id');

        // Average session duration in minutes (only closed sessions)
        $avgSessionQuery = SessionLog::whereNotNull('session_end');
        
        switch ($driver) {
            case 'pgsql':
                $avgSessionQuery->selectRaw('AVG(ABS(EXTRACT(EPOCH FROM (session_end - session_start)) / 60)) as avg_minutes');
                break;
            case 'sqlite':
                $avgSessionQuery->selectRaw('AVG(ABS((julianday(session_end) - julianday(session_start)) * 1440)) as avg_minutes');
                break;
            case 'mysql':
            default:
                $avgSessionQuery->selectRaw('AVG(ABS(TIMESTAMPDIFF(MINUTE, session_start, session_end))) as avg_minutes');
                break;
        }
        
        $avgSession = $avgSessionQuery->value('avg_minutes');
        $avgSessionMinutes = round($avgSession ?? 0, 1);

        // Daily interactions for current week (Mon-Sun)
        $startOfWeek = Carbon::now()->startOfWeek(1); // Monday
        $endOfWeek = Carbon::now()->endOfWeek(7);    // Sunday

        $dailyQuery = ChatMessage::whereBetween('created_at', [$startOfWeek, $endOfWeek]);

        switch ($driver) {
            case 'pgsql':
                $dailyQuery->selectRaw('EXTRACT(DOW FROM created_at) + 1 as dow, COUNT(*) as total');
                break;
            case 'sqlite':
                $dailyQuery->selectRaw('CAST(strftime(\'%w\', created_at) AS INTEGER) + 1 as dow, COUNT(*) as total');
                break;
            case 'mysql':
            default:
                $dailyQuery->selectRaw('DAYOFWEEK(created_at) as dow, COUNT(*) as total');
                break;
        }

        $dailyRaw = $dailyQuery->groupByRaw('dow')
            ->pluck('total', 'dow')
            ->toArray();

        // DAYOFWEEK: 1=Sun,2=Mon,...,7=Sat → remap to Mon-Sun order
        $dailyInteractions = [];
        $dayMap = [2, 3, 4, 5, 6, 7, 1]; // Mon=2, Tue=3, ..., Sun=1
        foreach ($dayMap as $dow) {
            $dailyInteractions[] = $dailyRaw[$dow] ?? 0;
        }

        // Monthly interactions for current year
        $monthlyQuery = ChatMessage::whereYear('created_at', Carbon::now()->year);
        
        switch ($driver) {
            case 'pgsql':
                $monthlyQuery->selectRaw('EXTRACT(MONTH FROM created_at) as month, COUNT(*) as total');
                break;
            case 'sqlite':
                $monthlyQuery->selectRaw('CAST(strftime(\'%m\', created_at) AS INTEGER) as month, COUNT(*) as total');
                break;
            case 'mysql':
            default:
                $monthlyQuery->selectRaw('MONTH(created_at) as month, COUNT(*) as total');
                break;
        }

        $monthlyRaw = $monthlyQuery->groupByRaw('month')
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
