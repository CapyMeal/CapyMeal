<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private const RECOVERY_CODES_COUNT = 10;

    public function setup(Request $request)
    {
        $google2fa = new Google2FA;

        // Pisa cualquier secreto pendiente anterior a propósito: llamar
        // setup() de nuevo antes de confirmar (ej. el escaneo del QR salió
        // mal) tiene que poder reintentarse sin quedar con dos secretos
        // compitiendo. two_factor_confirmed_at no se toca acá -- 2FA sigue
        // sin estar activo hasta confirm().
        $secret = $google2fa->generateSecretKey();
        $request->user()->forceFill(['two_factor_secret' => $secret])->save();

        $otpauthUrl = $google2fa->getQRCodeUrl('CapyMeal', $request->user()->email, $secret);

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);
        $qrCodeSvg = (new Writer($renderer))->writeString($otpauthUrl);

        return response()->json([
            'secret' => $secret,
            'qrCodeSvg' => 'data:image/svg+xml;base64,'.base64_encode($qrCodeSvg),
        ]);
    }

    public function confirm(Request $request)
    {
        $data = $request->validate(['code' => 'required|string']);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            throw ValidationException::withMessages([
                'code' => [__('messages.two_factor_setup_not_started')],
            ]);
        }

        $valid = (new Google2FA)->verifyKey($user->two_factor_secret, $data['code']);

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => [__('messages.two_factor_invalid_code')],
            ]);
        }

        $recoveryCodes = collect(range(1, self::RECOVERY_CODES_COUNT))
            ->map(fn () => Str::random(10).'-'.Str::random(10))
            ->all();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => array_map(fn (string $code) => Hash::make($code), $recoveryCodes),
        ])->save();

        // Los códigos en texto plano sólo existen acá, en memoria, para
        // esta única respuesta -- lo que se guarda arriba son sus hashes.
        // No hay forma de volver a mostrarlos después de esto.
        return response()->json(['recoveryCodes' => $recoveryCodes]);
    }

    // Segundo paso del login cuando hay 2FA activo -- AuthController::login()
    // ya validó la contraseña y dejó un desafío de un solo uso en cache en
    // vez de abrir sesión. Acá se valida el código real (TOTP o de
    // recuperación) y recién ahí se loguea de verdad.
    public function verifyLogin(Request $request)
    {
        $data = $request->validate([
            'challenge' => 'required|string',
            'code' => 'required|string',
        ]);

        $payload = Cache::pull(AuthController::TWO_FACTOR_CHALLENGE_PREFIX.$data['challenge']);
        $user = $payload ? User::find($payload['user_id']) : null;

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => [__('messages.two_factor_challenge_expired')],
            ]);
        }

        if ((new Google2FA)->verifyKey($user->two_factor_secret, $data['code'])) {
            return $this->completeLogin($request, $user);
        }

        // No era un TOTP válido -- se prueba como código de recuperación
        // (cada uno sirve una sola vez, se saca del array apenas se usa).
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        foreach ($recoveryCodes as $index => $hashedCode) {
            if (Hash::check($data['code'], $hashedCode)) {
                unset($recoveryCodes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($recoveryCodes)])->save();

                return $this->completeLogin($request, $user);
            }
        }

        throw ValidationException::withMessages([
            'code' => [__('messages.two_factor_invalid_code')],
        ]);
    }

    private function completeLogin(Request $request, User $user)
    {
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource($user),
            'csrfToken' => $request->session()->token(),
        ]);
    }

    public function disable(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['password' => 'required|string']);

        if (is_null($user->password) || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('messages.password_incorrect')],
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(null, 204);
    }
}
