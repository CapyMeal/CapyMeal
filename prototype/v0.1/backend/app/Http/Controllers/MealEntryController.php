<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MealEntryController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->mealEntries()->orderByDesc('date')->get();
    }

    public function show(Request $request, string $date)
    {
        return $request->user()->mealEntries()->where('date', $date)->firstOrFail();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'breakfast' => 'nullable|string',
            'lunch' => 'nullable|string',
            'snack' => 'nullable|string',
            'dinner' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Un registro por usuario y fecha
        if ($request->user()->mealEntries()->where('date', $data['date'])->exists()) {
            throw ValidationException::withMessages([
                'date' => ['Ya existe un registro para esa fecha.'],
            ]);
        }

        $normalized = $this->normalizeEntryFields($data);

        if (! $this->hasAtLeastOneFilledField($normalized)) {
            throw ValidationException::withMessages([
                'entry' => ['Tenés que completar al menos una comida o recuerdo antes de guardar.'],
            ]);
        }

        $entry = $request->user()->mealEntries()->create($normalized);

        return response()->json($entry, 201);
    }

    public function update(Request $request, string $date)
    {
        $entry = $request->user()->mealEntries()->where('date', $date)->firstOrFail();

        $data = $request->validate([
            'breakfast' => 'nullable|string',
            'lunch' => 'nullable|string',
            'snack' => 'nullable|string',
            'dinner' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $normalized = $this->normalizeEntryFields($data);

        if (! $this->hasAtLeastOneFilledField($normalized)) {
            throw ValidationException::withMessages([
                'entry' => ['No se puede guardar un registro vacío.'],
            ]);
        }

        $entry->update($normalized);

        return response()->json($entry);
    }

    public function destroy(Request $request, string $date)
    {
        $request->user()->mealEntries()->where('date', $date)->firstOrFail()->delete();

        return response()->json(null, 204);
    }

    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $query = $request->user()->mealEntries()->orderBy('date');

        if (! empty($validated['from'])) {
            $query->whereDate('date', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->whereDate('date', '<=', $validated['to']);
        }

        $entries = $query->get();

        Carbon::setLocale('es');

        // isRemoteEnabled queda deshabilitado (default de DomPDF): todas las
        // imagenes de la vista (iconos, emojis) se incrustan como data URIs
        // ya resueltas en PHP, asi que DomPDF nunca necesita pedir una URL
        // por su cuenta. Dejarlo deshabilitado evita que texto del usuario
        // (comidas/notas) pueda hacer que el servidor pida una URL remota.
        $pdf = Pdf::loadView('pdf.meal-entries', [
            'entries' => $entries,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
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
            if (! empty($data[$field])) {
                return true;
            }
        }

        return false;
    }
}
