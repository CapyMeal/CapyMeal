<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesMealEntryFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMealEntryRequest extends FormRequest
{
    use ValidatesMealEntryFields;

    public function authorize(): bool
    {
        // La autorización real (que el registro sea del usuario logueado) la
        // hace el controller vía MealEntryPolicy, después de resolver el
        // modelo -- acá todavía no hay nada que autorizar.
        return true;
    }

    public function rules(): array
    {
        return $this->mealEntryFieldRules();
    }
}
