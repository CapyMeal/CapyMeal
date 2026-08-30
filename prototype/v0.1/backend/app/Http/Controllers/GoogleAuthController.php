<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private const EXCHANGE_TTL_SECONDS = 60;

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();
        $user = $this->findOrCreateUser($googleUser);

        // El token de Sanctum se genera recién en exchange(), no acá -- si
        // el código nunca se canjea (pestaña cerrada, error de red), no
        // queda un token real huérfano en personal_access_tokens sin nada
        // que lo revoque, porque nunca se llegó a crear.
        $code = Str::random(64);
        Cache::put(
            "google-auth-exchange:{$code}",
            ['user_id' => $user->id],
            now()->addSeconds(self::EXCHANGE_TTL_SECONDS)
        );

        $frontendUrl = rtrim(config('services.google.frontend_url'), '/');

        return redirect("{$frontendUrl}/auth/google/callback?code={$code}");
    }

    public function exchange(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string',
        ]);

        // Cache::pull() en vez de get()+forget() separados: el código es de
        // un solo uso. En el store "database" esto no es perfectamente
        // atómico (ventana teórica de TOCTOU entre dos canjes simultáneos
        // del mismo código), pero explotarla ya requiere poseer un código
        // aleatorio de 64 caracteres con vida de 60 segundos -- en ese punto
        // ya se puede loguear una vez sin necesidad de la carrera.
        $payload = Cache::pull("google-auth-exchange:{$data['code']}");
        $user = $payload ? User::find($payload['user_id']) : null;

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => ['Este enlace ya no es válido. Iniciá sesión de nuevo.'],
            ]);
        }

        $token = $user->createToken('capymeal')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    private function findOrCreateUser(SocialiteUser $googleUser): User
    {
        $user = User::where('google_id', $googleUser->getId())->first();
        if ($user) {
            return $user;
        }

        // Auto-link: Google sólo devuelve emails verificados, así que si ya
        // existe una cuenta con este email (creada por password) es seguro
        // enlazarla en vez de bloquear o crear una cuenta duplicada.
        $user = User::where('email', $googleUser->getEmail())->first();
        if ($user) {
            $user->forceFill(['google_id' => $googleUser->getId()])->save();

            return $user;
        }

        $newUser = User::create([
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: $googleUser->getEmail(),
            'email' => $googleUser->getEmail(),
            'password' => null,
        ]);

        // google_id y email_verified_at no son fillable a propósito (ver
        // comentario en User.php) -- Google ya verificó este email, así
        // que se marca como tal en el mismo momento en que se crea la
        // cuenta.
        $newUser->forceFill([
            'google_id' => $googleUser->getId(),
            'email_verified_at' => now(),
        ])->save();

        return $newUser;
    }
}
