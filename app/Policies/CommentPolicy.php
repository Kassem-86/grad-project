<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Determine if the user can delete the comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }
    public function update(User $user, Comment $comment): bool
{
    // لازم يتأكد إن الـ user_id اللي في الكومنت هو هو الـ id بتاع اليوزر اللي عامل login
    return $user->id === $comment->user_id;
}
}
