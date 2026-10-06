<?php

namespace App\Events;

use App\Models\DirectMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DirectMessage $message;

    /**
     * Create a new event instance.
     */
    public function __construct(DirectMessage $message)
    {
        $this->message = $message->load(['sender:id,first_name,last_name,email,role,profile_image']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('direct-chat.' . $this->message->conversation_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'DirectMessageSent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id'              => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id'       => $this->message->sender_id,
            'receiver_id'     => $this->message->receiver_id,
            'message'         => $this->message->message,
            'is_read'         => $this->message->is_read,
            'created_at'      => $this->message->created_at ? $this->message->created_at->toIso8601String() : null,
            'sender'          => [
                'id'                => $this->message->sender->id ?? null,
                'first_name'        => $this->message->sender->first_name ?? '',
                'last_name'         => $this->message->sender->last_name ?? '',
                'role'              => $this->message->sender->role ?? '',
                'profile_image_url' => $this->message->sender->profile_image_url ?? null,
            ],
        ];
    }
}
