<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesMealEntryFields;
use Illuminate\Foundation\Http\FormRequest;

class StoreMealEntryRequest extends FormRequest
{
    use ValidatesMealEntryFields;

    public function authorize(): bool
    {
        // No hay todavía un modelo existente que autorizar -- el dueño del
        // registro nuevo queda garantizado por
        // $request->user()->mealEntries()->create() en el controller, no
        // por esta validación.
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            ...$this->mealEntryFieldRules(),
        ];
    }
}
