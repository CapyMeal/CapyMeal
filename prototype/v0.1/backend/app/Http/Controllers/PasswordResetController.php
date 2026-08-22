<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $status = Password::sendResetLink($data);
        } catch (\Throwable $e) {
            // Password::sendResetLink() manda el mail de forma sincrónica
            // (QUEUE_CONNECTION=sync): si el SMTP falla (credenciales,
            // remitente no verificado en Brevo, etc.) la excepción llega
            // hasta acá sin capturar y el usuario veía un "Server Error"
            // en inglés sin ninguna pista de la causa real. Se loguea la
            // excepción real (queda en los logs de Render) y se responde
            // con un mensaje claro en español.
            report($e);

            return response()->json([
                'message' => 'No pudimos enviar el email en este momento. Intentá de nuevo en un rato.',
            ], 500);
        }

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'message' => 'Ya enviamos un enlace hace poco. Esperá un minuto antes de volver a intentarlo.',
            ], 429);
        }

        // Siempre la misma respuesta genérica exista o no la cuenta,
        // para no filtrar si un email está registrado.
        return response()->json([
            'message' => 'Si existe una cuenta con ese email, vas a recibir un enlace en los próximos minutos.',
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            // Cerramos sesión en todos los dispositivos: si alguien
            // resetea su contraseña, probablemente sospecha que otra
            // persona tenía acceso a la cuenta.
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Tu contraseña fue actualizada.',
        ]);
    }
}
