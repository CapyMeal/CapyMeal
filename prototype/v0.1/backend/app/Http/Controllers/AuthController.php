<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user  = User::create($data)->fresh();
        $token = $user->createToken('capymeal')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['El email o la contraseña son incorrectos.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('capymeal')->plainTextToken;

        return response()->json([
            'user'  => $user,
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
        return response()->json($request->user());
    }

    public function updateAvatar(Request $request)
    {
        $data = $request->validate([
            'avatar' => ['nullable', 'string', Rule::in(self::AVAILABLE_AVATARS)],
        ]);

        $request->user()->update(['avatar' => $data['avatar'] ?? null]);

        return response()->json($request->user()->fresh());
    }
}
