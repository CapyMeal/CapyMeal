<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MealEntryController;
use Illuminate\Support\Facades\Route;

// Rutas públicas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::get('/meal-entries',             [MealEntryController::class, 'index']);
    Route::get('/meal-entries/export/pdf',  [MealEntryController::class, 'exportPdf']);
    Route::get('/meal-entries/{date}',      [MealEntryController::class, 'show']);
    Route::post('/meal-entries',            [MealEntryController::class, 'store']);
    Route::put('/meal-entries/{date}',      [MealEntryController::class, 'update']);
    Route::delete('/meal-entries/{date}',   [MealEntryController::class, 'destroy']);
});
