<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CrisisAlert;

class CrisisAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = CrisisAlert::with(['user:id,first_name,last_name,email']);

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $alerts = $query->orderByDesc('created_at')->paginate(20);

        // Severity counts
        $stats = CrisisAlert::selectRaw("
            SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_count,
            SUM(CASE WHEN severity = 'severe' THEN 1 ELSE 0 END) as severe_count,
            SUM(CASE WHEN severity = 'moderate' THEN 1 ELSE 0 END) as moderate_count,
            SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low_count
        ")->first();

        // Transform alerts to include masked email and user display name
        $alerts->getCollection()->transform(function ($alert) {
            $data = $alert->toArray();
            if ($alert->user) {
                $data['masked_email'] = \App\Helpers\DataFormatter::maskEmail($alert->user->email);
                $data['user_display'] = 'Anonymous #' . ($alert->id + 1000);
            } else {
                $data['masked_email'] = 'Anonymous';
                $data['user_display'] = 'Anonymous #' . ($alert->id + 1000);
            }
            unset($data['user']); // Remove raw user data
            return $data;
        });

        return response()->json([
            'stats'  => $stats,
            'alerts' => $alerts,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:new,reviewed,resolved',
        ]);

        $alert = CrisisAlert::findOrFail($id);
        $alert->update(['status' => $request->status]);

        return response()->json(['message' => 'Alert updated', 'alert' => $alert]);
    }
}
