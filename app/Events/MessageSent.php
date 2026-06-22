<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
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
    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
        
        // تحديد الـ receiverId لاستخدامه داخل البيانات (اختياري)
        $conversation = $this->message->conversation;
        $this->receiverId = $conversation 
            ? ($conversation->user1_id === $this->message->sender_id ? $conversation->user2_id : $conversation->user1_id)
            : null;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // القناة أصبحت تعتمد على الـ conversation_id
        return [
            new PrivateChannel('chat.' . $this->message->conversation_id),
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
        $createdAt = $this->message->created_at instanceof \Carbon\Carbon 
                     ? $this->message->created_at 
                     : \Carbon\Carbon::parse($this->message->created_at);

        return [
            'message_id'      => $this->message->id,
            'conversation_id' => $this->message->conversation_id, // أصبح متاحاً للـ Frontend
            'sender_id'       => $this->message->sender_id,
            'receiver_id'     => $this->receiverId,
            'message_text'    => $this->message->message,
            'created_at'      => $createdAt->format('Y-m-d\TH:i:s.u\Z'),
        ];
    }
}