<?php

namespace App\Policies;

use App\Models\SelectedMedication;
use App\Models\User;

class SelectedMedicationPolicy
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
    public function view(User $user, SelectedMedication $selectedMedication): bool
    {
        return $user->id === $selectedMedication->user_id;
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
    public function update(User $user, SelectedMedication $selectedMedication): bool
    {
        return $user->id === $selectedMedication->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SelectedMedication $selectedMedication): bool
    {
        return $user->id === $selectedMedication->user_id;
    }
}
