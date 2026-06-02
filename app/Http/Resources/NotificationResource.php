<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'notification_id' => $this->notification_id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'extra_data' => $this->formatExtraData(),
            'reference_id' => $this->reference_id,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at ? $this->read_at->format('Y-m-d H:i:s') : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }

    /**
     * Format the extra_data based on notification type.
     */
    protected function formatExtraData(): ?array
    {
        // If extra_data exists from the DB, return it natively formatted
        $data = $this->extra_data ?? [];

        switch ($this->type) {
            case 'like':
                return [
                    'post_id' => $data['post_id'] ?? null,
                    'username' => $data['username'] ?? null,
                    'likes_count' => $data['likes_count'] ?? 0,
                ];
            case 'comment':
                return [
                    'post_id' => $data['post_id'] ?? null,
                    'username' => $data['username'] ?? null,
                    'comments_count' => $data['comments_count'] ?? 0,
                ];
            case 'alert':
                return [
                    'glucose_level' => $data['glucose_level'] ?? null,
                    'alert_type' => $data['alert_type'] ?? null,
                ];
            case 'reminder':
                return [
                    'type' => $data['type'] ?? null,
                    'title' => $data['title'] ?? null,
                ];
            default:
                // For 'chat', 'friend_request', or others, return as is or as empty object-like array
                return !empty($data) ? $data : null;
        }
    }
}
