<?php

namespace App\Http\Controllers;

use App\Events\DirectMessageSent;
use App\Models\DirectConversation;
use App\Models\DirectMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DirectMessageController extends Controller
{
    /**
     * Get all direct conversations for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isStaff = in_array($user->role, ['guidance', 'admin']);

        $query = DirectConversation::with(['admin', 'student']);

        if ($isStaff) {
            $query->where('admin_id', $user->id);
        } else {
            $query->where('student_id', $user->id);
        }

        $conversations = $query->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        $formatted = $conversations->map(function ($convo) use ($user, $isStaff) {
            $otherParticipant = $isStaff ? $convo->student : $convo->admin;
            $unreadCount = $isStaff ? $convo->admin_unread_count : $convo->student_unread_count;

            return [
                'id'                   => $convo->id,
                'admin_id'             => $convo->admin_id,
                'student_id'           => $convo->student_id,
                'last_message'         => $convo->last_message,
                'last_message_at'      => $convo->last_message_at ? $convo->last_message_at->toIso8601String() : null,
                'unread_count'         => $unreadCount,
                'created_at'           => $convo->created_at ? $convo->created_at->toIso8601String() : null,
                'other_participant'    => $otherParticipant ? [
                    'id'                => $otherParticipant->id,
                    'first_name'        => $otherParticipant->first_name,
                    'last_name'         => $otherParticipant->last_name,
                    'full_name'         => trim(($otherParticipant->first_name ?? '') . ' ' . ($otherParticipant->last_name ?? '')),
                    'email'             => $otherParticipant->email,
                    'role'              => $otherParticipant->role,
                    'department'        => $otherParticipant->department,
                    'profile_image_url' => $otherParticipant->profile_image_url,
                ] : null,
            ];
        });

        $totalUnread = $isStaff
            ? DirectConversation::where('admin_id', $user->id)->sum('admin_unread_count')
            : DirectConversation::where('student_id', $user->id)->sum('student_unread_count');

        return response()->json([
            'conversations' => $formatted,
            'total_unread'  => (int) $totalUnread,
        ]);
    }

    /**
     * Start or retrieve an existing conversation with a student (or staff).
     */
    public function startConversation(Request $request)
    {
        $user = $request->user();
        $isStaff = in_array($user->role, ['guidance', 'admin']);

        if ($isStaff) {
            $request->validate([
                'student_id' => 'required|exists:users,id',
            ]);
            $adminId = $user->id;
            $studentId = (int) $request->student_id;
        } else {
            // Student starting conversation with guidance officer
            $adminId = $request->admin_id;
            if (!$adminId) {
                // Default to first available guidance officer
                $guidanceUser = User::whereIn('role', ['guidance', 'admin'])->first();
                if (!$guidanceUser) {
                    return response()->json(['message' => 'No guidance counselor available.'], 404);
                }
                $adminId = $guidanceUser->id;
            }
            $studentId = $user->id;
        }

        // Prevent duplicate conversations between the same admin and student
        $conversation = DirectConversation::where('admin_id', $adminId)
            ->where('student_id', $studentId)
            ->first();

        if (!$conversation) {
            $conversation = DirectConversation::create([
                'admin_id'             => $adminId,
                'student_id'           => $studentId,
                'admin_unread_count'   => 0,
                'student_unread_count' => 0,
            ]);
        }

        $conversation->load(['admin', 'student']);
        $otherParticipant = $isStaff ? $conversation->student : $conversation->admin;

        return response()->json([
            'id'                => $conversation->id,
            'admin_id'          => $conversation->admin_id,
            'student_id'        => $conversation->student_id,
            'last_message'      => $conversation->last_message,
            'last_message_at'   => $conversation->last_message_at ? $conversation->last_message_at->toIso8601String() : null,
            'unread_count'      => $isStaff ? $conversation->admin_unread_count : $conversation->student_unread_count,
            'other_participant' => $otherParticipant ? [
                'id'                => $otherParticipant->id,
                'first_name'        => $otherParticipant->first_name,
                'last_name'         => $otherParticipant->last_name,
                'full_name'         => trim(($otherParticipant->first_name ?? '') . ' ' . ($otherParticipant->last_name ?? '')),
                'email'             => $otherParticipant->email,
                'role'              => $otherParticipant->role,
                'department'        => $otherParticipant->department,
                'profile_image_url' => $otherParticipant->profile_image_url,
            ] : null,
        ]);
    }

    /**
     * Get messages for a specific conversation.
     */
    public function messages(Request $request, int $id)
    {
        $user = $request->user();
        $conversation = DirectConversation::findOrFail($id);

        // Authorization check
        if ((int) $conversation->admin_id !== (int) $user->id && (int) $conversation->student_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized conversation access.'], 403);
        }

        // Mark unread messages sent to current user as read
        DirectMessage::where('conversation_id', $conversation->id)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);

        // Reset user's unread counter
        if ((int) $conversation->admin_id === (int) $user->id) {
            $conversation->update(['admin_unread_count' => 0]);
        } else {
            $conversation->update(['student_unread_count' => 0]);
        }

        $messages = DirectMessage::with(['sender:id,first_name,last_name,role,profile_image'])
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id'              => $msg->id,
                    'conversation_id' => $msg->conversation_id,
                    'sender_id'       => $msg->sender_id,
                    'receiver_id'     => $msg->receiver_id,
                    'message'         => $msg->message,
                    'is_read'         => $msg->is_read,
                    'created_at'      => $msg->created_at ? $msg->created_at->toIso8601String() : null,
                    'sender'          => [
                        'id'                => $msg->sender->id ?? null,
                        'first_name'        => $msg->sender->first_name ?? '',
                        'last_name'         => $msg->sender->last_name ?? '',
                        'role'              => $msg->sender->role ?? '',
                        'profile_image_url' => $msg->sender->profile_image_url ?? null,
                    ],
                ];
            });

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages'        => $messages,
        ]);
    }

    /**
     * Send a direct message in a conversation.
     */
    public function storeMessage(Request $request, int $id)
    {
        $user = $request->user();
        $conversation = DirectConversation::findOrFail($id);

        if ((int) $conversation->admin_id !== (int) $user->id && (int) $conversation->student_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized conversation access.'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:3000',
        ]);

        $receiverId = (int) $user->id === (int) $conversation->admin_id
            ? $conversation->student_id
            : $conversation->admin_id;

        $message = DirectMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'receiver_id'     => $receiverId,
            'message'         => trim($validated['message']),
            'is_read'         => false,
        ]);

        // Update conversation last message & unread counter
        $now = Carbon::now();
        if ((int) $user->id === (int) $conversation->admin_id) {
            $conversation->increment('student_unread_count');
        } else {
            $conversation->increment('admin_unread_count');
        }

        $conversation->update([
            'last_message'    => trim($validated['message']),
            'last_message_at' => $now,
        ]);

        // Broadcast event via Reverb / Laravel Echo
        try {
            event(new DirectMessageSent($message));
        } catch (\Throwable $e) {
            // Log error silently if Reverb server is offline; fallback handles polling
            \Illuminate\Support\Facades\Log::warning('Broadcasting DirectMessageSent failed: ' . $e->getMessage());
        }

        $message->load(['sender:id,first_name,last_name,role,profile_image']);

        return response()->json([
            'id'              => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id'       => $message->sender_id,
            'receiver_id'     => $message->receiver_id,
            'message'         => $message->message,
            'is_read'         => $message->is_read,
            'created_at'      => $message->created_at ? $message->created_at->toIso8601String() : null,
            'sender'          => [
                'id'                => $message->sender->id ?? null,
                'first_name'        => $message->sender->first_name ?? '',
                'last_name'         => $message->sender->last_name ?? '',
                'role'              => $message->sender->role ?? '',
                'profile_image_url' => $message->sender->profile_image_url ?? null,
            ],
        ], 201);
    }

    /**
     * Mark conversation messages as read.
     */
    public function markRead(Request $request, int $id)
    {
        $user = $request->user();
        $conversation = DirectConversation::findOrFail($id);

        if ((int) $conversation->admin_id !== (int) $user->id && (int) $conversation->student_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized conversation access.'], 403);
        }

        DirectMessage::where('conversation_id', $conversation->id)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now(),
            ]);

        if ((int) $conversation->admin_id === (int) $user->id) {
            $conversation->update(['admin_unread_count' => 0]);
        } else {
            $conversation->update(['student_unread_count' => 0]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Search students for starting a direct message.
     */
    public function searchStudents(Request $request)
    {
        $admin = $request->user();
        $q = trim($request->input('q', ''));

        $query = User::where('role', 'student');

        if ($q !== '') {
            $domainQuery = $q;
            if (str_contains($q, '@')) {
                $parts = explode('@', $q);
                $domainQuery = end($parts);
            }

            $query->where(function ($w) use ($q, $domainQuery) {
                $w->where('first_name', 'like', "%{$q}%")
                  ->orWhere('last_name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%@{$domainQuery}%")
                  ->orWhere('department', 'like', "%{$q}%")
                  ->orWhere('program', 'like', "%{$q}%");

                $driver = DB::connection()->getDriverName();
                if ($driver === 'sqlite') {
                    $w->orWhereRaw("(COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')) LIKE ?", ["%{$q}%"]);
                } else {
                    $w->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$q}%"]);
                }

                if (is_numeric($q)) {
                    $w->orWhere('id', (int) $q);
                }
            });
        }

        $students = $query->orderBy('first_name')->limit(30)->get();

        $studentIds = $students->pluck('id')->toArray();
        $existingConversations = $admin ? DirectConversation::where('admin_id', $admin->id)
            ->whereIn('student_id', $studentIds)
            ->pluck('id', 'student_id')
            ->toArray() : [];

        $result = $students->map(function ($s) use ($existingConversations) {
            return [
                'id'                => $s->id,
                'first_name'        => $s->first_name,
                'last_name'         => $s->last_name,
                'full_name'         => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                'email'             => $s->email,
                'department'        => $s->department,
                'program'           => $s->program,
                'profile_image_url' => $s->profile_image_url,
                'conversation_id'   => $existingConversations[$s->id] ?? null,
            ];
        });

        return response()->json(['students' => $result]);
    }
}
