<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MoodEntry;

class MoodController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'mood' => 'required|string',
        ]);

        $userId = $request->user('sanctum') ? $request->user('sanctum')->id : null;

        MoodEntry::create([
            'user_id' => $userId,
            'mood' => $request->input('mood'),
        ]);

        return response()->json([
            'message' => 'Mood saved successfully'
        ], 201);
    }
}
