<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Support both legacy Message model and new ChatMessage model
        $receiverId = $this->receiver_id ?? null;
        if (is_null($receiverId) && isset($this->conversation) && isset($this->sender_id)) {
            $conv = $this->conversation;
            if ($conv->user1_id === $this->sender_id) {
                $receiverId = $conv->user2_id;
            } else {
                $receiverId = $conv->user1_id;
            }
        }

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id ?? null,
            'sender_id' => $this->sender_id,
            'receiver_id' => $receiverId,
            'message' => $this->message,
            'image_url' => $this->image_url ? url(Storage::url($this->image_url)) : null,
            'voice_url' => $this->voice_url ? url(Storage::url($this->voice_url)) : null,
            'video_url' => $this->video_url ?? null,
            'is_read' => $this->is_read ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
