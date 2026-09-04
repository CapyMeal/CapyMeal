<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

abstract class SocialAuthController extends Controller
{
    private const EXCHANGE_TTL_SECONDS = 60;

    // Prefijo genérico: el canje no depende de qué proveedor generó el
    // código (ver exchange()), así que un solo namespace de cache alcanza
    // para todos los proveedores sociales.
    private const EXCHANGE_CACHE_PREFIX = 'social-auth-exchange:';

    abstract protected function driver(): string;

    abstract protected function providerIdColumn(): string;

    public function redirect()
    {
        return Socialite::driver($this->driver())->redirect();
    }

    public function callback()
    {
        $socialiteUser = Socialite::driver($this->driver())->user();
        $user = $this->findOrCreateUser($socialiteUser);

        // La sesión autenticada se abre recién en exchange(), no acá -- si
        // el código nunca se canjea (pestaña cerrada, error de red), nunca
        // se llega a crear una sesión huérfana.
        $code = Str::random(64);
        Cache::put(
            self::EXCHANGE_CACHE_PREFIX.$code,
            ['user_id' => $user->id],
            now()->addSeconds(self::EXCHANGE_TTL_SECONDS)
        );

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return redirect("{$frontendUrl}/auth/{$this->driver()}/callback?code={$code}");
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
        $payload = Cache::pull(self::EXCHANGE_CACHE_PREFIX.$data['code']);
        $user = $payload ? User::find($payload['user_id']) : null;

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => ['Este enlace ya no es válido. Iniciá sesión de nuevo.'],
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    // Por defecto usamos el email tal cual lo devuelve Socialite. Google
    // sólo entrega emails verificados, así que esto alcanza ahí;
    // MicrosoftAuthController lo sobreescribe porque su caso es distinto
    // (ver comentario en esa clase).
    protected function extractEmail(SocialiteUser $socialiteUser): ?string
    {
        return $socialiteUser->getEmail();
    }

    private function findOrCreateUser(SocialiteUser $socialiteUser): User
    {
        $column = $this->providerIdColumn();

        $user = User::where($column, $socialiteUser->getId())->first();
        if ($user) {
            return $user;
        }

        $email = $this->extractEmail($socialiteUser);

        // Auto-link: si ya existe una cuenta con este email (creada por
        // password, o por otro proveedor social) es seguro enlazarla en vez
        // de bloquear o crear una cuenta duplicada -- siempre que el
        // proveedor garantice que el email es verificado (ver
        // extractEmail()).
        $user = $email ? User::where('email', $email)->first() : null;
        if ($user) {
            $user->forceFill([$column => $socialiteUser->getId()])->save();

            return $user;
        }

        $newUser = User::create([
            'name' => $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: $email,
            'email' => $email,
            'password' => null,
        ]);

        // El id del proveedor y email_verified_at no son fillable a
        // propósito (ver comentario en User.php) -- el proveedor ya
        // verificó este email, así que se marca como tal en el mismo
        // momento en que se crea la cuenta.
        $newUser->forceFill([
            $column => $socialiteUser->getId(),
            'email_verified_at' => now(),
        ])->save();

        return $newUser;
    }
}
