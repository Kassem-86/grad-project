<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Http\Resources\MessageResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $receiverId;

    /**
     * Create a new event instance.
     */
    function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // determine the receiving user id from conversation
        $conversation = $this->message->conversation ?? null;
        $receiverId = null;
        if ($conversation) {
            $receiverId = $conversation->user1_id === $this->message->sender_id ? $conversation->user2_id : $conversation->user1_id;
        }
        $this->receiverId = $receiverId;

        return [
            new PrivateChannel('chat.' . $receiverId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message_id'   => $this->message->id,
            'sender_id'    => $this->message->sender_id,
            'receiver_id'  => $this->receiverId,
            'message_text' => $this->message->message,
            'created_at'   => $this->message->created_at->toISOString(),
        ];
    }
}
