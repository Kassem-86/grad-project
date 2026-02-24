<?php

namespace App\Policies;

use App\Models\Glucose;
use App\Models\User;

class GlucosePolicy
{
    /**
     * Determine if the user can view the glucose entry.
     */
    public function view(User $user, Glucose $glucose): bool
    {
        return $user->id === $glucose->log->user_id;
    }

    /**
     * Determine if the user can update the glucose entry.
     */
    public function update(User $user, Glucose $glucose): bool
    {
        return $user->id === $glucose->log->user_id;
    }

    /**
     * Determine if the user can delete the glucose entry.
     */
    public function delete(User $user, Glucose $glucose): bool
    {
        return $user->id === $glucose->log->user_id;
    }
}
