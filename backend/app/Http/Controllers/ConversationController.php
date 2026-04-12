<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user('sanctum') ? $request->user('sanctum')->id : null;
        if (!$userId) return response()->json([]);

        $conversations = \App\Models\Conversation::where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->get();
        return response()->json($conversations);
    }

    public function store(Request $request)
    {
        $userId = $request->user('sanctum') ? $request->user('sanctum')->id : null;
        $email = $request->user('sanctum') ? $request->user('sanctum')->email : null;
        if (!$userId) return response()->json(['error' => 'Unauthenticated'], 401);

        $conversation = \App\Models\Conversation::create([
            'user_id' => $userId,
            'email' => $email,
            'title' => 'New Chat',
            'last_message' => 'No messages yet',
            'is_saved' => false
        ]);

        return response()->json($conversation, 201);
    }

    public function update(Request $request, $id)
    {
        $userId = $request->user('sanctum') ? $request->user('sanctum')->id : null;
        if (!$userId) return response()->json(['error' => 'Unauthenticated'], 401);

        $conversation = \App\Models\Conversation::where('id', $id)
            ->where('user_id', $userId)->first();
        if (!$conversation) return response()->json(['error' => 'Not found'], 404);

        if ($request->has('title')) $conversation->title = $request->title;
        if ($request->has('is_saved')) $conversation->is_saved = $request->is_saved;

        $conversation->save();
        return response()->json($conversation);
    }

    public function destroy(Request $request, $id)
    {
        $userId = $request->user('sanctum') ? $request->user('sanctum')->id : null;
        if (!$userId) return response()->json(['error' => 'Unauthenticated'], 401);

        $conversation = \App\Models\Conversation::where('id', $id)
            ->where('user_id', $userId)->first();
        if (!$conversation) return response()->json(['error' => 'Not found'], 404);

        $conversation->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
