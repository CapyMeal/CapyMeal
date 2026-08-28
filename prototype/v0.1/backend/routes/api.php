<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MealEntryController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Rutas públicas de autenticación
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// Recuperación de contraseña
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->middleware('throttle:6,1');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me/avatar', [AuthController::class, 'updateAvatar']);
    Route::delete('/me', [AuthController::class, 'destroy']);

    Route::get('/meal-entries', [MealEntryController::class, 'index']);
    // Generar el PDF es lo más pesado de la API (renderiza la vista con
    // imagenes/emojis vía DomPDF) -- sin límite, alguien podría pedirlo en
    // loop y cargar el servidor de más. throttle ya cuenta por usuario acá
    // (auth:sanctum), no por IP.
    Route::get('/meal-entries/export/pdf', [MealEntryController::class, 'exportPdf'])->middleware('throttle:10,1');
    Route::get('/meal-entries/{date}', [MealEntryController::class, 'show']);
    Route::post('/meal-entries', [MealEntryController::class, 'store']);
    Route::put('/meal-entries/{date}', [MealEntryController::class, 'update']);
    Route::delete('/meal-entries/{date}', [MealEntryController::class, 'destroy']);
});
