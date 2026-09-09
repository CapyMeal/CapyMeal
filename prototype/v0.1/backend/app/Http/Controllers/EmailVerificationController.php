<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    // El middleware "signed" ya cortó acá si el link fue alterado o venció
    // (403, antes de llegar a este método) -- lo que queda es confirmar
    // que el hash corresponde al email actual del usuario, mismo chequeo
    // que hace el EmailVerificationRequest de Laravel por defecto. Sin
    // auth:sanctum a propósito: el link se abre desde el cliente de
    // correo, en cualquier dispositivo, no necesariamente el que tiene la
    // sesión -- el signed URL en sí es la prueba, no la cookie.
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->email), $hash)) {
            abort(403);
        }

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        if ($user->email_verified_at !== null) {
            return redirect("{$frontendUrl}/email-verificado?status=already");
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect("{$frontendUrl}/email-verificado?status=ok");
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return response()->json(['message' => 'Tu email ya está verificado.']);
        }

        try {
            $user->notify(new VerifyEmailNotification);
        } catch (\Throwable $e) {
            // Mismo patrón que PasswordResetController::forgotPassword():
            // un hiccup de SMTP no debe romper la respuesta, pero sí
            // queda en los logs de Render para poder investigarlo.
            report($e);

            return response()->json([
                'message' => 'No pudimos enviar el email en este momento. Intentá de nuevo en un rato.',
            ], 500);
        }

        return response()->json([
            'message' => 'Te mandamos un nuevo enlace. Revisá tu bandeja de entrada (y spam).',
        ]);
    }
}
