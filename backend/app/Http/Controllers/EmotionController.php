<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmotionLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmotionController extends Controller
{
    public function index(Request $request)
    {
        // Top emotions — percentage breakdown of all emotion logs
        $totalLogs = EmotionLog::count();

        $topEmotions = [];
        if ($totalLogs > 0) {
            $topEmotions = EmotionLog::selectRaw('emotion, COUNT(*) as count')
                ->groupBy('emotion')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(function ($row) use ($totalLogs) {
                    return [
                        'name'  => ucfirst($row->emotion),
                        'value' => round(($row->count / $totalLogs) * 100, 1),
                        'count' => $row->count,
                    ];
                });
        }

        // Weekly emotion trends (last 6 weeks)
        $emotionTypes = ['positive', 'sad', 'anxious', 'stressed'];
        $weeklyData = [];
        foreach ($emotionTypes as $emo) {
            $weeklyData[$emo] = [];
        }

        $start = Carbon::now()->subWeeks(5)->startOfWeek();
        $end = Carbon::now()->endOfWeek();
        $driver = DB::connection()->getDriverName();

        switch ($driver) {
            case 'pgsql':
                $dateExpr = "created_at::date";
                break;
            case 'sqlite':
                $dateExpr = "date(created_at)";
                break;
            default:
                $dateExpr = "DATE(created_at)";
                break;
        }

        $emotionCounts = EmotionLog::whereBetween('created_at', [$start, $end])
            ->selectRaw("$dateExpr as date, emotion, COUNT(*) as count")
            ->groupByRaw("$dateExpr, emotion")
            ->get()
            ->groupBy('date')
            ->map(function ($items) {
                return $items->pluck('count', 'emotion')->toArray();
            })
            ->toArray();

        for ($i = 5; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd   = Carbon::now()->subWeeks($i)->endOfWeek();

            $weekCounts = [];
            $currentDay = $weekStart->copy();
            while ($currentDay->lte($weekEnd)) {
                $dayStr = $currentDay->toDateString();
                if (isset($emotionCounts[$dayStr])) {
                    foreach ($emotionCounts[$dayStr] as $emo => $cnt) {
                        $weekCounts[$emo] = ($weekCounts[$emo] ?? 0) + $cnt;
                    }
                }
                $currentDay->addDay();
            }

            $weekTotal = array_sum($weekCounts);

            foreach ($emotionTypes as $emo) {
                $weeklyData[$emo][] = $weekTotal > 0
                    ? round((($weekCounts[$emo] ?? 0) / $weekTotal) * 100, 1)
                    : 0;
            }
        }

        // Referral-style stats
        $referralStats = [
            ['label' => 'Total',    'value' => $totalLogs, 'modifier' => 'total'],
            ['label' => 'This Week','value' => EmotionLog::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(), 'modifier' => 'accepted'],
            ['label' => 'This Month','value' => EmotionLog::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count(), 'modifier' => 'pending'],
        ];

        return response()->json([
            'top_emotions'   => $topEmotions,
            'weekly_data'    => $weeklyData,
            'week_labels'    => ['W1', 'W2', 'W3', 'W4', 'W5', 'W6'],
            'referral_stats' => $referralStats,
        ]);
    }
}
