<?php

namespace App\Observers;

use App\Models\Comment;

class CommentObserver
{
    /**
     * Handle the Comment "created" event.
     * Increment the post's comments_count when a comment is created.
     */
    public function created(Comment $comment): void
    {
        $comment->post->increment('comments_count');
    }

    /**
     * Handle the Comment "deleting" event.
     * Decrement the post's comments_count when a comment is deleted.
     */
    public function deleting(Comment $comment): void
    {
        $comment->post->decrement('comments_count');
    }
}
