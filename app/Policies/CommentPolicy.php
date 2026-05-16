<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Perform authorization checks that apply to all methods.
     * If this method returns a non-null boolean, that value will be used.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Admins or moderators can perform any action on comments
        // You can add this check if needed
        return null;
    }

    /**
     * Determine if the user can update the comment.
     * Performs strict integer type casting to prevent type mismatch issues.
     */
    public function update(User $user, Comment $comment): bool
    {
        // Ensure both values are cast to integers for strict comparison
        $userId = (int) $user->id;
        $commentUserId = (int) $comment->user_id;

        return $userId === $commentUserId;
    }

    /**
     * Determine if the user can delete the comment.
     * Performs strict integer type casting to prevent type mismatch issues.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Ensure both values are cast to integers for strict comparison
        $userId = (int) $user->id;
        $commentUserId = (int) $comment->user_id;

        return $userId === $commentUserId;
    }

    /**
     * Determine if the user can view the comment.
     */
    public function view(User $user, Comment $comment): bool
    {
        // Everyone can view comments
        return true;
    }

    /**
     * Determine if the user can create a comment.
     */
    public function create(User $user): bool
    {
        // Authenticated users can create comments
        return true;
    }
}

