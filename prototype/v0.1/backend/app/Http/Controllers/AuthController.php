<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Avatares ilustrados disponibles para elegir (ademas de Gravatar,
    // que es el default cuando 'avatar' es null). Mantener en sync con
    // las opciones que muestra Settingsview.vue en el frontend.
    private const AVAILABLE_AVATARS = ['capy1', 'capy2', 'capy3'];

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create($data)->fresh();
        $token = $user->createToken('capymeal')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['El email o la contraseña son incorrectos.'],
            ]);
        }

        $this->assertPasswordMatches($user, $data['password'], 'email', 'El email o la contraseña son incorrectos.');

        // A propósito no se revocan los tokens existentes acá: CapyMeal es
        // una PWA pensada para usarse desde varios dispositivos (celular +
        // compu), y loguearse en uno no debería desloguear al otro sin
        // aviso. El revoke sigue existiendo donde sí hay una razón de
        // seguridad real -- reset de contraseña y borrado de cuenta.
        $token = $user->createToken('capymeal')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    public function me(Request $request)
    {
        return response()->json(new UserResource($request->user()));
    }

    public function updateAvatar(Request $request)
    {
        $data = $request->validate([
            'avatar' => ['nullable', 'string', Rule::in(self::AVAILABLE_AVATARS)],
        ]);

        $request->user()->update(['avatar' => $data['avatar'] ?? null]);

        return response()->json(new UserResource($request->user()->fresh()));
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        $this->assertPasswordMatches($user, $data['password'], 'password', 'La contraseña no es correcta.');

        DB::transaction(function () use ($user) {
            // password_reset_tokens se indexa por email, no por user_id --
            // sin esto, un token de recuperación viejo quedaría huerfano
            // para ese email despues de borrar la cuenta.
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            // personal_access_tokens (Sanctum) es polimorfico, sin FK/cascade
            // -- a diferencia de meal_entries, hay que revocarlos a mano.
            $user->tokens()->delete();

            $user->delete();
        });

        return response()->json(null, 204);
    }

    private function assertPasswordMatches(User $user, string $password, string $field, string $message): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([$field => [$message]]);
        }
    }
}
