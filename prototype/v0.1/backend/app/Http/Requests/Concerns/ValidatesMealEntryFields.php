<?php

namespace App\Http\Requests\Concerns;

trait ValidatesMealEntryFields
{
    protected function mealEntryFieldRules(): array
    {
        return [
            'breakfast' => 'nullable|string|max:2000',
            'lunch' => 'nullable|string|max:2000',
            'snack' => 'nullable|string|max:2000',
            'dinner' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
