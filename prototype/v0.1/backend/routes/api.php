<?php

use App\Http\Controllers\MealEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/meal-entries',          [MealEntryController::class, 'index']);
Route::get('/meal-entries/{date}',   [MealEntryController::class, 'show']);
Route::post('/meal-entries',         [MealEntryController::class, 'store']);
Route::put('/meal-entries/{date}',   [MealEntryController::class, 'update']);
Route::delete('/meal-entries/{date}',[MealEntryController::class, 'destroy']);
