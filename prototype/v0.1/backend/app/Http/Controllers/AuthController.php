<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Avatares ilustrados disponibles para elegir (ademas de Gravatar,
    // que es el default cuando 'avatar' es null). Mantener en sync con
    // las opciones que muestra Settingsview.vue en el frontend.
    private const AVAILABLE_AVATARS = ['capy1', 'capy2', 'capy3'];

    // Prefijo del cache donde vive el desafío de 2FA entre login() (que lo
    // crea) y TwoFactorController::verifyLogin() (que lo consume) -- public
    // para que el otro controller lo use sin duplicar el string. Mismo
    // patrón que EXCHANGE_CACHE_PREFIX ya usa SocialAuthController para su
    // código de un solo uso.
    public const TWO_FACTOR_CHALLENGE_PREFIX = 'two-factor-challenge:';

    private const TWO_FACTOR_CHALLENGE_TTL_SECONDS = 300;

    // El patrón habitual de Sanctum (cookie XSRF-TOKEN, legible por JS) no
    // sirve acá: frontend y backend son dominios sin ninguna relación
    // (capy-meal.vercel.app / capymeal.onrender.com), así que una cookie
    // que puso este backend es invisible para el JS que corre en el
    // frontend -- document.cookie nunca la muestra, sin importar
    // SameSite/Secure. El token viaja en el body en su lugar: esta ruta lo
    // entrega antes del primer login/register, y login/register/exchange lo
    // reenvían fresco en su propia respuesta (regenerate() lo rota).
    public function csrfToken(Request $request)
    {
        return response()->json(['token' => $request->session()->token()]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create($data)->fresh();

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        try {
            $user->notify(new VerifyEmailNotification);
        } catch (\Throwable $e) {
            // El registro tiene que completarse igual aunque el mail
            // falle (verificación blanda, no gatea nada) -- mismo patrón
            // de resiliencia que PasswordResetController::forgotPassword().
            report($e);
        }

        return response()->json([
            'user' => new UserResource($user),
            'csrfToken' => $request->session()->token(),
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
                'email' => [__('messages.login_failed')],
            ]);
        }

        $this->assertPasswordMatches($user, $data['password'], 'email', __('messages.login_failed'));

        // Con 2FA activo, la contraseña sola no alcanza -- todavía no se
        // abre sesión. Se guarda un desafío de un solo uso en cache (mismo
        // patrón que el código de intercambio de SocialAuthController) y el
        // login de verdad queda a cargo de TwoFactorController::verifyLogin(),
        // una vez que el código de la app autenticadora (o un código de
        // recuperación) confirme que es la misma persona.
        if ($user->hasTwoFactorEnabled()) {
            $challenge = Str::random(40);
            Cache::put(
                self::TWO_FACTOR_CHALLENGE_PREFIX.$challenge,
                ['user_id' => $user->id],
                now()->addSeconds(self::TWO_FACTOR_CHALLENGE_TTL_SECONDS)
            );

            return response()->json([
                'twoFactorRequired' => true,
                'challenge' => $challenge,
            ]);
        }

        // A propósito no se invalidan las sesiones existentes acá: CapyMeal
        // es una PWA pensada para usarse desde varios dispositivos (celular
        // + compu), y loguearse en uno no debería desloguear al otro sin
        // aviso. El logout real sigue existiendo donde sí hay una razón de
        // seguridad real -- reset de contraseña y borrado de cuenta.
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource($user),
            'csrfToken' => $request->session()->token(),
        ]);
    }

    public function logout(Request $request)
    {
        // Un bearer token real (cliente sin sesión de navegador, o uno viejo
        // emitido antes de esta migración a cookies) se revoca de verdad acá.
        // Autenticado por cookie, currentAccessToken() devuelve un
        // TransientToken sin fila real en la tabla ni delete() -- nada que
        // borrar ahí, sólo cerrar la sesión (bloque de abajo). El @var mixed
        // es necesario para Larastan: tipa currentAccessToken() como
        // PersonalAccessToken siempre (vía el genérico TToken de Sanctum),
        // así que sin esto method_exists() le queda tautológico aunque en
        // runtime sí pueda ser un TransientToken.
        /** @var mixed $token */
        $token = $request->user()->currentAccessToken();
        if ($token !== null && method_exists($token, 'delete')) {
            $token->delete();
        }

        // Sin sesión de navegador (mismo caso que el bearer token de arriba)
        // no hay nada que invalidar -- $request->session() explota si se lo
        // llama sin que EnsureFrontendRequestsAreStateful haya arrancado una.
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

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
        $user = $request->user();

        // Las cuentas de Google no tienen contraseña propia (ver
        // GoogleAuthController) -- para esas, no hay nada que confirmar más
        // allá de la sesión autenticada, la misma protección que ya tiene
        // logout()/updateAvatar()/toda mutación de meal_entries. No es una
        // reducción de seguridad respecto al resto de la API: acá el
        // password nunca fue el único factor real, era un extra disponible
        // sólo cuando existía.
        if ($user->password) {
            $data = $request->validate(['password' => 'required|string']);
            $this->assertPasswordMatches($user, $data['password'], 'password', __('messages.password_incorrect'));
        }

        DB::transaction(function () use ($user) {
            // password_reset_tokens se indexa por email, no por user_id --
            // sin esto, un token de recuperación viejo quedaría huerfano
            // para ese email despues de borrar la cuenta.
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            // personal_access_tokens (Sanctum) es polimorfico, sin FK/cascade
            // -- a diferencia de meal_entries, hay que revocarlos a mano.
            $user->tokens()->delete();

            // sessions tampoco tiene FK/cascade hacia users (ver la
            // migración original) -- sin este delete, una sesión de cookie
            // ya abierta en otro dispositivo seguía autenticada contra un
            // usuario que ya no existe hasta que esa fila expirara sola.
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->delete();
        });

        return response()->json(null, 204);
    }

    private function assertPasswordMatches(User $user, string $password, string $field, string $message): void
    {
        // Una cuenta creada vía Google tiene password null -- nunca puede
        // "coincidir" con nada que se envíe acá. Mismo mensaje genérico de
        // siempre (nunca uno distinto que revele que la cuenta es de
        // Google, mismo principio anti-enumeración que ya usa login()).
        if (is_null($user->password) || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([$field => [$message]]);
        }
    }
}
