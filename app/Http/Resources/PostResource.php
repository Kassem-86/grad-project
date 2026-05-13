<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')), // ✅ مكانها الصح
            'title' => $this->title,
            'content' => $this->content,
            'images' => PostImageResource::collection($this->whenLoaded('images')),
            'category' => $this->category,
            'likes_count' => $this->likes_count,
            'comments_count' => $this->comments_count,
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'likes' => LikeResource::collection($this->whenLoaded('likes')),
            'is_liked' => $this->is_liked,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]; 
    }
}
