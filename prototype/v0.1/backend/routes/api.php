<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\MealEntryController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Rutas públicas de autenticación
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// Recuperación de contraseña
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->middleware('throttle:6,1');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');

// Login con Google. redirect()/callback() van con middleware "web" (no
// stateless()) a propósito: el round-trip a Google es una navegación real
// de nivel superior en ambos sentidos, así que la cookie de sesión sí viaja
// (SESSION_SAME_SITE=lax lo permite entre navegaciones top-level al mismo
// origen) -- eso habilita la verificación real del parámetro "state" de
// Socialite contra CSRF de login, sin tocar CORS ni el resto de la API
// (que sigue siendo 100% bearer-token stateless). "web" no complica nada
// acá: VerifyCsrfToken sólo mira POST/PUT/PATCH/DELETE, y las dos rutas
// son GET.
Route::middleware('web')->group(function () {
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->middleware('throttle:20,1');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
});
// exchange() sí es bearer-token/stateless como el resto de la API: recibe
// el código de un solo uso que callback() generó y devuelve el token real.
Route::post('/auth/google/exchange', [GoogleAuthController::class, 'exchange'])->middleware('throttle:10,1');

// Rutas protegidas. throttle:60,1,api es el piso para todo el grupo -- antes
// sólo auth y el export a PDF tenían límite, y el resto (listar/crear/
// editar/borrar comidas) quedaba sin ninguno. El export a PDF mantiene
// además su propio límite más estricto (ver abajo). El prefijo "api" es
// necesario: Laravel arma la clave del rate limiter sólo con el ID del
// usuario autenticado (no con la ruta), así que sin un prefijo distinto
// este throttle y el del export a PDF pisarían el mismo contador.
Route::middleware(['auth:sanctum', 'throttle:60,1,api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me/avatar', [AuthController::class, 'updateAvatar']);
    Route::delete('/me', [AuthController::class, 'destroy']);

    Route::get('/meal-entries', [MealEntryController::class, 'index']);
    // Generar el PDF es lo más pesado de la API (renderiza la vista con
    // imagenes/emojis vía DomPDF) -- límite propio, más estricto que el piso
    // del grupo. Prefijo "pdf-export" distinto del throttle del grupo (ver
    // comentario arriba) para que sean dos contadores independientes.
    Route::get('/meal-entries/export/pdf', [MealEntryController::class, 'exportPdf'])->middleware('throttle:10,1,pdf-export');
    Route::get('/meal-entries/{date}', [MealEntryController::class, 'show']);
    Route::post('/meal-entries', [MealEntryController::class, 'store']);
    Route::put('/meal-entries/{date}', [MealEntryController::class, 'update']);
    Route::delete('/meal-entries/{date}', [MealEntryController::class, 'destroy']);
});
