<?php

namespace App\Policies;

use App\Models\MealEntry;
use App\Models\User;

class MealEntryPolicy
{
    public function view(User $user, MealEntry $mealEntry): bool
    {
        return $user->id === $mealEntry->user_id;
    }

    public function update(User $user, MealEntry $mealEntry): bool
    {
        return $user->id === $mealEntry->user_id;
    }

    public function delete(User $user, MealEntry $mealEntry): bool
    {
        return $user->id === $mealEntry->user_id;
    }
}
