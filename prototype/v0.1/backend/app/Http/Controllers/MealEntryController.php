<?php

namespace App\Http\Controllers;

use App\Models\MealEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MealEntryController extends Controller
{
    public function index()
    {
        return MealEntry::orderByDesc('date')->get();
    }

    public function show(string $date)
    {
        return MealEntry::where('date', $date)->firstOrFail();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'      => 'required|date|unique:meal_entries,date',
            'breakfast' => 'nullable|string',
            'lunch'     => 'nullable|string',
            'snack'     => 'nullable|string',
            'dinner'    => 'nullable|string',
            'notes'     => 'nullable|string',
        ]);

        $normalized = $this->normalizeEntryFields($data);

        if (!$this->hasAtLeastOneFilledField($normalized)) {
            throw ValidationException::withMessages([
                'entry' => ['Tenés que completar al menos una comida o recuerdo antes de guardar.'],
            ]);
        }

        return response()->json(MealEntry::create($normalized), 201);
    }

    public function update(Request $request, string $date)
    {
        $entry = MealEntry::where('date', $date)->firstOrFail();

        $data = $request->validate([
            'breakfast' => 'nullable|string',
            'lunch'     => 'nullable|string',
            'snack'     => 'nullable|string',
            'dinner'    => 'nullable|string',
            'notes'     => 'nullable|string',
        ]);

        $normalized = $this->normalizeEntryFields($data);

        if (array_key_exists('breakfast', $data) || array_key_exists('lunch', $data) ||
            array_key_exists('snack', $data) || array_key_exists('dinner', $data) ||
            array_key_exists('notes', $data)) {
            if (!$this->hasAtLeastOneFilledField($normalized)) {
                throw ValidationException::withMessages([
                    'entry' => ['No se puede guardar un registro vacío.'],
                ]);
            }
        }

        $entry->update($normalized);

        return response()->json($entry);
    }

    public function destroy(string $date)
    {
        MealEntry::where('date', $date)->firstOrFail()->delete();

        return response()->json(null, 204);
    }

    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $query = MealEntry::query()->orderBy('date');

        if (!empty($validated['from'])) {
            $query->whereDate('date', '>=', $validated['from']);
        }

        if (!empty($validated['to'])) {
            $query->whereDate('date', '<=', $validated['to']);
        }

        $entries = $query->get();

        \Carbon\Carbon::setLocale('es');

        $pdf = Pdf::loadView('pdf.meal-entries', [
            'entries' => $entries,
            'from'    => $validated['from'] ?? null,
            'to'      => $validated['to'] ?? null,
        ]);

        return $pdf->download('capymeal-diario.pdf');
    }

    private function normalizeEntryFields(array $data): array
    {
        foreach (['breakfast', 'lunch', 'snack', 'dinner', 'notes'] as $field) {
            if (array_key_exists($field, $data) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        return $data;
    }

    private function hasAtLeastOneFilledField(array $data): bool
    {
        foreach (['breakfast', 'lunch', 'snack', 'dinner', 'notes'] as $field) {
            if (!empty($data[$field])) {
                return true;
            }
        }

        return false;
    }
}
