<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMealEntryRequest;
use App\Http\Requests\UpdateMealEntryRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function store(StoreMealEntryRequest $request)
    {
        $normalized = $this->normalizeEntryFields($request->validated());

        // Un registro por usuario y fecha: se confía en el índice único
        // (user_id, date) como fuente de verdad en vez de un exists() previo
        // -- ese chequeo puede quedar desactualizado entre la lectura y el
        // insert bajo requests concurrentes (doble click, reintento de red
        // del frontend), lo que antes terminaba en un 500 sin capturar en
        // vez de este 422. El create() va envuelto en DB::transaction()
        // porque en Postgres un statement fallido dentro de una transacción
        // (la de más afuera de cada test, o una futura del caller en
        // producción) deja la conexión entera "abortada" hasta el rollback
        // -- sin este wrapper, capturar la excepción acá no alcanza para
        // que cualquier query posterior en la misma transacción funcione.
        try {
            $entry = DB::transaction(
                fn () => $request->user()->mealEntries()->create($normalized)
            );
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'date' => ['Ya existe un registro para esa fecha.'],
            ]);
        }

        return response()->json($entry, 201);
    }

    public function update(UpdateMealEntryRequest $request, string $date)
    {
        $entry = $request->user()->mealEntries()->where('date', $date)->firstOrFail();

        $this->authorize('update', $entry);

        $normalized = $this->normalizeEntryFields($request->validated());

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
        // Tope duro de 5 años, siempre aplicado -- el filtro from/to de
        // arriba es opcional (sin él, se sigue devolviendo "todo"), así que
        // sin este tope un historial de años sin filtrar trae y renderiza
        // (en exportPdf) el diario entero de una sola vez, sin límite real.
        $query->whereDate('date', '>=', Carbon::now()->subYears(5)->toDateString());

        if (! empty($validated['from'])) {
            $query->whereDate('date', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->whereDate('date', '<=', $validated['to']);
        }

        return $query;
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
}
