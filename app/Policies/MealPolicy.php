<?php

namespace App\Policies;

use App\Models\Meal;
use App\Models\User;

class MealPolicy
{
    /**
     * Determine if the user can view the meal entry.
     */
    public function view(User $user, Meal $meal): bool
    {
        return $user->id === $meal->log->user_id;
    }

    /**
     * Determine if the user can update the meal entry.
     */
    public function update(User $user, Meal $meal): bool
    {
        return $user->id === $meal->log->user_id;
    }

    /**
     * Determine if the user can delete the meal entry.
     */
    public function delete(User $user, Meal $meal): bool
    {
        return $user->id === $meal->log->user_id;
    }
}
