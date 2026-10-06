<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\DirectConversation;

Broadcast::channel('direct-chat.{conversationId}', function ($user, $conversationId) {
    $conversation = DirectConversation::find($conversationId);
    if (!$conversation) {
        return false;
    }
    return (int) $user->id === (int) $conversation->admin_id || (int) $user->id === (int) $conversation->student_id;
});

Broadcast::channel('user-direct-messages.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
