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
        // "from"/"to" son opcionales: sin ellos se sigue devolviendo todo el
        // diario (mismo comportamiento de siempre), pero le permiten al
        // frontend pedir sólo el rango que va a mostrar en vez de traer y
        // filtrar todo del lado del cliente -- mismo patrón que exportPdf().
        $validated = $request->validate($this->dateRangeRules());

        $query = $this->applyDateRangeFilter(
            $request->user()->mealEntries()->orderByDesc('date'),
            $validated
        );

        return $query->get();
    }

    public function show(Request $request, string $date)
    {
        // No tener registro para un dia es el caso normal (la mayoria de los
        // dias no tienen uno todavia), no un error: se devuelve 200 con
        // `null` en vez de 404, para no tratar como excepcion algo esperado.
        // response()->json(null) no sirve: Symfony convierte un $data null
        // en un objeto vacio "{}" en vez de mandar el literal "null".
        $entry = $request->user()->mealEntries()->where('date', $date)->first();

        // El lookup ya está escopeado al usuario autenticado, así que esto
        // nunca debería fallar -- es la red de seguridad ante un futuro
        // cambio que rompa ese scoping sin querer. Con guard porque $entry
        // puede ser null a propósito (ver comentario arriba), y authorize()
        // no acepta un modelo null.
        if ($entry) {
            $this->authorize('view', $entry);
        }

        return response(json_encode($entry), 200, ['Content-Type' => 'application/json']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            ...$this->entryFieldRules(),
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

        $this->authorize('update', $entry);

        $data = $request->validate($this->entryFieldRules());

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
        $entry = $request->user()->mealEntries()->where('date', $date)->firstOrFail();

        $this->authorize('delete', $entry);

        $entry->delete();

        return response()->json(null, 204);
    }

    public function exportPdf(Request $request)
    {
        $validated = $request->validate($this->dateRangeRules());

        $query = $this->applyDateRangeFilter(
            $request->user()->mealEntries()->orderBy('date'),
            $validated
        );

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

    private function dateRangeRules(): array
    {
        return [
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ];
    }

    private function applyDateRangeFilter($query, array $validated)
    {
        if (! empty($validated['from'])) {
            $query->whereDate('date', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->whereDate('date', '<=', $validated['to']);
        }

        return $query;
    }

    private function entryFieldRules(): array
    {
        return [
            'breakfast' => 'nullable|string|max:2000',
            'lunch' => 'nullable|string|max:2000',
            'snack' => 'nullable|string|max:2000',
            'dinner' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ];
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
