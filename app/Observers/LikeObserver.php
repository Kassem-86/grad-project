<?php

namespace App\Observers;

use App\Models\Like;
use App\Models\Post;
use App\Models\Comment;

class LikeObserver
{
    /**
     * Handle the Like "created" event.
     * Increment likes_count for the likeable model (Post or Comment).
     */
    public function created(Like $like): void
    {
        if ($like->likeable instanceof Post) {
            $like->likeable->increment('likes_count');
        } elseif ($like->likeable instanceof Comment) {
            $like->likeable->increment('likes_count');
        }
    }

    /**
     * Handle the Like "deleting" event.
     * Decrement likes_count for the likeable model (Post or Comment).
     */
    public function deleting(Like $like): void
    {
        if ($like->likeable instanceof Post) {
            $like->likeable->decrement('likes_count');
        } elseif ($like->likeable instanceof Comment) {
            $like->likeable->decrement('likes_count');
        }
    }
}
