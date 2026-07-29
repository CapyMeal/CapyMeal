<?php

namespace App\Http\Controllers;

use App\Models\MealEntry;
use Illuminate\Http\Request;

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

        return response()->json(MealEntry::create($data), 201);
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

        $entry->update($data);

        return response()->json($entry);
    }

    public function destroy(string $date)
    {
        MealEntry::where('date', $date)->firstOrFail()->delete();

        return response()->json(null, 204);
    }
}
