<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesMealEntryFields
{
    private const MEAL_ENTRY_FIELDS = ['breakfast', 'lunch', 'snack', 'dinner', 'notes'];

    protected function mealEntryFieldRules(): array
    {
        return array_fill_keys(self::MEAL_ENTRY_FIELDS, 'nullable|string|max:2000');
    }

    // "Al menos un campo lleno" es una regla de negocio, no de formato --
    // antes vivía duplicada como un chequeo manual en el controller,
    // después de que esta misma validación ya había corrido. Vive acá para
    // que ambos FormRequests (Store/Update) la apliquen automáticamente y
    // el controller quede libre de reglas de negocio de validación.
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->getData();

            $hasContent = collect(self::MEAL_ENTRY_FIELDS)
                ->contains(fn (string $field) => trim((string) ($data[$field] ?? '')) !== '');

            if (! $hasContent) {
                $validator->errors()->add('entry', 'Tenés que completar al menos una comida o recuerdo antes de guardar.');
            }
        });
    }
}
