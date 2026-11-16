<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Límite de intentos fallidos por cuenta, independiente de la IP.
 *
 * Los throttles de ruta cuentan por IP, y la IP sale de X-Forwarded-For
 * (ver trustProxies en bootstrap/app.php). Quien le pegue directo al
 * backend de Render, sin pasar por Vercel, puede inventar una IP distinta
 * en cada intento y saltear ese límite. Este contador va por cuenta: da
 * igual desde cuántas IPs se pruebe, cada cuenta admite pocos intentos
 * fallidos seguidos.
 *
 * El login cuenta por email (exista o no la cuenta, para no revelar cuáles
 * existen); el código de la verificación en dos pasos, por usuario.
 */
final class AccountAttemptLimiter
{
    public const MAX_FAILED_ATTEMPTS = 5;

    public const LOCK_SECONDS = 900;

    public static function loginKey(string $email): string
    {
        return 'account-attempts:login:'.sha1(Str::lower(trim($email)));
    }

    public static function twoFactorKey(int $userId): string
    {
        return 'account-attempts:two-factor:'.$userId;
    }

    /**
     * Corta antes de verificar nada si la cuenta ya juntó demasiados
     * intentos fallidos. Responde 429 con un mensaje que dice cuánto falta.
     */
    public static function ensureNotLocked(string $key, string $field): void
    {
        if (! RateLimiter::tooManyAttempts($key, self::MAX_FAILED_ATTEMPTS)) {
            return;
        }

        $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

        throw ValidationException::withMessages([
            $field => [trans_choice('messages.account_attempts_locked', $minutes, ['minutes' => $minutes])],
        ])->status(429);
    }

    public static function recordFailure(string $key): void
    {
        RateLimiter::hit($key, self::LOCK_SECONDS);
    }

    public static function clear(string $key): void
    {
        RateLimiter::clear($key);
    }
}
