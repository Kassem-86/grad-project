<?php

namespace App\Policies;

use App\Models\Medication;
use App\Models\User;

class MedicationPolicy
{
    /**
     * Determine if the user can view the medication entry.
     */
    public function view(User $user, Medication $medication): bool
    {
        return $user->id === $medication->log->user_id;
    }

    /**
     * Determine if the user can update the medication entry.
     */
    public function update(User $user, Medication $medication): bool
    {
        return $user->id === $medication->log->user_id;
    }

    /**
     * Determine if the user can delete the medication entry.
     */
    public function delete(User $user, Medication $medication): bool
    {
        return $user->id === $medication->log->user_id;
    }
}
