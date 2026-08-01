<?php

namespace App\Http\Controllers;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function sendLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Busca el usuario y envía el link (usa password_reset_tokens internamente)
        $status = Password::sendResetLink(
            $request->only('email'),
            function ($user, $token) {
                $user->notify(new ResetPasswordNotification($token));
            }
        );

        // Siempre respondemos igual para no exponer si el email existe
        return response()->json([
            'message' => 'Si existe una cuenta con ese email, recibirás un enlace para restablecer tu contraseña.',
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])
                     ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Contraseña actualizada correctamente.']);
        }

        return response()->json(['message' => 'El enlace es inválido o expiró. Pedí uno nuevo.'], 422);
    }
}
