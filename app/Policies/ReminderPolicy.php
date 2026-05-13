<?php

namespace App\Policies;

use App\Models\Reminder;
use App\Models\User;

class ReminderPolicy
{
    /**
     * Determine if the user can update the reminder.
     */
    public function update(User $user, Reminder $reminder): bool
    {
        return $user->id === $reminder->user_id;
    }

    /**
     * Determine if the user can delete the reminder.
     */
    public function delete(User $user, Reminder $reminder): bool
    {
        return $user->id === $reminder->user_id;
    }
}
