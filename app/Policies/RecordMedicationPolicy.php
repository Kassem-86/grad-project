<?php

namespace App\Policies;

use App\Models\RecordMedication;
use App\Models\User;

class RecordMedicationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RecordMedication $recordMedication): bool
    {
        return $user->id === $recordMedication->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RecordMedication $recordMedication): bool
    {
        return $user->id === $recordMedication->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RecordMedication $recordMedication): bool
    {
        return $user->id === $recordMedication->user_id;
    }
}
